<?php

/*
  +--------------------------------------------------------------------------+
  | ZEP to PHP Translator                                                    |
  +--------------------------------------------------------------------------+
  | Copyright (c) 2015 pf at sourcenotes.org                                 |
  +--------------------------------------------------------------------------+
  | This source file is subject the MIT license, that is bundled with        |
  | this package in the file LICENSE, and is available through the           |
  | world-wide-web at the following url:                                     |
  | https://opensource.org/licenses/MIT                                      |
  +--------------------------------------------------------------------------+
 */

namespace ZEPtoPHP\Phases;

use ZEPtoPHP\Base\Phase as IPhase;

/**
 * Inline Expand Property Shortcuts
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class InlineShortcuts implements IPhase {

  // Mixins
  use \ZEPtoPHP\Base\Mixins\DI;

  /**
   * Process the AST
   * 
   * @param array $ast AST to be processed
   * @return array Old or Transformed AST
   */
  public function top($ast) {
    return $ast;
  }

  /**
   * Process Class or Interface Constant
   * 
   * @param array $class Class Definition
   * @param array $constant Class Constant Definition
   * @return array New Constant Definition, 'NULL' if to be removed
   * @throws \Exception On error Parsing Constant
   */
  public function constant(&$class, $constant) {
    return $constant;
  }

  /**
   * Process Class or Interface Property
   * 
   * @param array $class Class Definition
   * @param array $property Class Property Definition
   * @return array New Property Definition, 'NULL' if to be removed
   * @throws \Exception On error Parsing Property
   */
  public function property(&$class, $property) {
    // Does the Property have Shortcuts Defined?
    $shortcuts = isset($property['shortcuts']) ? $property['shortcuts'] : null;
    if (isset($shortcuts)) { // YES
      $methods = [];

      // Process All of the Properties Shortcuts
      $processed = [];
      foreach ($shortcuts as $shortcut) {
        // Have we already processed the shortcut?
        if (in_array($shortcut['name'], $processed)) { // YES
          throw new \Exception("Shortcut [{$shortcut['name']}] is used multiple times in Property [{$property['name']}].");
        }

        // Create Method for Shortcut
        $method = $this->_expandShortcut($property, $shortcut);
        if (isset($method)) {
          $methods[] = $method;
        }

        // Add Shortcut to List of Processed Shortcuts
        $processed[] = $shortcut['name'];
      }

      // Do we have Shortcuts to Add?
      if (count($methods)) { // YES        
        $classMethods = $this->_getMethodsDefinition($class);
        foreach ($methods as $method) {
          $name = $method['name'];
          if (array_key_exists($name, $classMethods)) {
            $message = "Property [{$property['name']}] Shortcut Method [{$name}] already exists.";
            if (isset($classMethods[$name]['shortcut'])) {
              $message.=" Shortcut used in property [{$classMethods[$name]['shortcut']}]";
            }
            throw new \Exception($message);
          }

          $classMethods[$name] = $method;
        }

        // Update Class
        $class['methods'] = $classMethods;
      }

      unset($property['shortcuts']);
    }

    return $property;
  }

  /**
   * Process Class or Interface Method
   * 
   * @param array $class Class Definition
   * @param array $method Class Method Definition
   * @return array New Property Definition, 'NULL' if to be removed
   */
  public function method(&$class, $method) {
    return $method;
  }

  /**
   * 
   * @param type $class
   * @return array
   */
  protected function _getMethodsDefinition(&$class) {

    if (!isset($class['methods'])) {
      $class['methods'] = [];
    }

    return $class['methods'];
  }

  protected function _expandShortcut($property, $shortcut) {
    // Shortcut Type
    $type = $shortcut['name'];

    // Calculate the Method Name
    $methodName = null;
    switch ($type) {
      case 'toString':
      case '__toString':
        $methodName = '__toString';
        $type = 'toString';
        break;
      case 'get':
      case 'set':
        /* HACK: zephir treats a single leading '_' as ignored (this is
         * probably due to the fact that in phalcon, the coding style used,
         * MARKS protected properties and methods with a leading '_'.
         * The fact that zephir, takes this into consideration, and does not
         * document, is a HACK.
         * ex:
         * protected _default {get};
         * generates a getter with the name getDefault and not get_default.
         */
        $name = rtrim($property['name'], '_');

        /* In order to improve this hack, I would like to extende this so that
         * '_' is used as a word break and therefore would uppercase all the
         * 1st letters in the word
         * 
         * example:
         * _default_name or default_name or ___default_name all generate,
         * getDefaultName
         * 
         * It's consistent with te existing hack but with a little extra.
         */
        $methodName = $type . implode(array_map('ucfirst', explode('_', $name)));
        break;
      default:
        throw new \Exception("Unhandled shortcut type[{$type}] at line [{$shortcut['line']}]");
    }

    // Basic Function Definition
    $method = [
      'visibility' => ['public'],
      'type' => 'method',
      'name' => $methodName,
      'parameters' => [],
      'locals' => [],
      'statements' => [],
      // Add an Extra Property to Mark the Method as Creatd by Shortcut for a Property
      'shortcut' => $property['name']
    ];

    switch ($type) {
      case 'toString':
      case 'get':
        $method['statements'][] = [
          'type' => 'return',
          'expr' => [
            'type' => 'property-access',
            'left' => [
              'type' => 'variable',
              'value' => 'this',
              'file' => $shortcut['file'],
              'line' => $shortcut['line'],
              'char' => $shortcut['char'],
            ],
            'right' => [
              'type' => 'variable',
              'value' => $property['name'],
              'file' => $shortcut['file'],
              'line' => $shortcut['line'],
              'char' => $shortcut['char'],
            ],
            'file' => $shortcut['file'],
            'line' => $shortcut['line'],
            'char' => $shortcut['char'],
          ],
          'file' => $shortcut['file'],
          'line' => $shortcut['line'],
          'char' => $shortcut['char'],
        ];
        break;
      case 'set':
        // Add Parameter to Function
        $pname = "__p_{$property['name']}__";
        $method['parameters'][] = [
          'type' => 'parameter',
          'name' => $pname,
          'const' => 0,
          'data-type' => 'variable',
          'mandatory' => 0,
          'reference' => 0,
          'file' => $shortcut['file'],
          'line' => $shortcut['line'],
          'char' => $shortcut['char'],
        ];

        /* TODO: See if Class Properties Can have Declared Types
         * If so, we need to add a declared type
          'cast' => [
          'type' => 'variable',
          'value' => 'DiInterface',
          'file' => '/home/pj/WEBPROJECTS/zephir/test/router.zep',
          'line' => 107,
          'char' => 55,
          ],
         */
        $method['statements'][] = [
          'type' => 'let',
          'assignments' => [
            [
              'assign-type' => 'object-property',
              'operator' => 'assign',
              'variable' => 'this',
              'property' => $property['name'],
              'expr' => [
                'type' => 'variable',
                'value' => $pname,
                'file' => $shortcut['file'],
                'line' => $shortcut['line'],
                'char' => $shortcut['char'],
              ],
              'file' => $shortcut['file'],
              'line' => $shortcut['line'],
              'char' => $shortcut['char'],
            ],
          ],
          'file' => $shortcut['file'],
          'line' => $shortcut['line'],
          'char' => $shortcut['char'],
        ];
    }

    return $method;
  }

}
