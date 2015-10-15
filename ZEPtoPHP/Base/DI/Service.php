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

namespace ZEPtoPHP\Base\DI;

use ZEPtoPHP\Base\DI as IDI;
use ZEPtoPHP\Base\Service as IService;

/**
 * Dependency Injection Service Implementation
 * 
 * Based Directly on Phalcon\Di\Service
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class Service implements IService {

  protected $_name;
  protected $_definition;
  protected $_shared = false;
  protected $_resolved = false;
  protected $_sharedInstance;

  /**
   * Constructor Definition
   *
   * @param string name
   * @param mixed definition
   * @param boolean shared
   */
  public function __construct($name, $definition, $shared = false) {
    $this->_name = $name;
    $this->_definition = $definition;
    $this->_shared = $shared;
  }

  /**
   * Returns the service's name
   *
   * @return string
   */
  public function getName() {
    return $this->_name;
  }

  /**
   * Sets if the service is shared or not
   */
  public function setShared($shared) {
    $this->_shared = $shared;
  }

  /**
   * Check whether the service is shared or not
   * 
   * @return boolean
   */
  public function isShared() {
    return $this->_shared;
  }

  /**
   * Sets/Resets the shared instance related to the service
   *
   * @param mixed sharedInstance
   */
  public function setSharedInstance($sharedInstance) {
    $this->_sharedInstance = $sharedInstance;
  }

  /**
   * Set the service definition
   *
   * @param mixed definition
   */
  public function setDefinition($definition) {
    $this->_definition = $definition;
  }

  /**
   * Returns the service definition
   *
   * @return mixed
   */
  public function getDefinition() {
    return $this->_definition;
  }

  /**
   * Resolves the service
   *
   * @param array parameters
   * @param \Zephir\API\DI dependencyInjector
   * @return mixed
   */
  public function resolve($parameters = null, IDI $dependencyInjector) {
    $shared = $this->_shared;

    /**
     * Check if the service is shared
     */
    if ($shared) {
      $sharedInstance = $this->_sharedInstance;
      if ($sharedInstance !== null) {
        return sharedInstance;
      }
    }

    $found = true;
    $instance = null;

    $definition = $this->_definition;
    if (gettype($definition) == "string") {

      /**
       * String definitions can be class names without implicit parameters
       */
      if (class_exists($definition)) {
        if (gettype($parameters) == "array") {
          if (count($parameters)) {
            if (is_php_version("5.6")) {
              $reflection = new \ReflectionClass($definition);
              $instance = $reflection->newInstanceArgs($parameters);
            } else {
              $instance = create_instance_params($definition, $parameters);
            }
          } else {
            if (is_php_version("5.6")) {
              $reflection = new \ReflectionClass($definition);
              $instance = $reflection->newInstance();
            } else {
              $instance = create_instance($definition);
            }
          }
        } else {
          if (is_php_version("5.6")) {
            $reflection = new \ReflectionClass($definition);
            $instance = $reflection->newInstance();
          } else {
            $instance = create_instance($definition);
          }
        }
      } else {
        $found = false;
      }
    } else {

      /**
       * Object definitions can be a Closure or an already resolved instance
       */
      if (gettype($definition) == "object") {
        if ($definition instanceof \Closure) {
          if (gettype($parameters) == "array") {
            $instance = call_user_func_array($definition, $parameters);
          } else {
            $instance = call_user_func($definition);
          }
        } else {
          $instance = definition;
        }
      } else {
        /**
         * Array definitions require a 'className' parameter
         */
        if (gettype($definition) == "array") {
          $builder = new Builder();
          $instance = $builder->build($dependencyInjector, $definition, $parameters);
        } else {
          $found = false;
        }
      }
    }

    /**
     * If the service can't be built, we must throw an exception
     */
    if ($found === false) {
      throw new \Exception("Service '" . $this->_name . "' cannot be resolved");
    }

    /**
     * Update the shared instance if the service is shared
     */
    if ($shared) {
      $this->_sharedInstance = $instance;
    }

    $this->_resolved = true;

    return $instance;
  }

  /**
   * Changes a parameter in the definition without resolve the service
   * 
   * @return \Zephir\API\DI\Service
   */
  public function setParameter($position, $parameter) {
    $definition = $this->_definition;
    if (gettype($definition) != "array") {
      throw new \Exception("Definition must be an array to update its parameters");
    }

    /**
     * Update the parameter
     */
    if (isset($definition["arguments"])) {
      $arguments = $definition["arguments"];
      $arguments[$position] = $parameter;
    } else {
      $arguments = ['position' => $parameter];
    }

    /**
     * Re-update the arguments
     */
    $definition["arguments"] = $arguments;

    /**
     * Re-update the definition
     */
    $this->_definition = $definition;

    return $this;
  }

  /**
   * Returns a parameter in a specific position
   *
   * @param int position
   * @return array
   */
  public function getParameter($position) {
    $definition = $this->_definition;
    if (gettype($definition) != "array") {
      throw new Exception("Definition must be an array to obtain its parameters");
    }

    /**
     * Update the parameter
     */
    if (isset($arguments["position"])) {
      $parameter = $arguments["position"];
      return $parameter;
    }

    return null;
  }

  /**
   * Returns true if the service was resolved
   * 
   * @return boolean
   */
  public function isResolved() {
    return $this->_resolved;
  }

  /**
   * Restore the interal state of a service
   * 
   * @return \Zephir\API\DI\Service
   */
  public static function __set_state($attributes) {
    if (isset($attributes["_name"])) {
      $name = $attributes["_name"];
    } else {
      throw new \Exception("The attribute '_name' is required");
    }

    if (isset($attributes["_definition"])) {
      $definition = $attributes["_definition"];
    } else {
      throw new \Exception("The attribute '_definition' is required");
    }

    if (isset($attributes["_shared"])) {
      $shared = $attributes["_shared"];
    } else {
      throw new \Exception("The attribute '_shared' is required");
    }

    return new self($name, $definition, $shared);
  }

}
