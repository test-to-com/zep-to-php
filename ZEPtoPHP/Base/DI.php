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

use ZEPtoPHP\Base\Service;

/**
 * Dependence Injection 
 * 
 * Based Directly on Phalcon\DiInterface
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
interface DI extends \ArrayAccess {

  /**
   * Registers a service in the services container
   *
   * @param string name
   * @param mixed definition
   * @param boolean shared
   * @return \Zephir\API\DI
   */
  public function set($name, $definition, $shared = false);

  /**
   * Registers an "always shared" service in the services container
   *
   * @param string name
   * @param mixed definition
   * @return \Zephir\API\DI\Service
   */
  public function setShared($name, $definition);

  /**
   * Removes a service in the services container
   */
  public function remove($name);

  /**
   * Attempts to register a service in the services container
   * Only is successful if a service hasn't been registered previously
   * with the same name
   *
   * @param string name
   * @param mixed definition
   * @param boolean shared
   * @return \Zephir\API\DI\Service
   */
  public function attempt($name, $definition, $shared = false);

  /**
   * Resolves the service based on its configuration
   *
   * @param string name
   * @param array parameters
   * @return mixed
   */
  public function get($name, $parameters = null);

  /**
   * Returns a shared service based on their configuration
   *
   * @param string name
   * @param array parameters
   * @return mixed
   */
  public function getShared($name, $parameters = null);

  /**
   * Sets a service using a raw Phalcon\Di\Service definition
   * 
   * @return \Zephir\API\DI\Service
   */
  public function setRaw($name, Service $rawDefinition);

  /**
   * Returns a service definition without resolving
   *
   * @param string name
   * @return mixed
   */
  public function getRaw($name);

  /**
   * Returns the corresponding Phalcon\Di\Service instance for a service
   * 
   * @return \Zephir\API\DI\Service
   */
  public function getService($name);

  /**
   * Check whether the DI contains a service by a name
   *
   * @return boolean
   */
  public function has($name);

  /**
   * Check whether the last service obtained via getShared produced a fresh instance or an existing one
   *
   * @return boolean
   */
  public function wasFreshInstance();

  /**
   * Return the services registered in the DI
   *
   * @return array
   */
  public function getServices();

  /**
   * Set a default dependency injection container to be obtained into static methods
   * 
   * @return \Zephir\API\DI
   */
  public static function setDefault(DI $dependencyInjector);

  /**
   * Return the last DI created
   * 
   * @return \Zephir\API\DI
   */
  public static function getDefault();

  /**
   * Resets the internal default DI
   */
  public static function reset();
}
