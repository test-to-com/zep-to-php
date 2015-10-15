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
 * Provides a Universal Interface to Zephir Required Filesystem Actions
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
interface FileSystem {
  /*
   * Possible File System (Relative To) Subspaces
   */

  const NONE = 0;
  const SYSTEM = 1;
  const INPUT = 2;
  const OUTPUT = 3;
  const CACHE = 4;
  const TEMP = 5;

  /**
   * Checks if the filesystem is initialized
   *
   * @return boolean
   */
  public function isInitialized();

  /**
   * Initialize the filesystem
   */
  public function initialize();

  /**
   * Generates a Full Path, using the given path as a basis, relative to
   * the specified File System Space.
   * The path produced, if not null, can then be used in all the other
   * commands.
   * 
   * @param string $path
   * @param integer $relative
   * @return string
   * @throws \Exception
   */
  public function realpath($path, $relative = FileSystem::NONE);

  /**
   * Checks whether a temporary entry does exist
   *
   * @param string $path
   * @return boolean
   */
  public function exists($path);

  /**
   * Returns a temporary entry as an array
   *
   * @param string $path
   * @return array
   */
  public function file($path);

  /**
   * Returns the modification time of a temporary  entry
   *
   * @param string $path
   * @return boolean
   */
  public function modificationTime($path);

  /**
   * Writes data from a temporary entry
   *
   * @param string $path
   */
  public function read($path);

  /**
   * Writes data into a temporary entry
   *
   * @param string $path
   * @param string $data
   */
  public function write($path, $data);

  /**
   * Executes a command and saves the result into a temporary entry
   *
   * @param string $command
   * @param string $descriptor
   * @param string $destination
   */
  public function system($command, $descriptor, $destination);

  /**
   * Requires a file from the temporary directory
   *
   * @param string $path
   * @return boolean
   */
  public function requireFile($path);

  /**
   * Deletes the temporary directory
   */
  public function clean();

  /**
   * This function does not perform operations in the temporary
   * directory but it caches the results to avoid reprocessing
   *
   * @param string $algorithm
   * @param string $path
   * @param boolean $cache
   * @return string
   */
  public function getHashFile($algorithm, $path, $cache = false);

  /**
   * Enumerate Files in Input Path.
   * A callback function with the following prototype:
   * - function ($path)
   * -- returns 'true' to continue enumeration, 'false' to stop
   * -- throws \Exception on critical errors (exception will not be captured,
   *    but passed on to the caller)
   * 
   * @param function $callback Callback Function 
   * @return string
   * @throws \Exception
   */
  public function enumerateFiles($callback);
}
