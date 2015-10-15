<?php

/*
  +--------------------------------------------------------------------------+
  | ZEP to PHP Translator                                                    |
  +--------------------------------------------------------------------------+
  | Copyright (c) 2015 pt ar sourcenotes.org                                 |
  +--------------------------------------------------------------------------+
  | This source file is subject the MIT license, that is bundled with        |
  | this package in the file LICENSE, and is available through the           |
  | world-wide-web at the following url:                                     |
  | https://opensource.org/licenses/MIT                                      |
  +--------------------------------------------------------------------------+
 */

namespace ZEPtoPHP;

use ZEPtoPHP\Base\FileSystem as IFileSystem;
use ZEPtoPHP\Base\Compiler as ICompiler;
use ZEPtoPHP\Base\Stage as IStage;

/**
 * PHP Code Emitter (i.e. translate ZEP to PHP in ZEPHIR Context)
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class Compiler implements ICompiler {

  // Mixins
  use \ZEPtoPHP\Base\Mixins\DI,
      \ZEPtoPHP\Base\Mixins\CompilerStages;

  /**
   * Initialize the Compiler Instance
   * 
   * @return self Return instance of compiler for Function Linking.
   */
  public function initialize() {
    $di = $this->getDI();

    // Do we have a List of Compiler Stages in the DI?
    if ($di->has('compiler-stages')) { // YES
      $stages = $di['compiler-stages'];
      // Build the Stage List for the Compiler
      foreach ($stages as $index => $stage) {
        // Is the Stage Defined as Class Name?
        if (is_string($stage)) { // YES
          $stage = new $stage;
        }

        // Do we have a Stage Instance?
        if (is_object($stage) && ($stage instanceof IStage)) { // YES
          $stage->setDI($di);
          $stage->initialize();
          $this->addStage($stage);
          continue;
        }
        // ELSE: Not a Valid Stage Instance - Abort
        throw new \Exception('Invalid Stage Definition');
      }
    }

    return $this;
  }

  /**
   * Emit Code for all Files in a Project
   * 
   * @param type $path
   * @throws \Exception
   */
  public function project($path) {
    
  }

  /**
   * Emit Code for a Single File
   * 
   * @param mixed $path
   * @throws \Exception
   */
  public function files($files) {
    if (isset($files)) {
      if (is_string($files)) {
        $path = trim($files);
        if (strlen($path) === 0) {
          throw new \Exception("Source Path is empty");
        }

        // Get File System from Dependency Injector
        $fs = $this->getDI()['fileSystem'];

        // Emite PHP Code for all the ZEP files in the Directory
        $compiler = $this;
        $fs->enumerateFiles(function($path) use($compiler) {
          $count_path = strlen($path);
          $extension = $count_path > 4 ? strtolower(substr($path, $count_path - 4)) : null;
          if (isset($extension) && ($extension === '.zep')) {
            echo "** FILE [{$path}] **\n";
            $compiler->file($path);
          }
          return true;
        });
      }
    }
  }

  /**
   * Emit Code for a Single File
   * 
   * @param type $path
   * @throws \Exception
   */
  public function file($path) {
    $fs = $this->getDI()['fileSystem'];

    // Generate IR for File
    $phpFile = $this->_genIR($path);
    $ast = $fs->requireFile($phpFile);

    // Was there a Problem Parsing the File?
    if (isset($ast['type']) && ($ast['type'] === 'error')) { // YES
      throw new \Exception("ERROR: {$ast['message']} in File[{$ast['file']}]");
    }

    // Initialize the Code Emitter
    $_emitter = $this->getDI()->getShared('emitter');
    $_emitter->start(str_replace('.zep', '.php', $path));

    // Run the Stages for the Compiler
    $stages = $this->getStages();
    foreach ($stages as $index => $stage) {
      // Reset the Stage for processing a new file
      $stage->reset();
      // Compile the AST
      $ast = $stage->compile($ast);
    }

    // Finish Emitter
    $_emitter->end();

    return $ast;
  }

  /**
   * Compiles the file generating a JSON intermediate representation
   *
   * @param Compiler $compiler
   * @return array
   */
  protected function _genIR($zepFile) {
    // Get File System from Dependency Injector
    $fs = $this->getDI()['fileSystem'];

    // Does the ZEP File Exist?
    $zepRealPath = $fs->realpath($zepFile, IFileSystem::INPUT);
    if (!$fs->exists($zepRealPath)) { // NO
      throw new \Exception("Source File [{$zepRealPath}] doesn't exist");
    }

    // Create Normalized File Paths for the Parse Results
    $compilePath = $this->_compilePath($zepRealPath);
    $compilePathJS = $compilePath . ".js";
    $compilePathPHP = $compilePath . ".php";

    // Create Path to Zephir Binary
    if (PHP_OS == "WINNT") {
      $zephirParserBinary = $fs->realpath(BINPATH . 'zephir-parser.exe', IFileSystem::SYSTEM);
    } else {
      $zephirParserBinary = $fs->realpath(BINPATH . 'zephir-parser', IFileSystem::SYSTEM);
    }

    // Does it Exist?
    if (!$fs->exists($zephirParserBinary)) { // NO
      throw new \Exception($zephirParserBinary . ' was not found');
    }

    $changed = false;

    // Has the ZEP File already been Parsed (intermediate file JS exists)?
    if ($fs->exists($compilePathJS)) { // YES
      // Is it Older than the Source ZEP File, OR, are we using a New ZEP Parser?
      $modificationTime = $fs->modificationTime($compilePathJS);
      if ($modificationTime < $fs->modificationTime($zepRealPath) || $modificationTime < $fs->modificationTime($zephirParserBinary)) { // YES
        // Reparse the File
        $fs->system($zephirParserBinary . ' ' . $zepRealPath, 'stdout', $compilePathJS);
        $changed = true;
      }
    } else { // NO : Parse the ZEP File
      $fs->system($zephirParserBinary . ' ' . $zepRealPath, 'stdout', $compilePathJS);
      $changed = true;
    }

    // Do we have a new Parsed Intermediate File (JS)?
    if ($changed || !$fs->exists($compilePathPHP)) { // YES: Try to build the Final PHP Result
      // Is the Intermediate JS Valid?
      $json = json_decode($fs->read($compilePathJS), true);
      if (!isset($json)) { // NO
        // TODO : $fs->delete($zepRealPath);
        throw new \Exception("Failed to Parse the ZEP File [{$zepRealPath}]");
      }
      $data = '<?php return ' . var_export($json, true) . ';';
      $fs->write($compilePathPHP, $data);
    }

    return $compilePathPHP;
  }

  /**
   * 
   * @param type $realPath
   * @return string
   */
  protected function _compilePath($realPath) {
    // Get File System from Dependency Injector
    $fs = $this->_di['fileSystem'];

    // Produce a Base Output File Name for the Given Name
    $normalizedPath = str_replace(array(DIRECTORY_SEPARATOR, ":", '/'), '_', $realPath);
    $compilePath = $fs->realpath($normalizedPath, IFileSystem::CACHE);
    return $compilePath;
  }

}
