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

/*
 * Zephir Included User Functions
 */

/**
 * 
 * @param string $version
 * @return boolean
 */
function is_php_version($version) {
  $version = isset($version) && is_string($version) ? trim($version) : null;
  if (isset($version) && count($version)) {
    return substr_compare(phpversion(), $version, 0, count($version)) === 0;
  }
  return false;
}

/**
 * See zephir_memnstr() and zephir_memnstr_str() in file string.c
 * 
 * Check if a string is contained into another
 * 
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function memstr($haystack, $needle) {
  if (!isset($haystack) && is_string($haystack)) {
    return false;
  }
  if (!isset($needle) && is_string($needle)) {
    return false;
  }

  return !(strpos($haystack, $needle) === FALSE);
}

/**
 * 
 * @param type $object
 * @param type $lower
 * @return type
 */
function get_class_ns($object, $lower = false) {
  return zephir_get_class_ns($object, $lower);
}

/**
 * 
 * @param type $object
 * @param type $lower
 * @return type
 */
function get_ns_class($object, $lower = false) {
  return zephir_get_ns_class($object, $lower);
}

/**
 * 
 * @param type $str
 * @return type
 */
function camelize($str) {
  return zephir_camelize($str);
}

/**
 * 
 * @param type $str
 * @return type
 */
function uncamelize($str) {
  return zephir_uncamelize($str);
}

/**
 * See zephir_start_with() in file string.c
 * 
 * Checks if a zval string starts with a zval string
 * 
 * @param string $haystack
 * @param string $needle
 * @param boolean $case_sensitive
 * @return boolean
 */
function starts_with($haystack, $needle, $case_sensitive = false) {
  if (!isset($haystack) && is_string($haystack)) {
    return false;
  }
  if (!isset($needle) && is_string($needle)) {
    return false;
  }

  $l_haystack = strlen($haystack);
  $l_needle = strlen($needle);
  if ($l_haystack < $l_needle) {
    return false;
  } else if ($l_haystack === $l_needle) {
    return $case_sensitive ? $l_haystack === $l_needle : strcasecmp($haystack, $needle) === 0;
  } else {
    return $case_sensitive ? substr($haystack, 0, $l_needle) === $needle : strncasecmp($haystack, $needle, $l_needle) === 0;
  }
}

/**
 * 
 * @param type $str
 * @return type
 */
function ends_with($str) {
  throw new \Exception('Function Not Implemented.');
}

/**
 * See zephir_prepare_virtual_path() in file.c
 * 
 * Replaces directory separators by the virtual separator
 * 
 * @param string $path
 * @param string $virtual_separator
 * @return string
 */
function prepare_virtual_path($path, $virtual_separator) {
  if (isset($path) && is_string($path)) {
    if (!isset($virtual_separator) || !is_string($virtual_separator)) {
      return $path;
    }
  } else {
    return '';
  }

  // Convert Path to lower case
  $virtual_str = strtolower($path);
  // replace '/', '\' and ':' with $virtual_separator
  $virtual_str = preg_replace("/\/|\\|:/", $virtual_separator, $virtual_str);
  return $virtual_str;
}

/**
 * See zephir_create_instance() in file object.c
 * 
 * Creates a new instance dynamically. Call constructor without parameters
 * 
 * @param string $class
 * @return \class
 * @throws \Exception
 */
function create_instance($class) {
  // Is 'class' a valid string?
  if (!isset($class) || !is_string($class)) { // YES
    throw new \Exception("Invalid class name");
  }

  // Does class exist?
  if (!class_exists($class)) { // YES
    throw new \Exception("Class [{$class}] does not exist");
  }

  // Create Class Instance
  return new $class;
}

/**
 * See zephir_create_instance_params() in file object.c
 * 
 * Creates a new instance dynamically calling constructor with parameters
 * s
 * @param string $class
 * @param array $parameters
 * @return type
 * @throws \Exception
 */
function create_instance_params($class, $parameters) {
  // Is 'class' a valid string?
  if (!isset($class) || !is_string($class)) { // YES
    throw new \Exception("Invalid class name");
  }

  // Is 'parameters' a valid array?
  if (!isset($parameters) || !is_array($parameters)) { // YES
    throw new \Exception("Instantiation parameters must be an array");
  }

  // Does class exist?
  if (!class_exists($class)) { // YES
    throw new \Exception("Class [{$class}] does not exist");
  }

  // Build Constructor Parameters
  $re_args = [];
  $refMethod = new ReflectionMethod($class, '__construct');
  foreach ($refMethod->getParameters() as $key => $param) {
    if ($param->isPassedByReference()) {
      $re_args[$key] = &$parameters[$key];
    } else {
      $re_args[$key] = $parameters[$key];
    }
  }

  // Create Class Instance
  $refClass = new ReflectionClass('class_name_here');
  return $refClass->newInstanceArgs((array) $re_args);
}

/**
 * See zephir_create_symbol_table() in memory.c
 * 
 */
function create_symbol_table() {
  // Zephir Extension Optimication (not required in PHP)
  // see kernel/**/memory.c zephir_create_symbol_table
}

/**
 * See zephir_compare_mtime() in file.c
 * 
 * Compares two file paths returning 1 if the first mtime is greater or equal than the second
 * 
 * @param string $filename1
 * @param string $filename2
 * @return int
 * @throws Exception
 */
function compare_mtime($filename1, $filename2) {
  if (!isset($filename1) || !is_string($filename1)) {
    throw new Exception("Invalid arguments supplied for compare_mtime()");
  }
  if (!isset($filename2) || !is_string($filename2)) {
    throw new Exception("Invalid arguments supplied for compare_mtime()");
  }

  $mfilename1 = filemtime($filename1);
  $mfilename2 = filemtime($filename2);
  if (($mfilename1 === FALSE) || ($mfilename2 === FALSE)) {
    throw new Exception("Invalid arguments supplied for compare_mtime()");
  }

  return (int) $mfilename1 >= $mfilename2;
}

// function function globals_get($globalName) - implemented in zephir_globals.php
// function function globals_set($globalName, $value) - implemented in zephir_globals.php

/**
 * 
 * @param type $left
 * @param type $values
 */
function merge_append(&$left, $values) {
  zephir_merge_append($left, $values);
}

/**
 * 
 * @param object $class
 * @return string
 * @throws \Exception
 */
function get_class_lower($class) {
  return zephir_get_class($class, true);
}

/**
 * 
 * @param type $object
 * @return type
 * @throws \Exception
 */
function iterator($object) {
  if (!isset($object) || !is_object($object)) {
    throw new \Exception("iterator expects an object");
  }
  
  return $object;
}