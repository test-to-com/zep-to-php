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

namespace ZEPtoPHP\Base\FileSystem;

use ZEPtoPHP\Base\FileSystem as IFileSystem;

/**
 * HardDisk
 *
 * Uses the standard hard-disk as filesystem for temporary operations
 */
abstract class FileSystemAbstract implements IFileSystem {

  protected $initialized = false;

  /**
   * Checks if the filesystem is initialized
   *
   * @return boolean 'true' if initialized, 'false' otherwise
   */
  public function isInitialized() {
    return $this->initialized;
  }

  /**
   * Initialize the filesystem
   * 
   * @return boolean 'true' if initialized, 'false' otherwise
   */
  public function initialize() {
    if (!$this->isInitialized()) {
      $this->initialized = $this->_initialize();
    }

    return $this->initialized;
  }

  /**
   * Perform the Actual FileSystem Initialization
   * 
   * @return boolean 'true' if initialized, 'false' otherwise
   */
  abstract protected function _initialize();
}
