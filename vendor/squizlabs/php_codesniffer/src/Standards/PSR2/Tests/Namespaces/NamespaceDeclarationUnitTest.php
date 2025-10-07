<?php

namespace PHP_CodeSniffer\Standards\PSR2\Tests\Namespaces;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the NamespaceDeclaration sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PSR2\Sniffs\Namespaces\NamespaceDeclarationSniff
 */
final class NamespaceDeclarationUnitTest extends AbstractSniffUnitTest {

  /**
   * Returns the lines where errors should occur.
   *
   * The key of the array should represent the line number and the value
   * should represent the number of errors that should occur on that line.
   *
   * @return array<int, int>
   */
  public function getErrorList() {
    return [
      6  => 1,
      9  => 1,
      17 => 1,
      19 => 1,
    ];

  }//end getErrorList()

  /**
   * Returns the lines where warnings should occur.
   *
   * The key of the array should represent the line number and the value
   * should represent the number of warnings that should occur on that line.
   *
   * @return array<int, int>
   */
  public function getWarningList() {
    return [];

  }//end getWarningList()

}//end class
