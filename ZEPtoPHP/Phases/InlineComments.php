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

namespace ZEPtoPHP\Phases;

use ZEPtoPHP\Base\Phase as IPhase;

/**
 * Normalizes the IR Ast to make for easier parsing
 * 
 * @author Paulo Ferreira <pf at sourcenotes.org>
 */
class InlineComments implements IPhase {

  // Mixins
  use \ZEPtoPHP\Base\Mixins\DI;

  protected $_comments = [];

  /**
   * Process the AST
   * 
   * @param array $ast AST to be processed
   * @return array Old or Transformed AST
   */
  public function top($ast) {
    // Are we dealing with a Comment?
    if ($ast['type'] === 'comment') { // YES
      // Add Comment to Pending Document Blocks
      $comment = $this->_processComment($ast);
      if (isset($comment)) {
        $this->_comments[] = $comment;
      }
      $ast = null;
    }

    // Do we have a Statement?
    if (isset($ast)) { // YES
      // Do we have Comment Blocks for the Statement?
      if (count($this->_comments)) { // YES: Merge it into this statement
        $entry['docblock'] = $this->_comments;
        $this->_comments = [];
      }
    }

    // Are we dealing with a top level function?
    if ($ast['type'] === 'function') { // YES: Cleanup it's comments
      $ast['statements'] = $this->_processStatementBlock($ast['statements']);
    }

    return $ast;
  }

  /**
   * Process Class or Interface Property
   * 
   * @param array $class Class Definition
   * @param array $property Class Property Definition
   * @return array New Property Definition, 'NULL' if to be removed
   */
  public function constant(&$class, $constant) {
    // Does the Constant have a Documentation Block?
    if (isset($constant['docblock'])) { // YES: Normalize it
      $docblock = $this->_commentToDocBlock($constant['docblock']);

      if (isset($docblock)) {
        // Add Missing Properties
        $docblock['file'] = $constant['file'];
        $docblock['line'] = $constant['line'];
        $docblock['char'] = $constant['char'];

        // Modify the Constant Document Block
        $constant['docblock'] = [$docblock];
      } else {
        unset($constant['docblock']);
      }
    }

    return $constant;
  }

  /**
   * Process Class or Interface Property
   * 
   * @param array $class Class Definition
   * @param array $property Class Property Definition
   * @return array New Property Definition, 'NULL' if to be removed
   */
  public function property(&$class, $property) {
    // Does the Property have a Documentation Block?
    if (isset($property['docblock'])) { // YES: Normalize it
      $docblock = $this->_commentToDocBlock($property['docblock']);

      if (isset($docblock)) {
        // Add Missing Properties
        $docblock['file'] = $property['file'];
        $docblock['line'] = $property['line'];
        $docblock['char'] = $property['char'];

        // Modify the Property Document Block
        $property['docblock'] = [$docblock];
      } else {
        unset($property['docblock']);
      }
    }

    return $property;
  }

  /**
   * Process Class or Interface Method
   * 
   * @param array $class Class Definition
   * @param array $method Class Method Definition
   * @return array New Method Definition, 'NULL' if to be removed
   */
  public function method(&$class, $method) {
    // Does the Method have a Documentation Block?
    if (isset($property['docblock'])) { // YES: Normalize it
      $docblock = $this->_commentToDocBlock($method['docblock']);

      if (isset($docblock)) {
        // Add Missing Properties
        $docblock['file'] = $method['file'];
        $docblock['line'] = $method['line'];
        $docblock['char'] = $method['char'];

        // Modify the Property Document Block
        $method['docblock'] = [$docblock];
      } else {
        unset($method['docblock']);
      }
    }

    $method['statements'] = $this->_processStatementBlock($method['statements']);
    return $method;
  }

