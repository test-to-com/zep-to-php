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
 * DI capable object
 * 
 * Based Directly on Phalcon\Di\InjectionAwareInterface;
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
interface InjectionAware {

  /**
   * Sets the dependency injector
   * 
   * @param \ZEPtoPHP\Base\DI $dependencyInjector New Dependency Object
   */
  public function setDI(DI $dependencyInjector);

  /**
   * Returns the internal dependency injector
   * 
   * @return \ZEPtoPHP\Base\DI Current Dependency Injection Object used
   */
  public function getDI();
}
