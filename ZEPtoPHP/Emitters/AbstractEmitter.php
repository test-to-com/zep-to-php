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

namespace ZEPtoPHP\Emitters;

use ZEPtoPHP\Base\Emitter as IEmitter;

/**
 * Console Emitter
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
abstract class AbstractEmitter implements IEmitter {

  // Spaces for Indent
  const spaces = '         ';
  const tabs = "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";

  // Indent Control
  protected $_indent_level = 0;
  protected $_indent_filler = null;
  protected $_indent_max_levels = 10;
  protected $_indent_queue;
  protected $_current = '';
  protected $_current_length = 0;

  /* ------------------------------------------------------------------------ 
   * Implementation of ZEPtoPHP\Base\Emitter
   * ------------------------------------------------------------------------ */

  /**
   * Initialize the Emitter Instance
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function initialize() {
    $this->_indent_queue = [];

    $settings = $this->getDI()['settings'];

    // Maximum of Indentation Levels (DEFAULT: 10)
    $this->_indent_level = 0;
    $this->_indent_max_levels = $settings->get('indent.max_levels|', 10);

    // Indent using Tabs?
    if (!!$settings['indent.tabs']) { // YES
      $this->_indent_filler = "\t";
    } else { // NO: Use Spaces
      $spaces = $settings['indent.spaces|'];
      if (isset($spaces)) {
        $this->_indent_filler = substr(self::spaces, 0, $spaces <= 10 ? $spaces : 10);
      } else {
        $this->_indent_filler = null;
      }
    }

    return $this;
  }

  /**
   * Indent one level, IFF we are at the beginning of a line
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function indent($flag = true) {
    if ($flag && ($this->_current_length === 0)) {
      $this->_indent_level++;
    }

    return $this;
  }

  /**
   * Unindent one level, IFF we are at the beginning of a line
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function unindent($flag = true) {
    if ($flag && ($this->_current_length === 0)) {
      $this->_indent_level--;
    }

    if ($this->_indent_level < 0) {
      throw new \Exception("Indentation Level CANNOT BE less than ZERO");
    }
    return $this;
  }

  /**
   * Save the Current Indent Level in a LIFO Queue
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function push_indent() {
    $this->_indent_queue[] = $this->_indent_level;
    return $this;
  }

  /**
   * Restore Indent Level from Queue
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function pop_indent() {
    if (count($this->_indent_queue) === 0) {
      throw new \Exception("Nothing in Indent Queue");
    }

    $this->_indent_level = array_pop($this->_indent_queue);
    return $this;
  }

  public function emit_keywords($keywords) {
    if (is_string($keywords)) {
      return $this->emit_keyword($keywords);
    }

    if (is_array($keywords)) {
      $count = count($keywords);
      for ($i = 0; $i < $count; $i++) {
        $this->emit_keyword($keywords[$i]);
      }
    }
    return $this;
  }

  public function emit_keyword($keyword, $context = null) {
    $this->_emit_string($keyword, true, true);
    return $this;
  }

  public function emit_operators($operators, $context = null) {
    if (is_string($operators)) {
      return $this->emit_operator($operators, $context);
    }

    if (is_array($operators)) {
      $count = count($operators);
      for ($i = 0; $i < $count; $i++) {
        $this->emit_operator($operators[$i], $context);
      }
    }

    return $this;
  }

  public function emit_operator($operator, $context = null) {
    $context = isset($context) && is_string($context) ? $context : null;
    $context_operator = $operator;

    // Does the Operator Start with a Period?
    $count = strlen($operator);
    if ($operator[0] === '.') { // YES: Special Case (PHP Concat)
      $context_operator = $count === 1 ? 'dot' : 'dot' . ltrim('.', $operator);
    }

    // Get Base Contexts
    $spaces = "spaces.{$context_operator}|";
    $newlines = "newlines.{$context_operator}|";

    // Get Space and New Line Settings
    $settings = $this->getDI()['settings'];
    $space_before = $settings->get(isset($context) ? "{$spaces}before.{$context}" : "{$spaces}before", false);
    $space_after = $settings->get(isset($context) ? "{$spaces}after.{$context}" : "{$spaces}after", false);
    $newline_before = $settings->get(isset($context) ? "{$newlines}before.{$context}" : "{$newlines}before", false);
    $newline_after = $settings->get(isset($context) ? "{$newlines}after.{$context}" : "{$newlines}after", false);

    /* TODO:
     * Consider Adding Indent
     */
    // Do we need to emit a new line (before the operator)?
    if ($newline_before) {
      $space_before = false;
      $this->flush();
    }

    // Emit the Operator String
    $this->_emit_string($operator, $space_before, $newline_after ? false : $space_after);

    // Do we need to emit a new line (after the operator)?
    if ($newline_after) {
      $this->flush();
    }

    return $this;
  }

  /**
   * 
   * @param string|array $content 
   * @param boolean $flush flush pending changes?
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit($text, $flush = false) {
    // Is the Content an Array?
    if (is_array($text)) { // YES: Build a Space Seperated String of the array
      $this->_emit_array($text);
    } else if (is_string($text)) {
      $this->_emit_string($text);
    }

    // Flush Everything?
    if ($flush) { // YES
      $this->flush();
    }

    return $this;
  }

  /**
   * Emit a Space
   * 
   * @param boolean $force [DEFAULT: false] add a space, even if previous character was a space
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_space($force = true) {
    $this->_current.=' ';
    $this->_current_length++;
  }
  
  /**
   * 
   * @param type $add_nl
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_eos($context = null) {
    if (isset($context) && is_string($context)) {
      $context = "statement.{$context}";
    } else {
      $context = 'statement';
    }
    return $this->emit_operator(';', $context);
  }

  /* ------------------------------------------------------------------------ 
   * ABSTRACT METHODS
   * ------------------------------------------------------------------------ */

  abstract protected function _emit_array($array, $space_before = false, $space_after = false, $space_between = true);

  abstract protected function _emit_string($text, $space_before = false, $space_after = false);

  /* ------------------------------------------------------------------------ 
   * HELPER METHODS
   * ------------------------------------------------------------------------ */

  /**
   * Calculate Indentation Padding
   * 
   * @return string
   */
  protected function _indentation() {
    // Do we have an indent filler?
    if (isset($this->_indent_filler)) { // YES: Calculate Indent
      $indent = $this->_indent_level > $this->_indent_max_levels ? $this->_indent_max_levels : $this->_indent_level;
      return str_repeat($this->_indent_filler, $indent);
    }

    return '';
  }

}
