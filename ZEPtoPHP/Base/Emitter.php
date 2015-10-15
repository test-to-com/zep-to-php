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
interface Emitter extends InjectionAware {

  /**
   * Initialize the Emitter Instance
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function initialize();

  /**
   * 
   * @param type $filename
   */
  public function start($filename = null);

  /**
   * 
   */
  public function end();

  /**
   * 
   * @param type $flag
   * @return self Return instance of Emitter for Function Linking.
   */
  public function indent($flag = true);

  /**
   * 
   * @param type $flag
   * @return self Return instance of Emitter for Function Linking.
   */
  public function unindent($flag = true);

  /**
   * 
   * @param string|array $content 
   * @param type $add_nl
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit($content, $add_nl = false);

  /**
   * 
   * @param type $force
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emitNL($force = true);

  /**
   * 
   * @param type $add_nl
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emitEOS($add_nl = true);
}
