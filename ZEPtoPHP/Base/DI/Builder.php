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

use ZEPtoPHP\Base\DI;

require_once __DIR__ . '/../../builtin.php';

/**
 * Dependency Injection Service Implementation
 * 
 * Based Directly on Phalcon\Di\Service
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class Builder {

  /**
   * Resolves a constructor/call parameter
   *
   * @param \ZEPtoPHP\Base\DI dependencyInjector
   * @param int position
   * @param array argument
   * @return mixed
   */
  private function _buildParameter(DI $dependencyInjector, $position, $argument) {
    /**
     * All the arguments must be an array
     */
    if (gettype($argument) != "array") {
      throw new Exception("Argument at position " . $position . " must be an array");
    }

    /**
     * All the arguments must have a type
     */
    if (isset($argument["type"])) {
      $type = $argument["type"];
    } else {
      throw new Exception("Argument at position " . $position . " must have a type");
    }

    switch ($type) {

      /**
       * If the argument type is 'service', we obtain the service from the DI
       */
      case "service":
        if (isset($argument["name"])) {
          $name = $argument["name"];
        } else {
          throw new Exception("Service 'name' is required in parameter on position " . $position);
        }
        if (gettype($dependencyInjector) != "object") {
          throw new Exception("The dependency injector container is not valid");
        }
        return $dependencyInjector->get($name);

      /**
       * If the argument type is 'parameter', we assign the value as it is
       */
      case "parameter":
        if (isset($argument["value"])) {
          $value = $argument["value"];
        } else {
          throw new Exception("Service 'value' is required in parameter on position " . $position);
        }
        return value;

      /**
       * If the argument type is 'instance', we assign the value as it is
       */
      case "instance":
        if (isset($argument["className"])) {
          $name = $argument["className"];
        } else {
          throw new Exception("Service 'className' is required in parameter on position " . $position);
        }

        if (gettype($dependencyInjector) != "object") {
          throw new Exception("The dependency injector container is not valid");
        }

        if (isset($argument["arguments"])) {
          $instanceArguments = $argument["arguments"];
          /**
           * Build the instance with arguments
           */
          return $dependencyInjector->get($name, $instanceArguments);
        }

        /**
         * The instance parameter does not have arguments for its constructor
         */
        return $dependencyInjector->get($name);

      default:
        /**
         * Unknown parameter type
         */
        throw new Exception("Unknown service type in parameter on position " . position);
    }
  }

  /**
   * Resolves an array of parameters
   *
   * @param \ZEPtoPHP\Base\DI dependencyInjector
   * @param array arguments
   * @return array
   */
  private function _buildParameters(DI $dependencyInjector, $arguments) {
    /**
     * The arguments group must be an array of arrays
     */
    if (gettype($arguments) != "array") {
      throw new Exception("Definition arguments must be an array");
    }

    $buildArguments = [];
    foreach ($arguments as $position => $argument) {
      $buildArguments[] = $this->_buildParameter($dependencyInjector, $position, $argument);
    }
    return $buildArguments;
  }

  /**
   * Builds a service using a complex service definition
   *
   * @param \ZEPtoPHP\Base\DI dependencyInjector
   * @param array definition
   * @param array parameters
   * @return mixed
   */
  public function build(DI $dependencyInjector, $definition, $parameters = null) {
    /**
     * The class name is required
     */
    if (isset($definition["className"])) {
      $className = $definition["className"];
    } else {
      throw new Exception("Invalid service definition. Missing 'className' parameter");
    }

    if (gettype($parameters) != "array") {

      /**
       * Build the instance overriding the definition constructor parameters
       */
      if (count($parameters)) {
        if (is_php_version("5.6")) {
          $reflection = new \ReflectionClass($className);
          $instance = $reflection->newInstanceArgs($parameters);
        } else {
          $instance = create_instance_params($className, $parameters);
        }
      } else {
        if (is_php_version("5.6")) {
          $reflection = new \ReflectionClass($className);
          $instance = $reflection->newInstance();
        } else {
          $instance = create_instance($className);
        }
      }
    } else {

      /**
       * Check if the argument has constructor arguments
       */
      if (isset($definition["arguments"])) {
        $arguments = $definition["arguments"];

        /**
         * Create the instance based on the parameters
         */
        $instance = create_instance_params($className, $this->_buildParameters($dependencyInjector, $arguments));
      } else {
        if (is_php_version("5.6")) {
          $reflection = new \ReflectionClass($className);
          $instance = $reflection->newInstance();
        } else {
          $instance = create_instance($className);
        }
      }
    }

    /**
     * The definition has calls?
     */
    if (isset($definition["calls"])) {
      $paramCalls = $definition["calls"];

      if (gettype($instance) != "object") {
        throw new Exception("The definition has setter injection parameters but the constructor didn't return an instance");
      }

      if (gettype($paramCalls) != "array") {
        throw new Exception("Setter injection parameters must be an array");
      }

      /**
       * The method call has parameters
       */
      foreach ($paramCalls as $methodPosition => $method) {

        /**
         * The call parameter must be an array of arrays
         */
        if (gettype($method) != "array") {
          throw new Exception("Method call must be an array on position " . $methodPosition);
        }

        /**
         * A param 'method' is required
         */
        if (isset($method["method"])) {
          $methodName = $method["method"];
        } else {
          throw new Exception("The method name is required on position " . $methodPosition);
        }

        /**
         * Create the method call
         */
        $methodCall = [$instance, $methodName];

        if (isset($method["arguments"])) {
          $arguments = $method["arguments"];

          if (gettype($arguments) != "array") {
            throw new Exception("Call arguments must be an array " . $methodPosition);
          }

          if (count($arguments)) {

            /**
             * Call the method on the instance
             */
            call_user_func_array($methodCall, $this->_buildParameters($dependencyInjector, $arguments));

            /**
             * Go to next method call
             */
            continue;
          }
        }

        /**
         * Call the method on the instance without arguments
         */
        call_user_func($methodCall);
      }
    }

    /**
     * The definition has properties?
     */
    if (isset($definition["properties"])) {
      $paramCalls = $definition["properties"];

      if (gettype($instance) != "object") {
        throw new Exception("The definition has properties injection parameters but the constructor didn't return an instance");
      }

      if (gettype($paramCalls) != "array") {
        throw new Exception("Setter injection parameters must be an array");
      }

      /**
       * The method call has parameters
       */
      foreach ($paramCalls as $propertyPosition => $property) {

        /**
         * The call parameter must be an array of arrays
         */
        if (gettype($property) != "array") {
          throw new Exception("Property must be an array on position " . $propertyPosition);
        }

        /**
         * A param 'name' is required
         */
        if (isset($property["name"])) {
          $propertyName = $property["name"];
        } else {
          throw new Exception("The property name is required on position " . $propertyPosition);
        }

        /**
         * A param 'value' is required
         */
        if (isset($property["value"])) {
          $propertyValue = $property["value"];
        } else {
          throw new Exception("The property value is required on position " . $propertyPosition);
        }

        /**
         * Update the public property
         */
        $instance->{$propertyName} = $this->_buildParameter($dependencyInjector, $propertyPosition, $propertyValue);
      }
    }

    return instance;
  }

}
