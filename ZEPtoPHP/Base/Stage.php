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
 * Compiler Stage Definition
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
interface Stage extends InjectionAware {

  /**
   * Initialize the Stage Instance
   * 
   * @return self Return instance of stage for Function Linking.
   */
  public function initialize();

  /**
   * Reset the Stage Instance (set the default state, if a stage is to
   * be re-used)
   * 
   * @return self Return instance of stage for Function Linking.
   */
  public function reset();
  
  /**
   * Compile or Transform the AST.
   * 
   * @param array $ast AST to be compiled/transformed
   * @return array Old or Transformed AST
   */
  public function compile($ast);
}
