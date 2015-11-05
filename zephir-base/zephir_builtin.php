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
 * Zephir Compiler Functions
 */

/**
 * 
 * @param type $object
 * @param type $property
 * @return type
 */
function zephir_read_property($object, $property) {
  /* TODO Improve Handling 
   * i.e. if $object is not an object -  "Trying to get property \"%s\" of non-object"
   * etc.
   * look at generated code for zephir_read_property
   */
  if (zephir_isset_property($object, $property)) {
    return $object->$property;
  }
}

/**
 * 
 * @param type $array
 * @param type $index
 * @return boolean
 * @throws \Exception
 */
function zephir_isset_array($array, $index) {
  if (isset($array) && isset($index)) {
    switch (gettype($index)) {
      case 'double':
        $index = (integer) $index;
      case 'boolean':
      case 'integer':
      case 'resource':
        return isset($array[$index]);
      case 'NULL':
        $index = '';
      case 'string':
        return array_key_exists($index, $array);
      default:
        throw new \Exception('Illegal offset type');
    }
  }
  return FALSE;
}

/**
 * 
 * @param type $result
 * @param type $array
 * @param type $index
 * @return boolean
 */
function zephir_fetch_array(&$result, $array, $index) {
  if (zephir_isset_array($array, $index)) {
    $result = $array[$index];
    return TRUE;
  }

  return FALSE;
}

/**
 * 
 * @param type $object
 * @param type $property
 * @return boolean
 */
function zephir_isset_property($object, $property) {
  if (isset($object) && isset($property)) {
    if (is_object($object) && is_string($property)) {
      return property_exists($object, $property);
    }
  }

  return FALSE;
}

/**
 * 
 * @param type $result
 * @param type $object
 * @param type $property
 * @return boolean
 */
function zephir_fetch_property(&$result, $object, $property) {
  if (zephir_isset_property($object, $property)) {
    $result = $object->$property;
    return TRUE;
  }

  return FALSE;
}

/**
 * 
 * @param mixed $var
 * @return boolean
 */
function zephir_isempty($var) {
  if (isset($var) && ($var !== null)) {
    if (is_bool($var)) {
      return $var === FALSE;
    } else if (is_string($var)) {
      return strlen($var) === 0;
    }

    // equivalent !zend_is_true($var)
    return ((bool) $var) === FALSE;
  }

  return true;
}

/**
 * 
 * @param mixed $var
 * @return string
 */
function zephir_typeof($var) {
  // Is $var set?
  if (!isset($var)) { // NO: ZEPHIR treats 'undefined' as === 'null'
    return 'null';
  }

  return gettype($var);
}

/**
 * 
 * @param mixed $start
 * @param mixed $finish
 * @return array
 */
function zephir_erange($start, $finish) {
  $range = range($start, $end);
  return count($erange) > 2 ? array_slice($range, 1, -1) : [];
}

/**
 * 
 * @param object $class
 * @param boolean $lower
 * @return string
 * @throws \Exception
 */
function zephir_get_class($class, $lower = false) {
  if (!isset($class) || !is_object($class)) {
    throw new \Exception("zephir_get_class expects an object");
  }

  $classname = get_class($class);
  if (!!$lower) {
    $classname = strtolower($classname);
  }

  return $classname;
}

/**
 * 
 * @param string $str
 * @return string
 * @throws \Exception
 */
function zephir_camelize($str) {
  if (!(isset($str) && is_string($str))) {
    throw new \Exception("Invalid arguments supplied for zephir_camelize()");
  }

  $camilized = implode(array_map('ucfirst', explode('-', $str)));
  $camilized = implode(array_map('ucfirst', explode('_', $str)));
  return $camilized;
}

/**
 * 
 * @param string $str
 * @return string
 * @throws \Exception
 */
function zephir_uncamelize($str) {
  if (!(isset($str) && is_string($str))) {
    throw new \Exception('Invalid arguments supplied for zephir_uncamelize()');
  }

  $matches = [];
  $count = preg_match_all('/[A-Z]/', $str, $matches);
  if ($count) {
    foreach ($matches as $match) {
      $match = $match[0];
      $replace = '_' . strtolower($match);
      $str = str_replace($match, $replace, $str);
    }

    // Remove Leading '_'
    $str = ltrim($str, '_');
  }

  return $str;
}

/**
 * 
 * @param type $object
 * @param type $lower
 * @return type
 * @throws \Exception
 */
function zephir_get_class_ns($object, $lower = false) {
  // Do we have a valid value for 'object' (object or string)
  if (!isset($object) || ((gettype($object) !== 'object') && !is_string($object))) { // NO
    throw new \Exception("zephir_get_class_ns expects an object");
  }

  // Is 'object' an object?
  if (gettype($object) === 'object') { // YES: Get it's class name
    $classname = get_class($object);
  } else { // NO: it's a string
    $classname = trim($object);
    // Is it an empty string?
    if (strlen($classname) === 0) { // YES
      throw new \Exception("zephir_get_class_ns expects an object");
    }

    // Remove Leading any Leading Slash
    $classname = ltrim($classname, '\\');
  }

  // Do we have a namespace component in the class name?
  $slash = strrpos($classname, '\\');
  if ($slash !== FALSE) { // YES: Remove It (we only wan the unqualified class name)
    $classname = substr($classname, $slash + 1);
  }

  // Do we want a lower case version of the class name?
  return !!$lower ? strtolower($classname) : $classname;
}

/**
 * 
 * @param type $object
 * @param type $lower
 * @return type
 * @throws \Exception
 */
function zephir_get_ns_class($object, $lower = false) {
  // Do we have a valid value for 'object' (object or string)
  if (!isset($object) || ((gettype($object) !== 'object') && !is_string($object))) { // NO
    throw new \Exception("zephir_get_ns_class expects an object");
  }

  // Is 'object' an object?
  if (gettype($object) === 'object') { // YES: Get it's class name
    $classname = get_class($object);
  } else { // NO: it's a string
    $classname = trim($object);
    // Is it an empty string?
    if (strlen($classname) === 0) { // YES
      throw new \Exception("zephir_get_ns_class expects an object");
    }

    // Remove Leading any Leading Slash
    $classname = ltrim($classname, '\\');
  }

  // Do we have a namespace component in the class name?
  $slash = strrpos($classname, '\\');
  if ($slash !== FALSE) { // YES: Remove It (we only wan the unqualified class name)
    $namespace = substr($classname, 0, $slash);
  } else {
    $namespace = null;
  }

  // Do we want a lower case version of the namespace?
  return isset($namespace) && (!!$lower) ? strtolower($namespace) : $namespace;
}

/**
 * 
 * @param type $left
 * @param type $values
 * @throws Exception
 */
function zephir_merge_append(&$left, $values) {
  if (!(isset($left) && is_array($left))) {
    throw new Exception('First parameter of zephir_merge_append must be an array');
  }

  if (isset($values)) {
    if (is_array($values)) {
      $left = array_merge($left, $values);
    } else {
      $left[] = $values;
    }
  }
}
