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
use ZEPtoPHP\Base\FileSystem as IFileSystem;

/**
 * Console Emitter
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class File implements IEmitter {

  // Mixins
  use \ZEPtoPHP\Base\Mixins\DI;

  // Spaces for Indent
  const spaces = '         ';
  const tabs = "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";

  protected $_current_line = '';
  protected $_current_length = 0;
  protected $_indent_level = 0;
  protected $_file = null;
  protected $_file_contents = [];

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
   * @param string $filepath
   */
  public function start($filepath = null) {
    if (!isset($filepath) || !is_string($filepath)) {
      throw new \Exception('Missing or Invalid File Path');
    }

    $filepath = trim($filepath);
    if (strlen($filepath) === 0) {
      throw new \Exception('Missing File Path');
    }

    // Get Full Output Path
    $fs = $this->getDI()['fileSystem'];
    $this->_file = $fs->realpath($filepath, IFileSystem::OUTPUT);

    $this->_current_line = '';
    $this->_current_length = 0;
    $this->_indent_level = 0;
    $this->_file_contents = [];
  }

  /**
   * 
   */
  public function end() {
    $this->_flush();

    // Write to the File
    $fs = $this->getDI()['fileSystem'];
    if($fs->write($this->_file, $this->_file_contents) === FALSE) {
      throw new \Exception("Failed to write file [{$this->_file}]");
    }
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
      $this->_file_contents[] = $this->_indentation() . $this->_current_line . "\n";
      $this->_current_line = '';
      $this->_current_length = 0;
    } else if ($force) {
      $this->_file_contents[] = "\n";
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
