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

// Default Code Emitter Settings
return [
    'indent' => false,
    'indent.tabs' => false,
    'indent.spaces' => 2,
    'indent.max_levels' => 20,
    // Leading and Trailing Spaces
    'spaces.{' => FALSE,
    'spaces.{.before.block' => TRUE,
    'spaces.{.before.block.for' => TRUE,
    'spaces.(' => FALSE,
    'spaces.(.before' => FALSE,
    'spaces.(.after' => FALSE,
    'spaces.(.before.parameters' => TRUE,
    'spaces.(.after.parameters' => TRUE,
    'spaces.)' => FALSE,
    'spaces.).before' => FALSE,
    'spaces.).after' => FALSE,
    'spaces.).before.parameters' => TRUE,
    'spaces.).after.parameters' => TRUE,
    'spaces.,' => FALSE,
    'spaces.,.before' => FALSE,
    'spaces.,.after' => FALSE,
    'spaces.,.after.parameters' => TRUE,
    // New Lines
    'newlines.{' => FALSE,
    'newlines.{.before.block' => FALSE,
    'newlines.{.after.block' => TRUE,
//    'newlines.{.after.block.for' => TRUE,
    'newlines.}' => FALSE,
    'newlines.}.before' => TRUE,
    'newlines.}.after' => FALSE,
    'newlines.}.before.block.for' => TRUE,
    'newlines.}.after.block.for' => TRUE,
    'newlines.}.after.block.if' => TRUE,
    'newlines.}.after.block.else' => TRUE,
    'newlines.}.after.block.else.if' => FALSE,
    'newlines.}.after.block.switch' => TRUE,
    'newlines.;' => FALSE,
    'newlines.;.after.statement' => TRUE,
    'newlines.block.method' => false,
//    'newline.block.method.before' => false,
//    'newline.block.method.after' => false,
    'newlines.class' => false,
//    'newline.class.block.before' => false,
//    'newline.class.block.after' => false,
    'newlines.class.section' => true,
    'newlines.comment' => false,
//    'newline.comment.before' => true,
//    'newline.comment.after' => true
    'newlines.function' => false,
//    'newline.function.parameters' => false,
    'newlines.method' => false,
//    'newline.method.parameters' => false,
    'newlines.use' => false,
//    'newline.use.entries' => false,
  ];
