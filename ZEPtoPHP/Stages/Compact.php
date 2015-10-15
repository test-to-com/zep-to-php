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
use ZEPtoPHP\Phases\InlineComments;
use ZEPtoPHP\Phases\InlineShortcuts;

/**
 * Normalizes the IR Ast to make for easier parsing
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class Compact implements IStage {

  // Mixins
  use \ZEPtoPHP\Base\Mixins\DI;

  /**
   * Initialize the Stage Instance
   * 
   * @return self Return instance of stage for Function Linking.
   */
  public function initialize() {
    return $this;
  }

  /**
   * Reset the Stage Instance (set the default state, if a stage is to
   * be re-used)
   * 
   * @return self Return instance of stage for Function Linking.
   */
  public function reset() {
    return $this;
  }

  /**
   * Compile or Transform the AST.
   * 
   * @param array $ast AST to be compiled/transformed
   * @return array Old or Transformed AST
   */
  public function compile($ast) {
    $newAST = [];

    foreach ($ast as $index => $entry) {

      switch ($entry['type']) {
        case 'cblock':
          // TODO: Warn of the Presence of CBLOCKS (for C extension use only)
          $entry = null;
          break;
        case 'function':
          $entry = $this->_compactFunction($entry);
          break;
        case 'class':
          $entry = $this->_compactClass($entry);
          break;
        case 'interface':
          $entry = $this->_compactInterface($entry);
          break;
      }

      if (isset($entry)) {
        // Add Entry to List of Statements
        $newAST[] = $entry;
      }
    }

    return $newAST;
  }

  protected function _compactFunction($function) {
    // Normalize Function Definition - So that we don't always have to test for the existance of
    if (!isset($function['parameters'])) {
      $function['parameters'] = [];
    }
    if (!isset($function['statements'])) {
      $function['statements'] = [];
    }
    $function['locals'] = [];

    // Compact Function Statements
    $function['statements'] = $this->_compactStatementBlock($method, $function['statements']);

    return $function;
  }

  /**
   * Compacts the Class Definition
   * 
   * @param array $ast Comment AST
   * @return array Comment Block Entry or Null, if an empty comment
   */
  protected function _compactClass($class) {

    // Get Class Definition
    $definition = isset($class['definition']) ? $class['definition'] : null;
    if (isset($definition)) {

      // Do we have Class Constants to Process?
      $map = [];
      $constants = isset($definition['constants']) ? $definition['constants'] : null;
      if (isset($constants)) { // YES
        // Convert Constants Array to Constants Map
        foreach ($constants as $constant) {
          $key = $constant['name'];
          if (array_key_exists($key, $map)) {
            throw new \Exception("Duplicate constant [{$key}] definition.");
          }
          $map[$key] = $constant;
        }

        // Remove Constants Definition
        unset($class['definition']['constants']);
      }

      // Create Properties Map
      $class['constants'] = $map;

      // Do we have Class Properties to Process?
      $map = [];
      $properties = isset($definition['properties']) ? $definition['properties'] : null;
      if (isset($properties)) { // YES
        // Convert Properties Array to Properties Map
        foreach ($properties as $property) {
          $key = $property['name'];
          if (array_key_exists($key, $map)) {
            throw new \Exception("Duplicate property [{$key}] definition.");
          }
          $map[$key] = $property;
        }

        // Remove Properties Definition
        unset($class['definition']['properties']);
      }

      // Create Properties Map
      $class['properties'] = $map;

      // Do we have Class Methods to Process?
      $map = [];
      $methods = isset($definition['methods']) ? $definition['methods'] : null;
      if (isset($methods)) { // YES
        // Convert Methods Array to Methods Map
        foreach ($methods as $method) {
          $key = $method['name'];
          if (array_key_exists($key, $map)) {
            throw new \Exception("Duplicate method [{$key}] definition.");
          }

          // Compact Method
          $method = $this->_compactMethod($method);

          // Add Entry to Map
          $map[$key] = $method;
        }

        // Remove Methods Array
        unset($class['definition']['methods']);
      }

      // Create Methods Map
      $class['methods'] = $map;

      // Clear Class Definitions
      unset($class['definition']);
    } else {
      $class['constants'] = [];
      $class['properties'] = [];
      $class['methods'] = [];
    }

    return $class;
  }

  /**
   * Compact Interface Definition
   * 
   * @param array $ast Comment AST
   * @return array Comment Block Entry or Null, if an empty comment
   */
  protected function _compactInterface($interface) {

    // Get Class Definition
    $definition = isset($interface['definition']) ? $interface['definition'] : null;
    if (isset($definition)) {

      // Do we have Class Constants to Process?
      $map = [];
      $constants = isset($definition['constants']) ? $definition['constants'] : null;
      if (isset($constants)) { // YES
        // Convert Constants Array to Constants Map
        foreach ($constants as $constant) {
          $key = $constant['name'];
          if (array_key_exists($key, $map)) {
            throw new \Exception("Duplicate constant [{$key}] definition.");
          }
          $map[$key] = $constant;
        }

        // Remove Constants Definition
        unset($interface['definition']['constants']);
      }

      // Create Properties Map
      $interface['constants'] = $map;

      // Do we have Class Properties to Process?
      $map = [];
      $properties = isset($definition['properties']) ? $definition['properties'] : null;
      if (isset($properties)) { // YES
        // Convert Properties Array to Properties Map
        foreach ($properties as $property) {
          $key = $property['name'];
          if (array_key_exists($key, $map)) {
            throw new \Exception("Duplicate property [{$key}] definition.");
          }
          $map[$key] = $property;
        }

        // Remove Properties Definition
        unset($interface['definition']['properties']);
      }

      // Create Properties Map
      $interface['properties'] = $map;

      // Do we have Class Methods to Process?
      $map = [];
      $methods = isset($definition['methods']) ? $definition['methods'] : null;
      if (isset($methods)) { // YES
        // Convert Methods Array to Methods Map
        foreach ($methods as $method) {
          $key = $method['name'];
          if (array_key_exists($key, $map)) {
            throw new \Exception("Duplicate method [{$key}] definition.");
          }

          // Compact Method
          $method = $this->_compactMethod($method);

          // Add Entry to Map
          $map[$key] = $method;
        }

        // Remove Methods Array
        unset($interface['definition']['methods']);
      }

      // Create Methods Map
      $interface['methods'] = $map;

      // Clear Interface Definitions
      unset($interface['definition']);
    } else {
      $interface['constants'] = [];
      $interface['properties'] = [];
      $interface['methods'] = [];
    }

    return $interface;
  }

  protected function _compactMethod($method) {
    // Normalize Method Definition - So that we don't always have to test for the existance of
    if (!isset($method['parameters'])) {
      $method['parameters'] = [];
    }
    if (!isset($method['statements'])) {
      $method['statements'] = [];
    }
    $method['locals'] = [];

    // Compact Method Statements
    $method['statements'] = $this->_compactStatementBlock($method, $method['statements']);

    return $method;
  }

  protected function _compactStatementBlock(&$method, $block) {
    // Convert Declares into Local Variable Declarations and LET Statements for defaults
    $statements = [];
    foreach ($block as $statement) {
      $type = $statement['type'];

      // Is the Statement a DECLARE?
      if ($type === 'declare') { // YES:
        $statements = array_merge($statements, $this->_compactDeclare($method, $statement));
        continue;
      }

      // For Complex Statements (i.e. statements with statement blocks)
      switch ($statement['type']) {
        case 'cblock':
          // TODO: Warn of the Presence of CBLOCKS (for C extension use only)
          $statement = null;
          break;
        case 'for':
        case 'loop':
        case 'while':
        case 'do-while':
          $statement['statements'] = isset($statement['statements']) ? $this->_compactStatementBlock($method, $statement['statements']) : [];
          /* TODO Handle Trailing comments 
           * i.e. comments that come after all the statements in a block.
           * 
           * example:
           * loop 
           * {
           *   a +=1;
           *   // Trailing Comment
           * }
           * 
           * Currently these comments are dropped
           */
          break;
        case 'if':
          // Process If (TRUE) block
          $statement['statements'] = isset($statement['statements']) ? $this->_compactStatementBlock($method, $statement['statements']) : [];

          // Process If (OTHER CONDITIONS) block
          if (isset($statement['elseif_statements'])) {
            $elseifs = $statement['elseif_statements'];
            foreach ($elseifs as &$elseif) {
              $elseif['statements'] = isset($elseif['statements']) ? $this->_compactStatementBlock($method, $elseif['statements']) : [];
            }
            $statement['elseif_statements'] = $elseifs;
          }
          // Process If (FALSE) block
          if (isset($statement['else_statements'])) {
            $statement['else_statements'] = $this->_compactStatementBlock($method, $statement['else_statements']);
          }
          break;
        case 'switch':
          $clauses = [];
          if (isset($statement['clauses'])) {
            $clauses = $statement['clauses'];
            foreach ($clauses as &$clause) {
              $clause['statements'] = isset($clause['statements']) ? $this->_compactStatementBlock($method, $clause['statements']) : [];
            }
          }
          $statement['clauses'] = $clauses;
          break;
      }

      // Add Statement to List of Statements
      if (isset($statement)) {
        $statements[] = $statement;
      }
    }

    return $statements;
  }

  protected function _compactDeclare(&$method, $statement) {
    $lets = [];
    $data_type = $statement['data-type'];
    foreach ($statement['variables'] as $variable) {
      // Normalize Declaration
      $variable['name'] = $variable['variable'];
      $variable['data-type'] = $data_type;
      unset($variable['variable']);

      // Does the variable have an initial value set?
      $let = $this->_createLetFromDeclare($variable);
      if (isset($let)) { // YES: Convert to Assignement
        $lets[] = $let;
        unset($variable['expr']);
      }

      // Add Declaration to Method Locals List
      $method['locals'][$variable['name']] = $variable;
    }

    return $lets;
  }

  protected function _createLetFromDeclare($variable) {
    if (isset($variable['expr'])) {
      return [
        'type' => 'let',
        'assignments' => [
          [
            'assign-type' => 'variable',
            'operator' => 'assign',
            'variable' => $variable['name'],
            'expr' => $variable['expr'],
            'file' => $variable['file'],
            'line' => $variable['line'],
            'char' => $variable['char'],
          ]
        ],
        'file' => $variable['file'],
        'line' => $variable['line'],
        'char' => $variable['char'],
      ];
    }

    return null;
  }

}
