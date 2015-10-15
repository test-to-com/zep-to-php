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

namespace ZEPtoPHP\Base;

/**
 * Compiler Definition
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
interface Compiler extends InjectionAware {

  /**
   * Initialize the Compiler Instance
   * 
   * @return self Return instance of compiler for Function Linking.
   */
  public function initialize();
  
  /**
   * Add a stage, to the END, of the current list of stages.
   * 
   * @param Stage $stage new Stage to Add
   * @return self Return instance of compiler for Function Linking.
   */
  public function addStage(Stage $stage);

  /**
   * Remove the stage, with the given index, from the compiler.
   * 
   * @param integer $index Stage Index
   * @return self Return instance of compiler for Function Linking.
   * @throws \Exception On any Problems
   */
  public function removeStage($index);

  /**
   * Retrieve Current List of Stages
   * 
   * @return array List of Stages
   */
  public function getStages();

  public function project($path);

  public function files($paths);

  /**
   * Emit Code for a Single File
   * 
   * @param type $path
   * @throws \Exception
   */
  public function file($path);
}
