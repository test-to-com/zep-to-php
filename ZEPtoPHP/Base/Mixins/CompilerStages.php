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

namespace ZEPtoPHP\Base\Mixins;

use ZEPtoPHP\Base\Stage;

/**
 * Implements the Stages Component of Compiler Interface
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
trait CompilerStages {

  protected $_stages = [];

  /**
   * Add a stage, to the END, of the current list of stages.
   * 
   * @param Stage $stage new Stage to Add
   * @return self Return instance of compiler for Function Linking.
   */
  public function addStage(Stage $stage) {
    $this->_stages[] = $stage;
    return $this;
  }

  /**
   * Remove the stage, with the given index, from the compiler.
   * 
   * @param integer $index Stage Index
   * @return self Return instance of compiler for Function Linking.
   * @throws \Exception On any Problems
   */
  public function removeStage($index) {
    if (isset($index) && is_integer($index)) {
      $length = count($this->_stages);
      if (($length >= 0) && ($index < $length)) {
        if ($index === 0) {
          array_shift($this->_stages);
        } else if ($index === ($length - 1)) {
          array_pop($this->_stages);
        } else {
          $this->_stages = array_merge(
            array_slice($this->_stages, 0, $index - 1), array_slice($this->_stages, $index + 1
            )
          );
        }
        return $this;
      }
      throw new \Exception("Stage with index [{$index}] doesn't exist in the Compiler");
    }
    throw new \Exception('Parameter [$index] is Missing or Invalid');
  }

  /**
   * Retrieve Current List of Stages
   * 
   * @return array List of Stages
   */
  public function getStages() {
    return $this->_stages;
  }

}
