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

// Initialize Autoloader
require_once __DIR__ . '/bootstrap.php';

// Include Zephir Extras
require_once BASEPATH . 'zephir-base/zephir_builtin.php';
require_once BASEPATH . 'zephir-base/zephir_extras.php';

// Include Required Class
use GetOptionKit\OptionCollection;
use GetOptionKit\OptionParser;
use GetOptionKit\OptionPrinter\ConsoleOptionPrinter;
use ZEPtoPHP\DI;

// Create a DI Container
$di = new DI;

// Initialize the DI
$di->set("fileSystem", "\ZEPtoPHP\Base\FileSystem\HardDisk", true);
$di->setShared('emitter', "\ZEPtoPHP\Emitters\File");
//$di->setShared('emitter', "\ZEPtoPHP\Emitters\Console");

// Define Command Line Options
$specs = new OptionCollection;
// Output Directory (STRING Optional - DEFAULT output goes to ./output)
$specs->add('o|output?', 'Output directory.')
  ->isa('String');

// Cache Directory (STRING Optional - DEFAULT cache goes to ./cache)
$specs->add('c|cache?', 'Cache directory.')
  ->isa('String');

// Temporary Directory (STRING Optional - DEFAULT output goes to system temporary directory\zephir)
$specs->add('t|tmp?', 'Temporary directory.')
  ->isa('String');

// Verbose Output (FLAG Option)
$specs->add('v', 'verbose');

// Parse Command Line
$parser = new OptionParser($specs);
try {
  $result = $parser->parse($argv);
} catch (Exception $e) {
  echo $e->getMessage();

  // Output Command Line Options
  echo "\nCommand Line Options:\n";
  $printer = new ConsoleOptionPrinter;
  echo $printer->render($specs);
  return 1;
}

// Display Command Line Options Used
echo "Enabled options:\n";
foreach ($result as $key => $spec) {
  echo $spec . "\n";
}

// Display Extra Arguments
echo "Extra Arguments:\n";
$arguments = $result->getArguments();
for ($i = 1; $i < count($arguments); $i++) {
  echo $arguments[$i] . "\n";
}

if (count($arguments) < 2) {
  echo "Missing Source [File or Directory]";
  return 2;
}

// Initialize File System
$fs = $di['fileSystem'];

// Set Output Directory
$output_dir = $result->output;
$fs->setOutputPath(isset($output_dir) && is_dir($output_dir) ? $output_dir : BASEPATH . 'output');

// Set Cache Directory
$cache_dir = $result->cache;
$fs->setCachePath(isset($cache_dir) && is_dir($cache_dir) ? $cache_dir : BASEPATH . 'cache');

$tmp_dir = $result->tmp;
if (isset($tmp_dir) && is_dir($tmp_dir)) {
  $fs->setTempPath($output_dir);
}

$input_dir = null;
$input_file = null;
$input = $arguments[1];
if (file_exists($input)) {
  if (is_file($input)) {
    echo "Source is File [{$input}]\n";
    if (!is_readable($input)) {
      echo "Source [{$input}] is not Readable by the Current User";
      return 3;
    }
    $input_file = basename($input);
    $input_dir = dirname($input);
    $fs->setInputPath($input_dir);
  } else {
    echo "Source is Dir [{$input}]\n";
    $input_dir = $input;
    $fs->setInputPath($input);
  }
} else {
  echo "Invalid Source [{$input}]\n";
  return 2;
}

$fs->initialize();

/*
echo "Current Working Directory [" . BASEPATH . "]\n";
echo "Input Directory [{$input_dir}]\n";
if (isset($input_file)) {
  echo "Input File [{$input_file}]\n";
}
echo "Output Directory [{$output_dir}]\n";
echo "Cache Directory [{$cache_dir}]\n";
echo "Temporary Directory [{$tmp_dir}]\n";
*/

$di->set("compiler", "\ZEPtoPHP\Compiler", true);
$di->set("compiler-stages", function() {
  return [
    "\ZEPtoPHP\Stages\Compact"
    , "\ZEPtoPHP\Stages\Process"
    , "\ZEPtoPHP\Stages\EmitCode"
  ];
}, true);

// Initialize the Compiler
$compiler = $di['compiler'];
$compiler->initialize();

// Are we parsing a Single File?
if (isset($input_file)) { // YES
//  var_dump($compiler->file($input_file));
  $compiler->file($input_file);
} else { // NO: Parsing Entire Directory
  $compiler->files($input_dir);
}