  protected function _processStatementBlock($block) {
    /* TODO Handle Trailing comments 
     * i.e. comments that come after all the statements in a block.
     * 
     * example:
     * loop 
     * {
     *   a +=1;
     *   // Trailing Comment
     * }
     * 
     * Currently these comments are dropped
     */
    $this->_comments = [];

    // Process Statement Block
    $statements = [];
    foreach ($block as $statement) {
      $type = $statement['type'];

      // Is the Statement a Comment?
      if ($type === 'comment') { // YES: Add it to list of pending  comments
        $comment = $this->_processComment($statement);
        if (isset($comment)) {
          $this->_comments[] = $comment;
        }
        continue;
      }

      // Do we have Comment Blocks for the Statement?
      if (count($this->_comments)) { // YES: Merge it into this statement
        $statement['docblock'] = $this->_comments;
      }

      // For Complex Statements (i.e. statements with statement blocks)
      switch ($type) {
        case 'for':
        case 'loop':
        case 'while':
        case 'doWhile':
          if (isset($statement['statements'])) {
            $statement['statements'] = $this->_processStatementBlock($statement['statements']);
          }
          /* TODO Handle Trailing comments 
           * i.e. comments that come after all the statements in a block.
           * 
           * example:
           * loop 
           * {
           *   a +=1;
           *   // Trailing Comment
           * }
           * 
           * Currently these comments are dropped
           */
          break;
        case 'if':
          // Process If (TRUE) block
          $statement['statements'] = $this->_processStatementBlock($statement['statements']);
          // Process If (OTHER CONDITIONS) block
          if (isset($statement['elseif_statements'])) {
            $elseifs = $statement['elseif_statements'];
            foreach ($elseifs as &$elseif) {
              $elseif['statements'] = $this->_processStatementBlock($elseif['statements']);
            }
            $statement['elseif_statements'] = $elseifs;
          }
          // Process If (FALSE) block
          if (isset($statement['else_statements'])) {
            $statement['else_statements'] = $this->_processStatementBlock($statement['else_statements']);
          }
          break;
        case 'switch':
          $clauses = $statement['clauses'];
          foreach ($clauses as &$clause) {
            $clause['statements'] = $this->_processStatementBlock($clause['statements']);
          }
          $statement['clauses'] = $clauses;
          break;
      }

      $statements[] = $statement;
    }

    return $statements;
  }

  /**
   * Converts a Comment AST Entry into a Comment Block Entry, to be merged into
   * the AST of the Next Statement Block
   * 
   * @param array $ast Comment AST
   * @return array Comment Block Entry or Null, if an empty comment
   */
  protected function _processComment($ast) {
    /* TODO Modify Lexer/Parser to distinguish among the comment types */

    // Convert AST to Document Block
    $docblock = $this->_commentToDocBlock($ast['value']);

    if (isset($docblock)) {
      $docblock['file'] = $ast['file'];
      $docblock['line'] = $ast['line'];
      $docblock['char'] = $ast['char'];
    }

    return $docblock;
  }

  protected function _commentToDocBlock($value) {
    // Extract Comment Lines
    $lines = explode("\n", $value);

    // Type of Comments?
    $multi_line = false;
    $doc_comment = strlen($lines[0]) ?
      ($lines[0][0] === '*' ? true : false) : false;
    if (count($lines) > 1) { // YES
      $multi_line = true;
    }

    // Are we dealing with a Document Comment?
    if ($doc_comment) { // YES: Remove Leading '*'
      $lines[0] = (strlen($lines[0]) > 1) ? substr($lines[0], 1) : '';
    }
    // Trim the Lines, now 
    /* NOTE: We didn't do this before, because, we wan't to be able to distinguish
     * between /* , /* * and also // * which would produce a resultant, trimmed
     * token of '*...' the same as if it was /**
     */
    $lines = array_map(function($e) {
      return trim($e);
    }, $lines);

    $trim_stars = true;
    // Do we want to trim leadin and trailing '*'?
    if ($trim_stars) { // YES
      $lines = array_map(function($e) {
        $length = strlen($e);
        if ($length && ($e[0] === '*')) {
          $e = $length === 1 ? '' : trim(substr($e, 1));
          $length = strlen($e);
        }

        if ($length && ($e[$length - 1] === '*')) {
          $e = $length === 1 ? '' : trim(substr($e, 0, $length - 1));
        }

        return $e;
      }, $lines);
    }

    // Check if Comment is Empty
    $not_empty = false;
    for ($i = 0; $i < count($lines); $i++) {
      if (strlen($lines[$i])) {
        $not_empty = true;
        break;
      }
    }

    // Are we dealing with an empty comment?
    if (isset($lines) && count($lines) && $not_empty) { // YES
      // Add Comment - Taking into Account Single or Multiple Lines
      $docblock = [
        'lines' => (count($lines) === 1 ? $lines[0] : $lines),
        'multi-line' => $multi_line,
        'document' => $doc_comment
      ];
      return $docblock;
    }

    return null;
  }

}
