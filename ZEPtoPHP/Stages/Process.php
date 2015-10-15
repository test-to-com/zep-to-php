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
use ZEPtoPHP\Phases\InlineNormalize;

/**
 * Normalizes the IR Ast to make for easier parsing
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class Process implements IStage {

  // Mixins
  use \ZEPtoPHP\Base\Mixins\DI;

  protected $_comments = [];
  protected $_phases = [];

  /**
   * 
   */
  public function __destruct() {
    // TODO HOW 
    $this->_comments = null;
    $this->_phases = null;
  }

  /**
   * Initialize the Stage Instance
   * 
   * @return self Return instance of stage for Function Linking.
   */
  public function initialize() {
    // Create Phases
    $this->_phases[] = new InlineComments();
    $this->_phases[] = new InlineShortcuts();
    $this->_phases[] = new InlineNormalize();

    // Initialize Phases
    $di = $this->getDI();
    foreach ($this->_phases as $phase) {
      $phase->setDI($di);
    }

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

      // Process Top Level Statements
      foreach ($this->_phases as $phase) {
        $entry = $phase->top($entry);

        // Is the entry to be processed further?
        if (!isset($entry)) { // NO:
          break;
        }
      }

      if (isset($entry)) {
        switch ($entry['type']) {
          case 'class':
            $entry = $this->_compileClass($entry);
        }

        // Add Entry to List of Statements
        $newAST[] = $entry;
      }
    }

    return $newAST;
  }

  /**
   * Converts a Comment AST Entry into a Comment Block Entry, to be merged into
   * the AST of the Next Statement Block
   * 
   * @param array $ast Comment AST
   * @return array Comment Block Entry or Null, if an empty comment
   */
  protected function _compileClass($class) {
    // NOTE: Requires Normalized Class Definition as Created by the Compact Stage

    $sections = ['constants', 'properties', 'methods'];
    foreach ($sections as $section) {

      // Process Each Individual Entry in the Section
      $entries = $class[$section];
      $newEntries = [];

      // Pass All Entries in the Section - Through All the Phases (In Sequence)
      foreach ($entries as $key => $entry) {
        foreach ($this->_phases as $phase) {
          switch ($section) {
            case 'constants':
              $entry = $phase->constant($class, $entry);
              break;
            case 'properties':
              $entry = $phase->property($class, $entry);
              break;
            case 'methods':
              $entry = $phase->method($class, $entry);
              break;
          }

          // Is the entry to be processed further?
          if (!isset($entry)) { // NO: Break Loop
            break;
          }
        }

        // Was the Entry Removed?
        if (isset($entry)) { // NO
          $newEntries[$key] = $entry;
        }
      }


      /* NOTE: We directly set only the properties definition, and not use the
       * shortcut $definition, because the actuall class definition, might be
       * changed, due to the processing of the properties, specifically,
       * ZEP property shortcuts, add methods, to the class definiion.
       */
      $class[$section] = $newEntries;
    }

    return $class;
  }

}
