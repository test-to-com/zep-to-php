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
   * Start Output
   * 
   * @param type $filename
   */
  public function start($filename = null);

  /**
   * End Output
   * 
   */
  public function end();

  /**
   * Indent one level, IFF we are at the beginning of a line
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function indent();

  /**
   * Unindent one level, IFF we are at the beginning of a line
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function unindent();

  /**
   * Save the Current Indent Level in a LIFO Queue
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function push_indent();

  /**
   * Restore Indent Level from Queue
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function pop_indent();

  /**
   * Emit a PHP Rerserver Word
   * 
   * @param string|string[] $keywords Single or List of PHP Keywords to emit
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_keywords($keywords);

  /**
   * Emit a PHP Rerserver Word
   * 
   * @param string[] $keyword Single PHP Keyword to emit
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_keyword($keyword);

  /**
   * Emit a PHP Operator
   * 
   * @param string|string[] $operators PHP Operator
   * @param string $context [OPTIONAL] Context in which to emit the operator (for spaces and new lines)
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_operators($operators, $context = null);

  /**
   * Emit a PHP Operator
   * 
   * @param string $operator PHP Operator
   * @param string $context [OPTIONAL] Context in which to emit the operator (for spaces and new lines)
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_operator($operator, $context = null);

  /**
   * Emit some content
   * 
   * @param string|string[] $text Text or Array of Text to emit
   * @param boolean $flush [DEFAULT: false] Flush after emitting
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit($text, $flush = false);

  /**
   * Emit a Space
   * 
   * @param boolean $force [DEFAULT: false] add a space, even if previous character was a space
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_space($force = true);
  
  /**
   * Emit a New Line
   * 
   * @param boolean $force [DEFAULT: false] add new line, even if current line is empty
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_nl($force = true);

  /**
   * Emit PHP End of Statement (';')
   * 
   * @param type $add_nl
   * @return self Return instance of Emitter for Function Linking.
   */
  public function emit_eos($add_nl = true);

  /**
   * Flush the Current Line
   * 
   * @return self Return instance of Emitter for Function Linking.
   */
  public function flush();
}
