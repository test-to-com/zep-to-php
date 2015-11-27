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

namespace ZEPtoPHP\Base\Mixins;

use ZEPtoPHP\Base\DI as IDI;

/**
 * Implements Requires for Interface InjectionAware
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
trait DI {

  protected $_di;

  /**
   * Sets the dependency injector
   * 
   * @param \ZEPtoPHP\Base\DI $di New Dependency Object
   */
  public function setDI(IDI $di) {
    $this->_di = $di;
    return $this;
  }

  /**
   * Returns the internal dependency injector
   * 
   * @return \ZEPtoPHP\Base\DI Current Dependency Injection Object used
   */
  public function getDI() {
    if (isset($this->_di)) {
      return $this->_di;
    }

    throw new \Exception('Dependency Injection has not been initialized.');
  }

}
