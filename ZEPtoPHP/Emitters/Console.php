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
class Console implements IEmitter {

  // Mixins
  use \ZEPtoPHP\Base\Mixins\DI;

  // Spaces for Indent
  const spaces = '         ';
  const tabs = "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";

  protected $_current_line = '';
  protected $_current_length = 0;
  protected $_indent_level = 0;

  /**
   * Initialize the Emitter Instance
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function initialize() {
    return $this;
  }

  /**
   * 
   * @param string $filename
   */
  public function start($filename = null) {
    $this->_current_line = '';
    $this->_current_length = 0;
    $this->_indent_level = 0;
  }

  /**
   * 
   */
  public function end() {
    $this->_flush();
  }

  /**
   * 
   * @param type $flag
   * @return self Return instance of Emitter for Function Linking.
   */
  public function indent($flag = true) {
    if ($flag) {
      $this->_indent_level++;
    }

    return $this;
  }

  /**
   * 
   * @param type $flag
   * @return self Return instance of Emitter for Function Linking.
   */
  public function unindent($flag = true) {
    if ($flag) {
      $this->_indent_level--;
    }

    if ($this->_indent_level < 0) {
      throw new \Exception("Indentation Level CANNOT BE less than ZERO");
    }
    return $this;
  }

  /**
   * 
   * @param string|array $content 
   * @param type $add_nl
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit($content, $nl = false) {
    // Is the Content an Array?
    if (is_array($content)) { // YES: Build a Space Seperated String of the array
      $content = implode(' ', array_map(function ($e) {
          return trim($e);
        }, $content));
    }

    // Is the Current Line Empty?
    if ($this->_current_length === 0) { // YES
      $this->_current_line = trim($content);
    } else { // NO: Append
      $this->_current_line.=' ' . trim($content);
    }
    $this->_current_length = strlen($this->_current_line);

    return $nl ? $this->emitNL($nl) : $this;
  }

  /**
   * 
   * @param type $force
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emitNL($force = true) {
    $this->_flush($force);
    return $this;
  }

  /**
   * 
   * @param type $add_nl
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emitEOS($nl = true) {
    return $this->emit(';', $nl);
  }

  protected function _flush($force = false) {
    if ($this->_current_length) {
      echo $this->_indentation() . $this->_current_line . "\n";
      $this->_current_line = '';
      $this->_current_length = 0;
    } else if ($force) {
      echo "\n";
    }
  }

  protected function _indentation() {
    // TODO: Move to the Flags to Configuration File
    $config_indentSpaces = true; // Seperate interface / extends with line-feed
    $config_indentSize = 2; // Seperate interface / extends with line-feed
    $config_indentMax = 10; // Maximum of 10 Indent Levels
    // Create Indent Filler Unit
    if ($config_indentSpaces) {
      $filler = substr(self::spaces, 0, $config_indentSize <= 10 ? $config_indentSize : 10);
    } else {
      $filler = "\t";
    }

    // Calculate Filler
    $indent = $this->_indent_level > $config_indentMax ? $config_indentMax : $this->_indent_level;
    $filler = str_repeat($filler, $indent);
    return $filler;
  }

}
