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

error_reporting(E_ALL);

// Current Version
define('VERSION', '0.1.0');

// PATHS
define('BASEPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('BINPATH', __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR);

if (file_exists(__DIR__ . '/externals/Kit-ClassLoader/src/autoload.php')) {
  require_once __DIR__ . '/externals/Kit-ClassLoader/src/autoload.php';

  $loader = new \Riimu\Kit\ClassLoader\ClassLoader();
  $loader->addPrefixPath(__DIR__ . '/ZEPtoPHP', 'ZEPtoPHP');
  $loader->addBasePath(__DIR__ . '/externals/GetOptionKit/src/');
  $loader->register();
} else {
  throw new Exception("Missing Autoloader");
}
