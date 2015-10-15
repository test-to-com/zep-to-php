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
 * Injected Service
 * 
 * Based Directly on Phalcon\Di\ServiceInterface;
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
interface Service {

  /**
   * Constructor Definition
   *
   * @param string name
   * @param mixed definition
   * @param boolean shared
   */
  public function __construct($name, $definition, $shared = false);

  /**
   * Returns the service's name
   *
   * @return string
   */
  public function getName();

  /**
   * Sets if the service is shared or not
   */
  public function setShared($shared);

  /**
   * Check whether the service is shared or not
   * 
   * @return boolean
   */
  public function isShared();

  /**
   * Set the service definition
   *
   * @param mixed definition
   */
  public function setDefinition($definition);

  /**
   * Returns the service definition
   *
   * @return mixed
   */
  public function getDefinition();

  /**
   * Resolves the service
   *
   * @param array parameters
   * @param Zephir\API\DI dependencyInjector
   * @return mixed
   */
  public function resolve($parameters = null, DI $dependencyInjector);

  /**
   * Restore the interal state of a service
   * 
   * @return \Zephir\API\DI\Service
   */
  public static function __set_state($attributes);
}
