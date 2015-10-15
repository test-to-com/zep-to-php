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

namespace ZEPtoPHP;

use ZEPtoPHP\Base\DI as IDI;
use ZEPtoPHP\Base\Service as IService;
use ZEPtoPHP\Base\InjectionAware as IInjectionAware;
use ZEPtoPHP\Base\DI\Service;

/**
 * Dependency Injection Implementation
 * 
 * Based Directly on Phalcon\Di but with Events Removed..
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class DI implements IDI {

  protected $_services;
  protected $_sharedInstances;
  protected $_freshInstance = false;
  protected static $_default;

  /**
   * Phalcon\Di constructor
   */
  public function __construct() {
    $di = self::$_default;
    if (!$di) {
      self::$_default = $this;
    }
  }

  /**
   * Registers a service in the services container
   *
   * @param string name
   * @param mixed definition
   * @param boolean shared
   * @return Zephir\API\DI\Service
   */
  public function set($name, $definition, $shared = false) {
    $service = new Service($name, $definition, $shared);
    $this->_services[$name] = $service;
    return $service;
  }

  /**
   * Registers an "always shared" service in the services container
   *
   * @param string name
   * @param mixed definition
   * @return Zephir\API\DI\Service
   */
  public function setShared($name, $definition) {
    $service = new Service($name, $definition, true);
    $this->_services[$name] = $service;
    return $service;
  }

  /**
   * Removes a service in the services container
   */
  public function remove($name) {
    unset($this->_services[$name]);
  }

  /**
   * Attempts to register a service in the services container
   * Only is successful if a service hasn"t been registered previously
   * with the same name
   *
   * @param string name
   * @param mixed definition
   * @param boolean shared
   * @return  Zephir\API\DI\Service|false
   */
  public function attempt($name, $definition, $shared = false) {
    $service;

    if (!isset($this->_services[$name])) {
      $service = new Service($name, $definition, $shared);
      $this->_services[$name] = $service;
      return $service;
    }

    return false;
  }

  /**
   * Sets a service using a raw Phalcon\Di\Service definition
   * 
   * @return  Zephir\API\DI\Service
   */
  public function setRaw($name, IService $rawDefinition) {
    $this->_services[$name] = $rawDefinition;
    return $rawDefinition;
  }

  /**
   * Returns a service definition without resolving
   *
   * @param string name
   * @return mixed
   */
  public function getRaw($name) {
    if (isset($this->_services[$name])) {
      $service = $this->_services[$name];
      return $service->getDefinition();
    }

    throw new \Exception("Service '" . $name . "' wasn't found in the dependency injection container");
  }

  /**
   * Returns the corresponding Phalcon\Di\Service instance for a service
   * 
   * @return \Zephir\API\DI\Service
   */
  public function getService($name) {
    if (isset($this->_services[$name])) {
      $service = $this->_services[$name];
      return $service;
    }

    throw new Exception("Service '" . name . "' wasn't found in the dependency injection container");
  }

  /**
   * Resolves the service based on its configuration
   *
   * @param string name
   * @param array parameters
   * @return mixed
   */
  public function get($name, $parameters = null) {
    /*
      $eventsManager = <\Phalcon\Events\ManagerInterface> this->getEventsManager();

      if typeof eventsManager == "object" {
      eventsManager->fire("di:beforeServiceResolve", this, ["name": name, "parameters": parameters]);
      }
     */

    if (isset($this->_services[$name])) {
      $service = $this->_services[$name];
      /**
       * The service is registered in the DI
       */
      $instance = $service->resolve($parameters, $this);
    } else {
      /**
       * The DI also acts as builder for any class even if it isn't defined in the DI
       */
      if (class_exists($name)) {
        if (gettype($parameters) == "array") {
          if (count($parameters)) {
            if (is_php_version("5.6")) {
              $reflection = new \ReflectionClass($name);
              $instance = $reflection->newInstanceArgs($parameters);
            } else {
              $instance = create_instance_params($name, $parameters);
            }
          } else {
            if (is_php_version("5.6")) {
              $reflection = new \ReflectionClass($name);
              $instance = $reflection->newInstance();
            } else {
              $instance = create_instance($name);
            }
          }
        } else {
          if (is_php_version("5.6")) {
            $reflection = new \ReflectionClass($name);
            $instance = $reflection->newInstance();
          } else {
            $instance = create_instance($name);
          }
        }
      } else {
        throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
      }
    }

    /**
     * Pass the DI itself if the instance implements \Phalcon\Di\InjectionAwareInterface
     */
    if (gettype($instance) == "object") {
      if ($instance instanceof IInjectionAware) {
        $instance->setDI($this);
      }
    }

    /*
      if typeof eventsManager == "object" {
      / **
     * Pass the EventsManager if the instance implements \Phalcon\Events\EventsAwareInterface
     * /
      if typeof instance == "object" {
      if instance instanceof EventsAwareInterface {
      instance->setEventsManager(eventsManager);
      }
      }

      eventsManager->fire("di:afterServiceResolve", this, ["name": name, "parameters": parameters, "instance": instance]);
      }

     */

    return $instance;
  }

  /**
   * Resolves a service, the resolved service is stored in the DI, subsequent requests for this service will return the same instance
   *
   * @param string name
   * @param array parameters
   * @return mixed
   */
  public function getShared($name, $parameters = null) {
    $instance;

    /**
     * This method provides a first level to shared instances allowing to use non-shared services as shared
     */
    if (isset($this->_sharedInstances[$name])) {
      $instance = $this->_sharedInstances[$name];
      $this->_freshInstance = false;
    } else {

      /**
       * Resolve the instance normally
       */
      $instance = $this->get($name, $parameters);

      /**
       * Save the instance in the first level shared
       */
      $this->_sharedInstances[$name] = $instance;
      $this->_freshInstance = true;
    }

    return $instance;
  }

  /**
   * Check whether the DI contains a service by a name
   *
   * @return boolean
   */
  public function has($name) {
    return isset($this->_services[$name]);
  }

  /**
   * Check whether the last service obtained via getShared produced a fresh instance or an existing one
   *
   * @return boolean
   */
  public function wasFreshInstance() {
    return $this->$_freshInstance;
  }

  /**
   * Return the services registered in the DI
   *
   * @return array
   */
  public function getServices() {
    return $this->_services;
  }

  /**
   * Check if a service is registered using the array syntax
   * 
   * @return boolean
   */
  public function offsetExists($name) {
    return $this->has($name);
  }

  /**
   * Allows to register a shared service using the array syntax
   *
   * <code>
   * 	$di["request"] = new \Phalcon\Http\Request();
   * </code>
   *
   * @param string name
   * @param mixed definition
   * @return boolean
   */
  public function offsetSet($name, $definition) {
    $this->setShared($name, $definition);
    return true;
  }

  /**
   * Allows to obtain a shared service using the array syntax
   *
   * <code>
   * 	var_dump($di["request"]);
   * </code>
   *
   * @param string name
   * @return mixed
   */
  public function offsetGet($name) {
    return $this->getShared($name);
  }

  /**
   * Removes a service from the services container using the array syntax
   * @return boolean
   */
  public function offsetUnset($name) {
    return false;
  }

  /**
   * Magic method to get or set services using setters/getters
   *
   * @param string method
   * @param array arguments
   * @return mixed
   */
  public function __call($method, $arguments = null) {
    /**
     * If the magic method starts with "get" we try to get a service with that name
     */
    if (starts_with($method, "get")) {
      $services = $this->_services;
      $possibleService = lcfirst(substr($method, 3));
      if (isset($services[$possibleService])) {
        if (count($arguments)) {
          $instance = $this->get($possibleService, $arguments);
        } else {
          $instance = $this->get($possibleService);
        }
        return $instance;
      }
    }

    /**
     * If the magic method starts with "set" we try to set a service using that name
     */
    if (starts_with($method, "set")) {
      if (isset($arguments[0])) {
        $definition = $arguments[0];
        $this->set(lcfirst(substr($method, 3)), $definition);
        return null;
      }
    }

    /**
     * The method doesn't start with set/get throw an exception
     */
    throw new \Exception("Call to undefined method or service '" . $method . "'");
  }

  /**
   * Set a default dependency injection container to be obtained into static methods
   * 
   * @param \Zephir\API\DI dependencyInjector
   */
  public static function setDefault(IDI $dependencyInjector) {
    self::$_default = $dependencyInjector;
  }

  /**
   * Return the last DI created
   * 
   * @return \Zephir\API\DI
   */
  public static function getDefault() {
    return self::_default;
  }

  /**
   * Resets the internal default DI
   */
  public static function reset() {
    self::$_default = null;
  }

}
