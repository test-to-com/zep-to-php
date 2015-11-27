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

use ZEPtoPHP\Base\FileSystem as IFileSystem;

/**
 * File Emitter
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class File extends AbstractEmitter {

  // Mixins
  use \ZEPtoPHP\Base\Mixins\DI;

  protected $_file = null;
  protected $_file_contents = [];

  /* ------------------------------------------------------------------------ 
   * Implementation of ZEPtoPHP\Base\Emitter
   * ------------------------------------------------------------------------ */

  /**
   * Initialize the Emitter Instance
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function initialize() {
    parent::initialize();

    // Re-Initialize Internal Parameters
    $this->_file = null;
    $this->_file_contents = [];
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

    // Re-Initialize Emitter
    $this->initialize();
    
    // Get Full Output Path
    $fs = $this->getDI()['fileSystem'];
    $this->_file = $fs->realpath($filepath, IFileSystem::OUTPUT);
  }

  /**
   * 
   */
  public function end() {
    $this->flush();

    // Write to the File
    $fs = $this->getDI()['fileSystem'];
    if ($fs->write($this->_file, $this->_file_contents) === FALSE) {
      throw new \Exception("Failed to write file [{$this->_file}]");
    }
  }

  /**
   * 
   * @param type $force
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_nl($force = true) {
    if ($this->_current_length) {
      $this->flush();
    } else if ($force) { // Empty Line
      $this->_file_contents[] = "\n";
      $this->_current = '';
      $this->_current_length = 0;
    }
    return $this;
  }

  public function flush($do = true) {
    if ($do && $this->_current_length) {
      $this->_file_contents[] = $this->_indentation() . trim($this->_current) . "\n";
      $this->_current = '';
      $this->_current_length = 0;
    }

    return $this;
  }

  /* ------------------------------------------------------------------------ 
   * Implementation of ZEPtoPHP\Emitters\AbstractEmitter
   * ------------------------------------------------------------------------ */

  protected function _emit_array($array, $space_before = false, $space_after = false, $space_between = true) {
    $text = $text = implode($space_between ? ' ' : '', array_map(function ($e) {
        return trim($e);
      }, $array));

    $this->_emit_string($text, $space_before, $space_after);
  }

  protected function _emit_string($text, $space_before = false, $space_after = false) {
    if ($space_before) {
      if ($space_after) {
        $this->_current .= " {$text} ";
        $this->_trailing_spaces = 1;
      } else {
        $this->_current .= " {$text}";
        $this->_trailing_spaces = 0;
      }
    } else if ($space_after) {
      $this->_current .= "{$text} ";
      $this->_trailing_spaces = 1;
    } else {
      $this->_current.=$text;
      $this->_trailing_spaces = 0;
    }

    $this->_current_length = strlen($this->_current);
  }

}
