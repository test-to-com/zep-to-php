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

/**
 * Console Emitter
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class Console extends AbstractEmitter {

  // Mixins
  use \ZEPtoPHP\Base\Mixins\DI;

  protected $_trailing_spaces = 0;

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
  }

  /**
   * 
   * @param string $filename
   */
  public function start($filename = null) {
    
  }

  /**
   * 
   */
  public function end() {
    $this->flush();
  }

  /**
   * 
   * @param type $force
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_nl($force = true) {
    if ($this->_current_length) {
      $this->flush();
    } else if ($force) {
      echo "\n";
    }
    return $this;
  }

  public function flush($do = true) {
    if ($do && $this->_current_length) {
      echo $this->_indentation() . trim($this->_current) . "\n";
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
