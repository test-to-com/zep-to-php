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
 * Configuration
 * 
 * Based Directly on Phalcon\DiInterface
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
interface Config extends InjectionAware, \ArrayAccess {

  /**
   * Does the property exist?
   * 
   * @param type $path Path to Property
   * @return boolean 'true' if property exists and has a non-null value.
   */
  public function has($path);

  /**
   * Set a Properties Value
   * 
   * @param type $path Path to Property
   * @param type $value New Value
   * @return self Reference to Self
   */
  public function set($path, $value);

  /**
   * Retrieve a Properties Value
   * 
   * @param string $path Path to Property
   * @return mixed Properties Value or NULL if it does not exist
   */
  public function get($path);

  /**
   * Remove an existing property
   * 
   * @param type $path Path to Property
   * @return self Reference to Self
   */
  public function remove($path);
}
