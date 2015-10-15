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

/**
 * HardDisk
 *
 * Uses the standard hard-disk as filesystem for temporary operations
 */
class HardDisk extends FileSystemAbstract {

  protected $systemPath = null;
  protected $inputPath = null;
  protected $outputPath = null;
  protected $tmpPath = null;

  /**
   * HardDisk constructor
   *
   * @param string $basePath
   */
  public function __construct($path = '.') {
    return $this->_setPath('systemPath', $path);
  }

  public function setSystemPath($path = '.') {
    return $this->_setPath('systemPath', $path);
  }

  public function setInputPath($path = '.') {
    return $this->_setPath('inputPath', $path);
  }

  public function setOutputPath($path = '.') {
    return $this->_setPath('outputPath', $path, true);
  }

  public function setCachePath($path = '.') {
    return $this->_setPath('cachePath', $path, true);
  }

  public function setTempPath($path) {
    if (isset($path)) {
      if (!is_string($path)) {
        throw new \Exception("Invalid Value for Path [{$property}]");
      }

      $path = trim($path);
      $path = strlen($path) ? $path : null;
    }

    $path = isset($path) ? $path : (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zephir');
    return $this->_setPath('tmpPath', $path, true);
  }

  /**
   * 
   * @param string $path
   * @param integer $relative
   * @return string
   * @throws \Exception
   */
  public function realpath($path, $relative = FileSystemAbstract::NONE) {
    // Is the Filesytem Initialized?
    if (!$this->isInitialized()) { // NO: Initialize before use...
      throw new \Exception('File System has not been initialize');
    }

    // Is $path a string?
    if (isset($path) && is_string($path)) { // YES
      // Is $path an empty string?
      $path = trim($path);
      if (strlen($path) === 0) { // YES
        $path = null;
      }
    } else {
      $path = null;
    }

    // Is $path set?
    if (isset($path)) { // YES
      // Is it a relative path?      
      if ($this->_isRelative($path)) { // YES
        // Create a NON-RELATIVE path based on the requested Path Space
        switch ($relative) {
          case FileSystemAbstract::SYSTEM:
            $path = $this->systemPath . DIRECTORY_SEPARATOR . $path;
            break;
          case FileSystemAbstract::INPUT:
            $path = $this->inputPath . DIRECTORY_SEPARATOR . $path;
            break;
          case FileSystemAbstract::OUTPUT:
            $path = $this->outputPath . DIRECTORY_SEPARATOR . $path;
            break;
          case FileSystemAbstract::CACHE:
            $path = $this->cachePath . DIRECTORY_SEPARATOR . $path;
            break;
          case FileSystemAbstract::TEMP:
            $path = $this->tmpPath . DIRECTORY_SEPARATOR . $path;
            break;
          default:
            $path = realpath($path);
        }
      }
    }

    return $path;
  }

  /**
   * Checks whether a temporary entry does exist
   *
   * @param string $path
   * @return boolean
   */
  public function exists($path) {
    return file_exists($path);
  }

  /**
   * Returns a temporary entry as an array
   *
   * @param string $path
   * @return array
   */
  public function file($path) {
    return exists($path) ? file($path) : null;
  }

  /**
   * Returns the modification time of a temporary  entry
   *
   * @param string $path
   * @return boolean
   */
  public function modificationTime($path) {
    return filemtime($path);
  }

  /**
   * Writes data from a temporary entry
   *
   * @param string $path
   */
  public function read($path) {
    return file_get_contents($path);
  }

  /**
   * Writes data into a temporary entry
   *
   * @param string $path
   * @param string $data
   */
  public function write($path, $data) {
    $basepath = dirname($path);
    if (!is_dir($basepath)) {
      if (mkdir($basepath, 0777, true) === FALSE) {
        throw new \Exception("Failed to create base directory [{$basepath}] for file write.");
      }
    }
    return file_put_contents($path, $data);
  }

  /**
   * Executes a command and saves the result into a temporary entry
   *
   * @param string $command
   * @param string $descriptor
   * @param string $destination
   */
  public function system($command, $descriptor, $destination) {
    switch ($descriptor) {
      case 'stdout':
        $result = system($command . ' > ' . $destination, $rvalue);
        break;
      case 'stderr':
        $result = system($command . ' 2> ' . $destination, $rvalue);
        break;
    }
  }

  /**
   * Requires a file from the temporary directory
   *
   * @param string $path
   * @return boolean
   */
  public function requireFile($path) {
    return require $path;
  }

  /**
   * Deletes the temporary directory
   */
  public function clean() {
    // Clean Temporary Files
    if (is_dir($this->tmpPath)) {
      $objects = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($this->tmpPath), \RecursiveIteratorIterator::SELF_FIRST
      );
      foreach ($objects as $name => $object) {
        if (!$object->isDir()) {
          @unlink($name);
        }
      }
    }

    // Clean Cache Files
    if (is_dir($this->cachePath)) {
      $objects = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($this->cachePath), \RecursiveIteratorIterator::SELF_FIRST
      );
      foreach ($objects as $name => $object) {
        if (!$object->isDir()) {
          @unlink($name);
        }
      }
    }
  }

  /**
   * This function does not perform operations in the temporary
   * directory but it caches the results to avoid reprocessing
   *
   * @param string $algorithm
   * @param string $path
   * @param boolean $cache
   * @return string
   */
  public function getHashFile($algorithm, $path, $cache = false) {
    if ($cache == false) {
      return hash_file($algorithm, $path);
    } else {
      $changed = false;
      $cacheFile = $this->basePath . str_replace(array(DIRECTORY_SEPARATOR, ':', '/'), '_', $path) . '.md5';
      if (!file_exists($cacheFile)) {
        $hash = hash_file($algorithm, $path);
        file_put_contents($cacheFile, $hash);
        $changed = true;
      } else {
        if (filemtime($path) > filemtime($cacheFile)) {
          $hash = hash_file($algorithm, $path);
          file_put_contents($cacheFile, $hash);
          $changed = true;
        }
      }

      if (!$changed) {
        return file_get_contents($cacheFile);
      }

      return $hash;
    }
  }

  /**
   * Initialize the filesystem
   */
  protected function _initialize() {
    if (!isset($this->inputPath)) {
      $this->inputPath = isset($this->outputPath) ? $this->outputPath : $this->systemPath;
    }
    if (!isset($this->outputPath)) {
      $this->outputPath = isset($this->inputPath) ? $this->inputPath : $this->systemPath;
    }
    if (!isset($this->systemPath)) {
      $this->systemPath = isset($this->inputPath) ? $this->inputPath : $this->outputPath;
    }
    if (!isset($this->tmpPath)) {
      $this->_setPath('tmpPath', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zephir', true);
    }
    if (!isset($this->cachePath)) {
      $this->cachePath = $this->tmpPath;
    }

    return isset($this->systemPath) && isset($this->tmpPath);
  }

  /**
   * 
   * @param type $path
   * @return type
   */
  protected function _isRelative($path) {
    if (PHP_OS == "WINNT") {
      return preg_match('/^(?:\\\\.+|[a-z]:(?:\\|\/).*)$/i', $path) == FALSE;
    } else {
      return $path{0} !== '/';
    }
  }

  /**
   * 
   * @param type $property
   * @param type $path
   * @return type
   */
  protected function _setPath($property, $path, $create = false) {
    if (!isset($path) || !is_string($path)) {
      throw new \Exception("Invalid Value for Path [{$property}]");
    }

    $path = trim($path);
    if (strlen($path) === 0) {
      throw new \Exception("Value for Path [{$property}] is empty");
    }

    if (file_exists($path)) {
      if (!is_dir($path)) {
        throw new \Exception("Path [{$path}] already exists, BUT, is not a directory");
      }
    } else if ($create) {
      if (!mkdir($path, 0750, true)) {
        throw new \Exception("Unable to create [{$path}]");
      }
    } else {
      throw new \Exception("Path [{$path}] does not exist");
    }

    $path = realpath($path);
    $this->$property = $path;
    return $path;
  }

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
  public function enumerateFiles($callback) {
    // Is the Filesytem Initialized?
    if (!$this->isInitialized()) { // NO: Initialize before use...
      throw new \Exception('File System has not been initialize');
    }

    if (!isset($callback) || !is_callable($callback)) {
      throw new \Exception('Missing or Invalid Callback Function');
    }

    // Create an Iterator for the Input Path
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->inputPath), \RecursiveIteratorIterator::SELF_FIRST
    );

    // Iterate Files in Directory
    $break = false;
    $inputPathLength = strlen($this->inputPath) + 1;
    foreach ($iterator as $name => $element) {
      if ($element->isFile() && $element->isReadable()) {
        // Return Relative Path Only
        $name = substr($name, $inputPathLength);
        $break = !$callback($name);
        if ($break) {
          break;
        }
      }
    }
  }

}
