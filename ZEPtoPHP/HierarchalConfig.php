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

use ZEPtoPHP\Base\Config as IConfig;

/**
 * Hierarchal Implementation of Configuration.
 * Hierarchal meaning that, when the property requested does not exist, then
 * it will search up the path, for value.
 * 
 * Example:
 * get('newline.block.if') will return (in the order given) the value of:
 * 1. if exists, value of newline.block.if
 * 2. if exists, value of newline.block
 * 3. if exists, value of newline
 * 4. NULL
 * 
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class HierarchalConfig implements IConfig {

  // Mixins
  use \ZEPtoPHP\Base\Mixins\DI;

  protected $m_arProps;

  /**
   * Config constructor
   * 
   * @param array $defaults Defaults to Use
   */
  public function __construct($defaults) {
    $this->m_arProps = isset($defaults) && is_array($defaults) ? $defaults : [];
  }

  /**
   * Does the property exist?
   * 
   * @param type $path Path to Property
   * @return boolean 'true' if property exists and has a non-null value.
   */
  public function has($path) {
    return $this->_has(trim($path));
  }

  /**
   * Same as $this->has() except no trimming is done on the path variable
   * 
   * @param type $path Path to Property
   * @return boolean 'true' if property exists and has a non-null value.
   */
  protected function _has($path) {
    return array_key_exists($path, $this->m_arProps) && isset($this->m_arProps[$path]);
  }

  /**
   * Set a Properties Value
   * 
   * @param string $path Path to Property
   * @param mixed $value New Value
   * @return self Reference to Self
   */
  public function set($path, $value) {
    // Is $value not null?
    if (isset($value)) { // YES: Use it
      $this->m_arProps[trim($path)] = $value;
      return $this;
    }
    // ELSE: Remove the $path
    return $this->remove($path);
  }

  /**
   * Retrieve a Properties Value
   * 
   * @param string $path Path to Property
   * @return mixed Properties Value or NULL if it does not exist
   */
  public function get($path, $default = NULL) {
    $path = trim($path);

    // Are we to do a search through the parents?
    $search = $path[strlen($path) - 1] === '|' ? false : true;
    if (!$search) { // NO: Developer specified appended a trailing '|'
      $path = rtrim($path, '|');
    }

    // Does the $path exist?
    $test = str_replace('|', '.', $path);
    if ($this->_has($test)) { // YES: Retrieve it
      return $this->m_arProps[$test];
    }

    // Search Parents?
    if ($search) { // YES
      // Split path (FORMAT: prefix|search_path)
      $parts = explode('|', $path, 2);
      // Do we have a prefix?
      if (count($parts) === 1) { // NO: It's just a search path
        $path = explode('.', $path);
      } else { // YES: explode search path, and then add the prefix
        $path = explode('.', $parts[1]);
        array_unshift($path, $parts[0]);
      }

      // Do we have a path?
      $count = count($path);
      if ($count > 1) { // YES: Search for Parent Properties
        $parent = NULL;
        for ($i = $count - 1; $i > 0; $i--) {
          // Does the $parent exist?
          $parent = implode('.', array_slice($path, 0, $i));
          if ($this->has($parent)) {// YES: Retrieve it
            return $this->m_arProps[$parent];
          }
        }
      }
    }
    // ELSE: Return $default Value
    return $default;
  }

  /**
   * Remove an existing property
   * 
   * @param type $path Path to Property
   * @return self Reference to Self
   */
  public function remove($path) {
    // Does the Property Exist?
    $path = trim($path);
    if ($this->_has($path)) { // YES: Remove it
      unset($this->m_arProps[$path]);
    }
    return $this;
  }

  /* ------------------------------------------------------------------------
   * Implemementation of \ArrayAccess
   * ------------------------------------------------------------------------ */

  /**
   * Check if Property Exists
   * 
   * @return boolean 'true' if it exists, 'false' otherwise
   */
  public function offsetExists($path) {
    return $this->has($path);
  }

  /**
   * Set a Properties Value
   * 
   * @param string $path Path to Property
   * @param mixed $value New Value
   */
  public function offsetSet($path, $value) {
    $this->set($path, $value);
  }

  /**
   * Retrieve a Properties Value
   * 
   * @param string $path Path to Property
   * @return mixed Properties Value or NULL if it does not exist
   */
  public function offsetGet($path) {
    return $this->get($path);
  }

  /**
   * Remove an existing property
   * 
   * @param type $path Path to Property
   */
  public function offsetUnset($path) {
    $this->remove($path);
  }

}
