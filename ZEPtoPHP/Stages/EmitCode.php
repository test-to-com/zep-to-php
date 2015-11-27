<?php

/*
  +--------------------------------------------------------------------------+
  | ZEP to PHP Translator                                                    |
  +--------------------------------------------------------------------------+
  | Copyright (c) 2015 pf ar sourcenotes.org                                 |
  +--------------------------------------------------------------------------+
  | This source file is subject the MIT license, that is bundled with        |
  | this package in the file LICENSE, and is available through the           |
  | world-wide-web at the following url:                                     |
  | https://opensource.org/licenses/MIT                                      |
  +--------------------------------------------------------------------------+
 */

namespace ZEPtoPHP\Stages;

use ZEPtoPHP\Base\Stage as IStage;

/**
 * Normalizes the IR Ast to make for easier parsing
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class EmitCode implements IStage {

  const VERSION = '20151126';
  // How to Handle Empty Statement Blocks?
  const EMPTY_NOTHING = 0; // Display Nothing
  const EMPTY_BLOCK = 1; // Display Empty Block
  const EMPTY_COMMA = 2; // Display Empty Statement (';')

  use \ZEPtoPHP\Base\Mixins\DI;

  // TODO: Maybe Create a Compile Context Object so as to simplify handlers
  // Processing a Right Expression (i.e. for variables this means dropping the '$');
  protected $_property = false;
  // Set Interface Mode (i.e. when emiting methods, emit only a method declaration
  protected $_interface = false;
  /* Special Handling when we are processing a new as variable
   * example: fcall.zep
   * 	return new Fcall()->testStrtokVarBySlash(str);
   */
  protected $_variable = false;
  // Current Emitter
  protected $_emitter = null;

  /**
   * Initialize the Stage Instance
   * 
   * @return self Return instance of stage for Function Linking.
   */
  public function initialize() {
    $this->_emitter = $this->getDI()->getShared('emitter');
    $this->_emitter->initialize();
    return $this;
  }

  /**
   * Reset the Stage Instance (set the default state, if a stage is to
   * be re-used)
   * 
   * @return self Return instance of stage for Function Linking.
   */
  public function reset() {
    $this->_property = false;
    $this->_interface = false;
    $this->_variable = false;
  }

  /**
   * Compile or Transform the AST.
   * 
   * @param array $ast AST to be compiled/transformed
   * @return array Old or Transformed AST
   */
  public function compile($ast) {
    /* FILE HEADER */
    $this->_emitter
      ->emit(['<?php', '// EMITTER VERSION [' . self::VERSION . ']'])
      ->flush();

    /* FILE BODY */
    $this->_processStatementBlock($ast);

    /* FILE FOOTER */
    $this->_emitter
      ->emit("?>")
      ->flush();
    return $ast;
  }

  protected function _processStatementBlock($block, $class = null, $method = null) {
    // Process Statement Block
    foreach ($block as $statement) {
      // Process Current Statement
      $this->_processStatement($statement, $class, $method);
    }
  }

  protected function _processStatement($statement, $class = null, $method = null) {
    $type = $statement['type'];

    // Do we have Specific Handler?
    $handler = $this->_handlerName("_statement", ucfirst($type));
    if (method_exists($this, $handler)) { // YES: Use it!
      return $this->$handler($statement, $class, $method);
    } else { // NO: Try Default
      $handler = '_statementDEFAULT';
    }

    // Do we have a Default Handler?
    if (method_exists($this, $handler)) { // YES: Use it!
      return $this->$handler($statement, $class, $method);
    } else { // NO: Aborts
      throw new \Exception("Unhandled statement type [{$type}] in line [{$statement['line']}]");
    }
  }

  protected function _processExpression($expression, $class = null, $method = null) {
    // Does the Expression Require a Cast?
    if (isset($expression['do-cast']) && $expression['do-cast']) { // YES
      $this->_emitCast($expression);
    }

    $type = $expression['type'];

    // Do we have Specific Handler?
    $handler = $this->_handlerName("_expression", ucfirst($type));
    if (method_exists($this, $handler)) { // YES: Use it!
      return $this->$handler($expression, $class, $method);
    } else { // NO: Try Default
      $handler = '_expressionDEFAULT';
    }

    // Do we have a Default Handler?
    if (method_exists($this, $handler)) { // YES: Use it!
      return $this->$handler($expression, $class, $method);
    } else { // NO: Aborts
      throw new \Exception("Unhandled expression type [{$type}] in line [{$expression['line']}]");
    }
  }

  protected function _classSectionConstants($class, $constants) {
    // Do we have constants to output?
    if (isset($constants) && is_array($constants)) { // YES
      // TODO: Move to the Flag to Configuration File
      $config_sortConstants = true; // Sort Class or Interface Constants?
      if ($config_sortConstants) {
        ksort($constants);
      }

      /* const CONSTANT = 'constant value'; */
      foreach ($constants as $name => $constant) {
        $this->_emitter
          ->emit_keywords('const')
          ->emit($name)
          ->emit_operator('=');
        $this->_processExpression($constant['default'], $class);
        $this->_emitter->emit_eos();
      }
    }
  }

  protected function _classSectionProperties($class, $properties) {
    // Do we have properties to output?
    if (isset($properties) && is_array($properties)) { // YES
      // TODO: Move to the Flag to Configuration File
      $config_sortProperties = true; // Sort Class or Interface Properties?
      if ($config_sortProperties) {
        ksort($properties);
      }

      foreach ($properties as $name => $property) {
        if (isset($property['visibility'])) {
          $this->_emitter->emit_keywords($property['visibility']);
        }
        $this->_emitter->emit("\${$name}");
        if (isset($property['default'])) {
          $this->_emitter->emit_operator('=');
          $this->_processExpression($property['default'], $class);
        }
        $this->_emitter->emit_eos();
      }
    }
  }

  protected function _classSectionMethods($class, $methods) {
    // Do we have properties to output?
    if (isset($methods) && is_array($methods)) { // YES
      // TODO: Move to the Flag to Configuration File
      $config_sortMethods = true; // Sort Class or Interface Methods?
      if ($config_sortMethods) {
        ksort($methods);
      }

      foreach ($methods as $name => $method) {
        // Process Class Metho
        $this->_statementMethod($class, $name, $method);
      }
    }
  }

  protected function _statementNamespace($namespace, $class, $method) {
    $this->_emitter
      ->emit_keyword('namespace')
      ->emit($namespace['name'])
      ->emit_eos('namespace');
  }

  protected function _statementUse($use, $class = null, $method = null) {
    // Beginning of Statement
    $this->_emitter
      ->push_indent()
      ->emit_keyword('use');

    // USE list
    $first = true;
    $indent = true; // Should we indent (more)?
    foreach ($use['aliases'] as $alias) {
      if (!$first) {
        $this->_emitter
          ->emit_operator(',', 'use')
          ->indent($indent);
        $indent = false;
      }
      $this->_emitter->emit($alias['name']);
      if (isset($alias['alias'])) {
        $this->_emitter
          ->emit_keyword('as')
          ->emit($alias['alias']);
      }
      $first = false;
    }

    // End of Statement
    $this->_emitter
      ->emit_eos('use')
      ->pop_indent();
  }

  protected function _statementRequire($require, $class = null, $method = null) {
    /* TODO
     * Normalize the Require Statement and Expression so that we don't have to
     * 2 handlers for the same thing.
     */
    $this->_emitter->emit_keyword('require');
    $this->_processExpression($require['expr'], $class, $method);
    $this->_emitter->emit_eos('require');
  }

  protected function _statementClass($class) {
    $settings = $this->getDI()['settings'];

    // Emit Class Documentation
    if (isset($class['docblock'])) {
      $this->_emitter
        ->emit_nl(!!$settings['newlines.comment|before'])
        ->emit('/*', true)
        ->emit($class['docblock'], true)
        ->emit('*/', true)
        ->emit_nl(!!$settings['newlines.comment|after']);
    }

    /* ------------
     * CLASS HEADER
     * ------------ */
    $this->_emitter
      ->emit_nl()
      ->push_indent(); // Save Current Indent Level
    if ($class['final']) {
      $this->_emitter->emit_keywords('final');
    } else if ($class['abstract']) {
      $this->_emitter->emit_keywords('abstract');
    }
    $this->_emitter
      ->emit_keywords('class')
      ->emit($class['name']);

    // Handle Extends
    $extends = isset($class['extends']) ? $class['extends'] : null;
    if (isset($extends)) {
      $this->_emitter
        ->emit_nl(!!$settings['newline.class.extends|before'])
        ->indent()
        ->emit_keywords('extends')
        ->emit($extends)
        ->unindent()
        ->emit_nl(!!$settings['newlines.class.extends|after']);
    }

    // Handle Implements
    $implements = isset($class['implements']) ? $class['implements'] : null;
    if (isset($implements)) {
      $this->_emitter
        ->emit_nl(!!$settings['newlines.class.implements|before'] && isset($extends))
        ->indent()
        ->emit_keywords('implements');

      $first = true;
      foreach ($implements as $interace) {
        if (!$first) {
          $this->_emitter->emit_operator(',', 'class.implements');
        }
        $this->_property = true;
        $this->_expressionVariable($interace, $class, null);
        $first = false;
      }
      $this->_emitter->unindent();
    }

    /* ---------------
     * CLASS BODY
     * --------------- */
    // Opening Brace
    $this->_emitter
      ->emit_operator('{', 'block.class')
      ->flush() // Garauntee that we start statement block on new line
      ->pop_indent()
      ->indent();

    // Emit the Various Sections
    $section_order = ['constants', 'properties', 'methods'];
    $seperate_section = $settings->get('newlines.class|section', false);
    foreach ($section_order as $order) {
      $section = isset($class[$order]) ? $class[$order] : null;
      if (isset($section)) {
        // Make sure we have flushed any pending changes
        $this->_emitter
          ->flush()
          ->emit_nl($seperate_section);
        $handler = $this->_handlerName('_classSection', $order);
        if (method_exists($this, $handler)) {
          $this->$handler($class, $section);
        } else {
          throw new \Exception("Unhandled section type [{$order}]");
        }
      }
    }

    // Closing Brace
    $this->_emitter
      ->flush()// Garauntee that we flush any pending lines
      ->unindent()
      ->emit_operator('}', 'block.class')
      ->flush();
  }

  protected function _statementInterface($interface) {
    $settings = $this->getDI()['settings'];

    // Emit Class Documentation
    if (isset($class['docblock'])) {
      $this->_emitter
        ->emit_nl(!!$settings['newlines.comment|before'])
        ->emit('/*', true)
        ->emit($class['docblock'], true)
        ->emit('*/', true)
        ->emit_nl(!!$settings['newlines.comment|after']);
    }

    /* ----------------
     * INTERFACE HEADER
     * ---------------- */
    $this->_emitter
      ->emit_nl()
      ->push_indent() // Save Current Indent Level
      ->emit_keywords('interface')
      ->emit($interface['name']);

    $extends = isset($interface['extends']) ? $interface['extends'] : null;
    if (isset($extends)) {
      $this->_emitter
        ->emit_nl(!!$settings['newlines.interface.extends|before'])
        ->indent()
        ->emit_keywords('extends');

      $first = true;
      foreach ($extends as $extend) {
        if (!$first) {
          $this->_emitter->emit_operator(',', 'interface.extends');
        }
        $this->_property = true;
        $this->_processExpression($extend);
        $first = false;
      }
    }

    /* --------------
     * INTERFACE BODY
     * -------------- */
    // Opening Brace
    $this->_emitter
      ->emit_operator('{', 'block.interface')
      ->flush() // Garauntee that we start statement block on new line
      ->pop_indent()
      ->indent();

    // Emit the Various Sections
    $section_order = ['constants', 'properties', 'methods'];
    $seperate_section = $settings->get('newlines.interface|section', false);
    foreach ($section_order as $order) {
      $section = isset($interface[$order]) ? $interface[$order] : null;
      if (isset($section)) {
        // Make sure we have flushed any pending changes
        $this->_emitter
          ->flush()
          ->emit_nl($seperate_section);

        $this->_interface = true;
        $handler = $this->_handlerName('_classSection', $order);
        if (method_exists($this, $handler)) {
          $this->$handler($interface, $section);
        } else {
          throw new \Exception("Unhandled section type [{$order}]");
        }
        $this->_interface = false;
      }
    }

    // Closing Brace
    $this->_emitter
      ->flush()// Garauntee that we flush any pending lines
      ->unindent()
      ->emit_operator('}', 'block.interface')
      ->flush();
  }

  protected function _emitComment($ast) {
    /* TODO:
     * Comment Modifiers Types
     * Simple Comment start with '* '
     * PHPDoc Comment starts with '**'
     * Extra Carriage Returns
     * - Before namespace       - simple YES phpdoc YES
     * - Before class           - simple  NO phpdoc NO
     * - Before Another Comment - simple  NO phpdoc NO
     */
    echo "/{$ast['value']}/\n";
  }

  protected function _statementMethod($class, $name, $method) {
    $settings = $this->getDI()['settings'];

    // Emit Method Documentation
    if (isset($method['docblock'])) {
      $this->_emitter
        ->emit_nl($settings['newlines.comment|before'])
        ->emit('/*', true)
        ->emit($method['docblock'], true)
        ->emit('*/', true)
        ->emit_nl($settings['newlines.comment|after']);
    }

    /* -------------
     * METHOD HEADER
     * ------------- */
    // Method Visibility
    if (isset($method['visibility'])) {
      $this->_emitter->emit_keywords($method['visibility']);
    }

    // Method Name
    $this->_emitter
      ->emit_keywords('function')
      ->emit($method['name']);

    // Method Parameters
    $this->_emitFunctionParameters($method['parameters'], 'method', $class, $method);

    /* -----------
     * METHOD BODY
     * ----------- */
    //Are we in interface mode?
    if (!$this->_interface) { // NO: Class Mode
      $this->_emitStatementBlock($method['statements'], 'method', self::EMPTY_BLOCK, $class, $method);
    } else { // YES
      $this->_emitter->emit_eos('method');
    }
  }

  protected function _statementFunction($function, $class = null, $method = null) {
    $settings = $this->getDI()['settings'];

    // Emit Method Documentation
    if (isset($function['docblock'])) {
      $this->_emitter
        ->emit_nl($settings['newlines.comment|before'])
        ->emit('/*', true)
        ->emit($function['docblock'], true)
        ->emit('*/', true)
        ->emit_nl($settings['newlines.comment|after']);
    }

    /* ---------------
     * FUNCTION HEADER
     * --------------- */
    // Function Name
    $this->_emitter
      ->emit_keywords('function')
      ->emit($function['name']);

    // Function Parameters
    $this->_emitFunctionParameters($function['parameters'], 'function');

    /* ---------------
     * FUNCTION BODY
     * --------------- */
    $this->_emitStatementBlock($function['statements'], 'function', self::EMPTY_BLOCK);
  }

  protected function _statementMcall($call, $class, $method) {
    /* STATEMENT Method Calls Have a Nested AST Structure, which diferentiate
     * it from EXPRESSION Method Calls (i.e. all statements, have expressions
     * and an expression can be used as part of statements)
      [ 'type' => 'mcall',
      'expr' => [
      'type' => 'mcall'
     */

    $this->_expressionMcall($call['expr'], $class, $method);
    $this->_emitter->emit_eos('call.method');
  }

  protected function _statementFcall($call, $class, $method) {
    /* STATEMENT Function Calls Have a Nested AST Structure, which diferentiate
     * it from EXPRESSION Method Calls (i.e. all statements, have expressions
     * and an expression can be used as part of statements)
      [ 'type' => 'fcall',
      'expr' => [
      'type' => 'fcall'
     */

    $this->_expressionFcall($call['expr'], $class, $method);
    $this->_emitter->emit_eos('call.funtion');
  }

  protected function _statementScall($call, $class, $method) {
    /* STATEMENT Function Calls Have a Nested AST Structure, which diferentiate
     * it from EXPRESSION Method Calls (i.e. all statements, have expressions
     * and an expression can be used as part of statements)
      [ 'type' => 'fcall',
      'expr' => [
      'type' => 'fcall'
     */

    $this->_expressionScall($call['expr'], $class, $method);
    $this->_emitter->emit_eos();
  }

  protected function _statementIncr($assign, $class, $method) {
    switch ($assign['assign-to-type']) {
      case 'variable':
        $this->_emitter
          ->emit("\${$assign['variable']}");
        break;
      case 'object-property':
        $this->_emitter
          ->emit("\${$assign['variable']}")
          ->emit_operator('->')
          ->emit($assign['property']);
        break;
      default:
        throw new \Exception("Unhandled Increment Type [{$assign['assign-to-type']}] in line [{$assign['line']}]");
    }

    $this->_emitter->emit_operator('++');
    $this->_emitter->emit_eos();
  }

  protected function _statementDecr($assign, $class, $method) {
    switch ($assign['assign-to-type']) {
      case 'variable':
        $this->_emitter
          ->emit("\${$assign['variable']}");
        break;
      case 'object-property':
        $this->_emitter
          ->emit("\${$assign['variable']}")
          ->emit_operator('->')
          ->emit($assign['property']);
        break;
      default:
        throw new \Exception("Unhandled Increment Type [{$assign['assign-to-type']}] in line [{$assign['line']}]");
    }

    $this->_emitter->emit_operator('--');
    $this->_emitter->emit_eos();
  }

  protected function _statementAssign($assign, $class, $method) {
    // PROCESS TO Expression
    switch ($assign['assign-to-type']) {
      case 'variable':
        $this->_emitter
          ->emit("\${$assign['variable']}");
        break;
      case 'dynamic-variable':
        $this->_emitter
          ->emit("\$\${$assign['variable']}");
        break;
      case 'variable-append':
        $this->_emitter
          ->emit("\${$assign['variable']}")
          ->emit_operators(['[', ']'], 'array.append');
        break;
      case 'array-index':
        $this->_emitter
          ->emit("\${$assign['variable']}");
        $this->_statementAssignArrayIndex($assign['index-expr'], $class, $method);
        break;
      case 'array-index-append':
        $this->_emitter->emit("\${$assign['variable']}");
        $this->_statementAssignArrayIndex($assign['index-expr'], $class, $method);
        $this->_emitter->emit_operators(['[', ']'], 'array.append');
        break;
      case 'object-property':
        $this->_emitObjectPropery($assign['variable'], $assign['property']);
        break;
      case 'object-property-append':
        $this
          ->_emitObjectPropery($assign['variable'], $assign['property'])
          ->emit_operators(['[', ']'], 'array.append');
        break;
      case 'object-property-array-index':
        $this->_emitObjectPropery($assign['variable'], $assign['property']);
        $this->_statementAssignArrayIndex($assign['index-expr'], $class, $method);
        break;
      case 'object-property-array-index-append':
        $this->_emitObjectPropery($assign['variable'], $assign['property']);
        $this->_statementAssignArrayIndex($assign['index-expr'], $class, $method);
        $this->_emitter->emit_operators(['[', ']'], 'array.append');
        break;
      case 'variable-dynamic-object-property':
        $this->_emitObjectPropery($assign['variable'], $assign['property'], true);
        break;
      case 'static-property':
        $this->_emitObjectPropery($assign['variable'], $assign['property'], false, true);
        break;
      case 'static-property-append':
        $this
          ->_emitObjectPropery($assign['variable'], $assign['property'], false, true)
          ->emit_operators(['[', ']'], 'array.append');
        break;
      case 'static-property-array-index':
        $this->_emitObjectPropery($assign['variable'], $assign['property'], false, true);
        $this->_statementAssignArrayIndex($assign['index-expr'], $class, $method);
        break;
      case 'static-property-array-index-append':
        $this->_emitObjectPropery($assign['variable'], $assign['property'], false, true);
        $this->_statementAssignArrayIndex($assign['index-expr'], $class, $method);
        $this->_emitter->emit_operators(['[', ']'], 'array.append');
        break;
      default:
        throw new \Exception("Unhandled Assignment Type [{$assign['assign-type']}] in line [{$assign['line']}]");
    }

    // PROCESS ASSIGNMENT OPERATOR
    switch ($assign['operator']) {
      case 'assign':
        $this->_emitter->emit_operator('=');
        break;
      case 'add-assign':
        $this->_emitter->emit_operator('+=');
        break;
      case 'sub-assign':
        $this->_emitter->emit_operator('-=');
        break;
      case 'mul-assign':
        $this->_emitter->emit_operator('*=');
        break;
      case 'div-assign':
        $this->_emitter->emit_operator('/=');
        break;
      case "concat-assign":
        $this->_emitter->emit_operator('.=');
        break;
      case 'mod-assign':
        $this->_emitter->emit_operator('%=');
        break;
      default:
        throw new \Exception("Unhandled assignment operator  [{$assign['operator']}] in line [{$assign['line']}]");
    }

    // PROCESS R.H.S Expression
    $this->_processExpression($assign['expr'], $class, $method);
    $this->_emitter->emit_eos();
  }

  protected function _statementAssignArrayIndex($indices, $class, $method) {
    $this->_emitter->emit_operator('[', 'array.index');
    $first = true;
    foreach ($indices as $index) {
      if (!$first) {
        $this->_emitter->emit_operators([']', '['], 'array.index');
      }
      $this->_processExpression($index, $class, $method);
      $first = false;
    }
    $this->_emitter->emit_operator(']', 'array.index');
  }

  protected function _statementFor($for, $class, $method) {
    $over_string = $for['basic-for'];
    if ($over_string) {
      $this->_emitFor($for, $class, $method);
    } else {
      $this->_emitForEach($for, $class, $method);
    }
  }

  protected function _statementWhile($while, $class, $method) {
    /* WHILE HEADER */
    $this->_emitter
      ->emit_keyword('while')
      ->emit_operator('(', 'while');
    $this->_processExpression($while['expr'], $class, $method);
    $this->_emitter->emit_operator(')', 'while');

    /*  WHILE BODY */
    $this->_emitStatementBlock($while['statements'], 'while', self::EMPTY_COMMA, $class, $method);
  }

  protected function _statementLoop($loop, $class = null, $method = null) {
    /* DO-WHILE HEADER */
    $this->_emitter->emit_keyword('do');

    /* DO-WHILE BODY */
    $this->_emitStatementBlock($loop['statements'], 'do', self::EMPTY_BLOCK, $class, $method);

    /* DO-WHILE FOOTER */
    $this->_emitter
      ->emit_keyword('while')
      ->emit_operator('(', 'do')
      ->emit_keyword('TRUE')
      ->emit_operator(')', 'do')
      ->emit_eos('do');
  }

  protected function _statementDoWhile($dowhile, $class = null, $method = null) {
    /* DO-WHILE HEADER */
    $this->_emitter->emit_keyword('do');

    /* DO-WHILE BODY */
    $this->_emitStatementBlock($dowhile['statements'], 'do', self::EMPTY_BLOCK, $class, $method);

    /* DO-WHILE FOOTER */
    $this->_emitter
      ->emit_keyword('while')
      ->emit_operator('(', 'do');
    $this->_processExpression($dowhile['expr'], $class, $method);
    $this->_emitter
      ->emit_operator(')', 'do')
      ->emit_eos('do');
  }

  protected function _statementIf($if, $class = null, $method = null) {
    $has_else = isset($if['else_statements']) && count($if['else_statements']);
    $has_else_if = isset($if['elseif_statements']) && count($if['elseif_statements']);

    /* IF (EXPR) */
    $this->_emitIfStatement($if, ($has_else || $has_else_if) ? 'else.if' : 'if', $class, $method);

    /* ELSE IF { statements } */
    if (isset($if['elseif_statements'])) {
      $count = count($if['elseif_statements']);
      $context = 'else.if';
      for ($i = 0; $i < $count; $i++) {
        if ($i === ($count - 1)) {
          $context = $has_else ? 'else' : 'if';
        }
        $else_if = $if['elseif_statements'][$i];
        $this->_emitter->emit_keyword('else');
        $this->_emitIfStatement($else_if, $context, $class, $method);
      }
    }

    /* ELSE { statements } */
    if (isset($if['else_statements'])) {
      $this->_emitter->emit_keyword('else');
      $this->_emitStatementBlock($if['else_statements'], "else", self::EMPTY_COMMA, $class, $method);
    }
  }

  protected function _statementSwitch($switch, $class = null, $method = null) {
    /* -------------
     * SWITCH HEADER
     * ------------- */
    $this->_emitter
      ->emit_keyword('switch')
      ->emit_operator('(', 'switch');
    $this->_processExpression($switch['expr'], $class, $method);
    $this->_emitter->emit_operator(')', 'switch');

    /* -----------
     * SWITCH BODY
     * ----------- */
    // Open SWITCH Block
    $this->_emitter
      ->emit_operator('{', 'block.switch')
      ->push_indent()
      ->indent();

    // BODY : SWITCH CLAUSES
    /* TODO 
     * 1. Optimization, if an 'switch' has no clauses we should just use a ';' rather than a '{ }' pair
     * 2. Optimization, if an 'switch' has no clauses, than maybe it is 'dead code' and should be removed
     * NOTE: this requires that the test expression has no side-effects (i.e. assigning within an if, function call, etc.)
     */
    if (isset($switch['clauses'])) {
      foreach ($switch['clauses'] as $clause) {
        switch ($clause['type']) {
          case 'case':
            $this->_emitter->emit_keyword('case');
            $this->_processExpression($clause['expr'], $class, $method);
            $this->_emitter->emit_operator(':', 'case');
            break;
          case 'default':
            $this->_emitter
              ->emit_keyword('default')
              ->emit_operator(':', 'case');
            break;
          default:
            throw new \Exception("Unexpected SWITCH Clause Type [{$clause['type']}] in line [{$assign['line']}]");
        }

        // Output Statement Blocks
        $this->_emitStatementBlock($clause['statements'], 'case', self::EMPTY_NOTHING, $class, $method);
      }
    }

    // Close SWITCH Block
    $this->_emitter
      ->pop_indent()
      ->emit_operator('}', 'block.switch');
  }

  protected function _statementTryCatch($trycatch, $class = null, $method = null) {
    // Code Emitter
    $emitter = $this->_emitter;

    /* TRY HEADER */
    $emitter->emit_keyword('try');

    /* TRY BODY */
    $this->_emitStatementBlock($trycatch['statements'], 'try', self::EMPTY_BLOCK, $class, $method);

    // BODY : CATCH CLAUSES
    foreach ($trycatch['catches'] as $catch) {
      /* CATCH HEADER */
      $emitter
        ->emit_keyword('catch')
        ->emit_operator('(', 'catch');
      $this->_property = true;
      $this->_processExpression($catch['class'], $class, $method);
      $emitter->emit_space();
      $this->_processExpression($catch['variable'], $class, $method);
      $emitter->emit_operator(')', 'catch');

      /* CATCH BODY */
      $this->_emitStatementBlock($catch['statements'], 'catch', self::EMPTY_BLOCK, $class, $method);
    }
  }

  protected function _statementContinue($continue, $class = null, $method = null) {
    $this->_emitter
      ->emit_keyword('continue')
      ->emit_eos('continue');
  }

  protected function _statementBreak($break, $class = null, $method = null) {
    $this->_emitter
      ->emit_keyword('break')
      ->emit_eos('break');
  }

  protected function _statementReturn($return, $class = null, $method = null) {
    $this->_emitter->emit_keyword('return');
    // Are we dealing with an empty return (i.e. return;)?
    if (isset($return['expr'])) { // NO
      $this->_processExpression($return['expr'], $class, $method);
    }
    $this->_emitter->emit_eos('return');
  }

  protected function _statementThrow($throw, $class = null, $method = null) {
    $this->_emitter->emit_keyword('throw');
    $this->_processExpression($throw['expr'], $class, $method);
    $this->_emitter->emit_eos('throw');
  }

  protected function _statementUnset($unset, $class = null, $method = null) {
    $this->_emitter
      ->emit_keyword('unset')
      ->emit_operator('(', 'unset');
    $this->_processExpression($unset['expr'], $class, $method);
    $this->_emitter
      ->emit_operator(')', 'unset')
      ->emit_eos('unset');
  }

  protected function _statementEcho($echo, $class = null, $method = null) {
    $this->_emitter->emit_keyword('echo');
    $first = true;
    foreach ($echo['expressions'] as $expression) {
      if (!$first) {
        $this->_emitter->emit('.');
      }
      $this->_processExpression($expression, $class, $method);
      $first = false;
    }
    $this->_emitter->emit_eos('echo');
  }

  /**
   * Class Static Method Call
   * 
   * @param type $ast
   */
  protected function _expressionScall($call, $class = null, $method = null) {
    // Method Name
    // Take Into Account if Class Name is By Name or By Reference
    $classname = isset($call['dynamic-class']) && $call['dynamic-class'] ? "\$${call['class']}" : $call['class'];
    // Take Into Account if Method Name is By Name or By Reference
    $methodname = isset($call['dynamic']) && $call['dynamic'] ? "\$${call['name']}" : $call['name'];
    $this->_emitter
      ->emit($classname)
      ->emit_operator('::')
      ->emit($methodname);

    // Method Call Parameters
    $this->_emitCallParameters($call['parameters'], null, $class, $method);
  }

  /**
   * 
   * @param type $call
   * @param type $class
   * @param type $method
   */
  protected function _expressionMcall($call, $class = null, $method = null) {
    /* TODO: Handle Special Case:
     * example: fcall.zep - return new Fcall()->testStrtokVarBySlash(str);
     * PHP doesn't allow this type of linking so we have 2 options
     * 1. wrap the new Fcall() in parentesis (this is the current solution).
     * result: return ( new Fcall () )->testStrtokVarBySlash($str);
     * 
     * 2. create a local variable to accept the result of the new Fcall() and then
     * use that variable
     * result: 
     * $__t_o_1 = new Fcall ();
     * return $__t_o_1->testStrtokVarBySlash($str);
     * 
     * Solution 1 can be done directly in the emitter. 
     * Solution 2 requires that we modify the AST (i.e. has to be done in the
     * normalization phase, and might produce slower code)
     * 
     * Study Solution...
     */

    // Method Name
    $this->_variable = true;
    $this->_processExpression($call['variable']);
    $this->_variable = false;
    if ($call['call-type'] === 2) {
      $this->_emitter
        ->emit_operator('->')
        ->emit("\${$call['name']}");
    } else {
      $this->_emitter
        ->emit_operator('->')
        ->emit($call['name']);
    }

    // Method Call Parameters
    $this->_emitCallParameters($call['parameters'], null, $class, $method);
  }

  /**
   * 
   * @param type $call
   * @param type $class
   * @param type $method
   */
  protected function _expressionFcall($call, $class = null, $method = null) {
    // Function Name
    $this->_emitter->emit($call['name']);

    // Function Call Parameters
    $this->_emitCallParameters($call['parameters'], null, $class, $method);
  }

  protected function _expressionClone($clone, $class, $method) {
    $this->_emitter->emit_keyword('clone');
    $this->_processExpression($clone['left'], $class, $method);
  }

  protected function _expressionNew($new, $class, $method) {
    // Is the new Being Treated as a Variable
    if (isset($this->_variable)) { // YES
      $this->_emitter->emit_operator('(', 'call.wrapper');
    }

    // Is the 'class' given as actual name?
    if (isset($new['dynamic']) && $new['dynamic']) { // NO: It's provided a variable value
      $this->_emitter->emit(['new', "\${$new['class']}"]);
    } else { // YES
      $this->_emitter->emit(['new', $new['class']]);
    }

    if (isset($new['parameters'])) {
      $this->_emitCallParameters($new['parameters'], null, $class, $method);
    }

    // Is the new being treated as a variable?
    if (isset($this->_variable)) { // YES
      $this->_emitter->emit_operator(')', 'call.wrapper');
    }
  }

  protected function _expressionIsset($isset, $class, $method) {
    $left = $isset['left'];
    $this->_emitter
      ->emit_keyword('isset')
      ->emit_operator('(', 'call.isset');
    switch ($left['type']) {
      case 'static-property-access':
        // TODO Verify if this is what zephir does for static access
        $this->_property = true;
        $this->_processExpression($left['left'], $class, $method);
        $this->_emitter->emit_operator('::');
        $this->_processExpression($left['right'], $class, $method);
        break;
      case 'variable':
        $this->_processExpression($left, $class, $method);
        break;
      default:
        throw new \Exception("TODO - 2 - isset([{$type}]) in line [{$isset['line']}]");
    }
    $this->_emitter->emit_operator(')', 'call.isset');
  }

  protected function _expressionTypeof($typeof, $class, $method) {
    // TODO: Transfer this to InlineNormalize (where it makes more sense to due the conversion to a function call)
    $this->_emitter
      ->emit_keyword('zephir_typeof')
      ->emit_operator('(', 'call.function');
    $this->_processExpression($typeof['left'], $class, $method);
    $this->_emitter->emit_operator(')', 'call.function');
  }

  protected function _expressionParameter($parameter, $class, $method) {
    $this->_emitter->emit("\${$parameter['name']}");
    if (isset($parameter['default'])) {
      $this->_emitter->emit_operator('=');
      $this->_processExpression($parameter['default'], $class);
    }
  }

  protected function _expressionArrayAccess($expression, $class = null, $method = null) {
    $left = $expression['left'];
    $right = $expression['right'];
    $this->_processExpression($left, $class, $method);
    $this->_emitter->emit_operator('[', 'array.index');
    $this->_processExpression($right, $class, $method);
    $this->_emitter->emit_operator(']', 'array.index');
  }

  protected function _expressionPropertyAccess($expression, $class = null, $method = null) {
    $left = $expression['left'];
    $right = $expression['right'];
    $this->_processExpression($left, $class, $method);
    $this->_emitter->emit_operator('->');
    // Flag the Next Expression as Property Expression
    $this->_property = true;
    $this->_processExpression($right, $class, $method);
  }

  protected function _expressionPropertyDynamicAccess($expression, $class = null, $method = null) {
    $left = $expression['left'];
    $right = $expression['right'];
    $this->_processExpression($left, $class, $method);
    $this->_emitter->emit_operator('->');
    // Flag the Next Expression as Property Expression
    $this->_processExpression($right, $class, $method);
  }

  protected function _expressionStaticPropertyAccess($expression, $class = null, $method = null) {
    $left = $expression['left'];
    $right = $expression['right'];
    $this->_property = true;
    $this->_processExpression($left, $class, $method);
    $this->_emitter->emit_operator('::');
    $this->_processExpression($right, $class, $method);
  }

  protected function _expressionStaticConstantAccess($expression, $class = null, $method = null) {
    $left = $expression['left'];
    $right = $expression['right'];
    $this->_property = true;
    $this->_processExpression($left, $class, $method);
    $this->_emitter->emit_operator('::');
    $this->_property = true;
    $this->_processExpression($right, $class, $method);
  }

  protected function _expressionTernary($ternary, $class, $method) {
    /* TODO Add Configuration for Line Feed and Alignment
     * example: expr ? true
     *               : false;
     */
    $this->_processExpression($ternary['left'], $class, $method);
    $this->_emitter->emit_operator('?', 'ternary');
    $this->_processExpression($ternary['right'], $class, $method);
    $this->_emitter->emit_operator(':', 'ternary');
    $this->_processExpression($ternary['extra'], $class, $method);
  }

  protected function _expressionVariable($variable, $class, $method) {
    if ($this->_property) {
      $this->_emitter->emit($variable['value']);
    } else {
      $this->_emitter->emit("\${$variable['value']}");
    }
    $this->_property = false;
  }

  protected function _emitNewType($ast) {
    $type = $ast['internal-type'];
    $parameters = isset($ast['parameters']) ? $ast['parameters'] : null;
    switch ($type) {
      case 'array':
        // TODO : Verify if this is correct handling for zephir
        $this->_emitter->emit_operators(['[', ']'], 'array.empty');
        break;
      case 'string':
        // TODO: See the Actual Implementation to Verify if this is worth it
        $this->_emitter->emit_operators(['\'', '\''], 'string.empty');
        break;
      default:
        throw new \Exception("Function [_emitNewType] - Cannot build instance of type [{$type}]");
    }
  }

  protected function _expressionClosure($closure, $class, $method) {
    /* --------------
     * CLOSURE HEADER
     * -------------- */
    $this->_emitter
      ->push_indent()
      ->emit_keyword('function');
    $this->_emitFunctionParameters($closure['parameters'], 'closure', $class, $method);

    /* ------------
     * CLOSURE BODY
     * ------------ */
    $this
      ->_emitStatementBlock($closure['statements'], 'closure', self::EMPTY_BLOCK)
      ->pop_indent();
  }

  protected function _expressionRequire($require, $class, $method) {
    $this->_emitter->emit_keyword('require ');
    $this->_processExpression($require['left'], $class, $method);
  }

  /*
   * EXPRESSION OPERATORS
   */

  protected function _expressionList($list, $class, $method) {
    $this->_emitter->emit_operator('(');
    $this->_processExpression($list['left'], $class, $method);
    $this->_emitter->emit_operator(')');
  }

  protected function _expressionBitwiseNot($bitwise_not, $class, $method) {
    $this->_emitter->emit_operator('~');
    $this->_processExpression($bitwise_not['left'], $class, $method);
  }

  protected function _expressionMinus($minus, $class, $method) {
    $this->_emitter->emit_operator('-');
    $this->_processExpression($minus['left'], $class, $method);
  }

  protected function _expressionPlus($plus, $class, $method) {
    $this->_emitter->emit_operator('+');
    $this->_processExpression($plus['left'], $class, $method);
  }

  protected function _expressionAdd($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '+', $operation['right'], $class, $method);
  }

  protected function _expressionSub($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '-', $operation['right'], $class, $method);
  }

  protected function _expressionMul($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '*', $operation['right'], $class, $method);
  }

  protected function _expressionDiv($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '/', $operation['right'], $class, $method);
  }

  protected function _expressionMod($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '%', $operation['right'], $class, $method);
  }

  protected function _expressionBitwiseOr($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '|', $operation['right'], $class, $method);
  }

  protected function _expressionBitwiseAnd($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '&', $operation['right'], $class, $method);
  }

  protected function _expressionBitwiseXor($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '^', $operation['right'], $class, $method);
  }

  protected function _expressionBitwiseShiftleft($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '<<', $operation['right'], $class, $method);
  }

  protected function _expressionBitwiseShiftright($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '>>', $operation['right'], $class, $method);
  }

  protected function _expressionConcat($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '.', $operation['right'], $class, $method);
  }

  /*
   * EXPRESSIONS BOOLEAN OPERATORS
   */

  protected function _expressionNot($operation, $class, $method) {
    $this->_emitter->emit_operator('!');
    $this->_processExpression($operation['left'], $class, $method);
  }

  protected function _expressionEquals($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '==', $operation['right'], $class, $method);
  }

  protected function _expressionNotEquals($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '!=', $operation['right'], $class, $method);
  }

  protected function _expressionIdentical($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '===', $operation['right'], $class, $method);
  }

  protected function _expressionNotIdentical($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '!==', $operation['right'], $class, $method);
  }

  protected function _expressionAnd($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '&&', $operation['right'], $class, $method);
  }

  protected function _expressionOr($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '||', $operation['right'], $class, $method);
  }

  protected function _expressionInstanceof($operation, $class, $method) {
    $this->_processExpression($operation['left'], $class, $method);
    $this->_emitter->emit_keyword('instanceof');
    $this->_property = true;
    $this->_processExpression($operation['right'], $class, $method);
  }

  protected function _expressionLess($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '<', $operation['right'], $class, $method);
  }

  protected function _expressionLessEqual($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '<=', $operation['right'], $class, $method);
  }

  protected function _expressionGreater($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '>', $operation['right'], $class, $method);
  }

  protected function _expressionGreaterEqual($operation, $class, $method) {
    $this->_emitOperator($operation['left'], '>=', $operation['right'], $class, $method);
  }

  /*
   * EXPRESSIONS BASIC TYPES
   */

  protected function _expressionDouble($ast, $class = null, $method = null) {
    $this->_emitter->emit($ast['value']);
  }

  protected function _expressionInt($ast, $class = null, $method = null) {
    $this->_emitter->emit($ast['value']);
  }

  protected function _expressionBool($ast, $class = null, $method = null) {
    $this->_emitter->emit_keyword(strtoupper($ast['value']));
  }

  protected function _expressionNull($ast, $class = null, $method = null) {
    $this->_emitter->emit_keyword('NULL');
  }

  protected function _expressionString($ast, $class = null, $method = null) {
    $string = $ast['value'];
    /* IMPLEMENTATION NOTE: Since Zephir does not allow embeding of variables in
     * string.
     */
    $string = str_replace('$', '\\$', $ast['value']);
    $this->_emitter->emit("\"{$string}\"");
  }

  protected function _expressionChar($ast, $class = null, $method = null) {
    $this->_emitter->emit("'{$ast['value']}'");
  }

  protected function _expressionArray($array, $class = null, $method = null) {
    // OPEN ARRAY
    $this->_emitter->emit('[', 'array.value');

    // PROCESS ARRAY ELEMENTS
    $first = true;
    $indent = true;
    foreach ($array['left'] as $entry) {
      if (!$first) {
        $this->_emitter
          ->emit_operator(',', 'array.value')
          ->indent($indent);
        $indent = false;
      }
      $key = isset($entry['key']) ? $entry['key'] : null;
      if (isset($key)) {
        $this->_processExpression($key, $class, $method);
        $this->_emitter->emit_operator('=>');
      }
      $this->_processExpression($entry['value'], $class, $method);
      $first = false;
    }

    // CLOSE ARRAY
    $this->_emitter->emit_operator(']', 'array.value');
  }

  protected function _expressionEmptyArray($array, $class = null, $method = null) {
    $this->_emitter->emit_operators(['[', ']'], 'array.empty');
  }

  protected function _expressionConstant($constant, $class = null, $method = null) {
    $this->_emitter->emit($constant['value']);
  }

  protected function _emitStatementBlock($block, $context, $empty_block, $class = null, $method = null) {
    $emitter = $this->_emitter;
    $case = $context === 'case';
    $context = isset($context) && is_string($context) ? "block.{$context}" : 'block';
    if (isset($block) && count($block)) {
      if (!$case) {
        $emitter->emit_operator('{', $context);
      } else {
        $emitter->flush();
      }

      $emitter->push_indent()
        ->indent();

      $this->_processStatementBlock($block, $class, $method);

      if (!$case) {
        $emitter
          ->pop_indent()
          ->emit_operator('}', $context);
      } else {
        $emitter
          ->flush() // Flush any Pending Lines
          ->pop_indent();
      }
    } else {
      // Are we Dealing with a Case Block?
      if ($context === 'case') { // YES: Just Flush the Line
        $this->_emitter->flush();
      }

      switch ($empty_block) {
        case self::EMPTY_NOTHING:
          break;
        case self::EMPTY_COMMA:
          $this->_emitter->emit_eos('empty');
          break;
        case self::EMPTY_BLOCK:
          $this->_emitter->emit_operators(['{', '}'], 'block.empty');
          break;
        default:
          throw new \Exception("System Error: Invalid Parameter for Handling Empty Statement Blocks");
      }
    }

    return $emitter;
  }

  protected function _emitIfStatement($if, $context, $class = null, $method = null) {
    /* IF HEADER */
    $this->_emitter
      ->emit_keyword('if')
      ->emit_operator('(', $context);
    $this->_processExpression($if['expr'], $class, $method);
    $this->_emitter->emit_operator(')', $context);

    /* IF { statements } */
    return $this->_emitStatementBlock($if['statements'], $context, self::EMPTY_COMMA, $class, $method);
  }

  protected function _emitFor($for, $class, $method) {
    // Get Index and Count
    $index = $for['key'];
    $length = $for['length'];
    $over = $for['expr'];

    // Create a Value Assignement Statemement ex: $for['value'] = $key
    $value_assign = [
      'type' => 'assign',
      'assign-to-type' => 'variable',
      'operator' => 'assign',
      'variable' => $for['value'],
      'expr' => [
        'type' => 'array-access',
        'left' => [
          'type' => 'variable',
          'value' => $over['value'],
          'file' => $for['file'],
          'line' => $for['line'],
          'char' => $for['char']
        ],
        'right' => [
          'type' => 'variable',
          'value' => $index,
          'file' => $for['file'],
          'line' => $for['line'],
          'char' => $for['char']
        ],
        'file' => $for['file'],
        'line' => $for['line'],
        'char' => $for['char']
      ]
    ];

    /* -----------
     * FOR HEADER
     * ----------- */
    $this->_emitter
      ->emit_keyword('for')
      // Opening Parenthesis
      ->emit_operator('(', 'for')
      // Index Initialization Statement
      ->emit("\${$index}")
      ->emit_operator('=')
      ->emit('0')
      ->emit_operator(';')
      // For Cut Off Statement
      ->emit("\${$index}")
      ->emit_operator('<')
      ->emit("\${$length}")
      ->emit_operator(';')
      // For Increment Statement
      ->emit("\${$index}")
      ->emit_operator('++')
      // Closing Parenthesis
      ->emit_operator(')', 'for');

    /* -----------
     * FOR BODY
     * ----------- */
    // Add Value Assignments
    $statements = $for['statements'];
    array_unshift($statements, $value_assign);

    return $this->_emitStatementBlock($statements, 'for', self::EMPTY_COMMA, $class, $method);
  }

  protected function _emitForEach($for, $class, $method) {
    // TODO Handle 'anonymous variable' i.e. key, _
    // TODO from flow.zep : for _ in range(1, 10) (No Key, No Value)
    $key = isset($for['key']) ? $for['key'] : null;
    $value = $for['value'];

    /* -----------
     * FOR HEADER
     * ----------- */
    $this->_emitter
      ->emit_keyword('foreach')
      // Opening Parenthesis
      ->emit_operator('(', 'for');
    // Source Expression
    $this->_processExpression($for['expr'], $class, $method);
    // As Expression
    $this->_emitter->emit_keyword('as');
    if (isset($key)) {
      $this->_emitter
        ->emit("\${$key}")
        ->emit_operator('=>')
        ->emit("\${$value}");
    } else {
      $this->_emitter->emit("\${$value}");
    }
    // Closing Parenthesis
    $this->_emitter->emit_operator(')', 'for');

    /* -----------
     * FOR BODY
     * ----------- */
    return $this->_emitStatementBlock($for['statements'], 'for', self::EMPTY_COMMA, true, $class, $method);
  }

  protected function _emitFunctionParameters($parameters, $context = null, $class = null, $method = null) {
    $context = isset($context) && is_string($context) ? "parameters.{$context}" : 'parametes.function';
    return $this->_emitList($parameters, $context, $class, $method);
  }

  protected function _emitCallParameters($parameters, $context = null, $class = null, $method = null) {
    $context = isset($context) && is_string($context) ? "call.{$context}" : 'call.parameters';
    return $this->_emitList($parameters, $context, $class, $method);
  }

  protected function _emitList($entries, $context, $class = null, $method = null) {
    // Opening Parenthesis
    $this->_emitter
      ->push_indent()
      ->emit_operator('(', $context);

    // Do we have entries (for the list)?
    if (isset($entries) && count($entries)) { // YES: Emit them
      $first = true;
      $indent = true; // Should we indent (more)?
      foreach ($entries as $entry) {
        if (!$first) {
          $this->_emitter
            ->emit_operator(',', $context)
            ->indent($indent);
          $indent = false;
        }
        $this->_processExpression($entry, $class, $method);
        $first = false;
      }
    }

    // Close Parenthesis
    return $this->_emitter
        ->emit_operator(')', $context)
        ->pop_indent();
  }

  protected function _emitOperator($left, $operator, $right, $class, $method) {
    $this->_processExpression($left, $class, $method);
    $this->_emitter->emit_operator($operator);
    $this->_processExpression($right, $class, $method);
    return $this->_emitter;
  }

  protected function _emitObjectPropery($object, $property, $dynamic = false, $static = false) {
    /*
     * normal: ${object reference} -> {property name}
     * static: (self|parent) :: ${property name}
     * dynamic: ${object reference} -> ${variable containing name of property}
     */
    $operator = $static ? '::' : '->';
    $object = $static ? $object : "\${$object}";
    $property = $static || $dynamic ? "\${$property}" : $property;

    return $this->_emitter
        ->emit($object)
        ->emit_operator($operator)
        ->emit($property);
  }

  protected function _emitCast($expression) {
    return $this->_emitter
        ->emit_operator('(', 'cast')
        ->emit($expression['data-type'])
        ->emit_operator(')', 'cast');
  }

  protected function _handlerName($prefix, $name) {
    $name = implode(
      array_map(function($e) {
        return ucfirst(trim($e));
      }, explode('-', $name))
    );
    $name = implode(
      array_map(function($e) {
        return ucfirst(trim($e));
      }, explode('_', $name))
    );
    return $prefix . $name;
  }

}
