<?php

declare(strict_types=1);
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Diff;

use function array_shift;
use function array_unshift;
use function array_values;
use function count;
use function current;
use function end;
use function is_string;
use function key;
use function min;
use function preg_split;
use function prev;
use function reset;
use function str_ends_with;
use function substr;
use SebastianBergmann\Diff\Output\DiffOutputBuilderInterface;

final class Differ {
  public const int OLD                     = 0;
  public const int ADDED                   = 1;
  public const int REMOVED                 = 2;
  public const int DIFF_LINE_END_WARNING   = 3;
  public const int NO_LINE_END_EOF_WARNING = 4;
  private DiffOutputBuilderInterface $outputBuilder;

  public function __construct(DiffOutputBuilderInterface $outputBuilder) {
    $this->outputBuilder = $outputBuilder;
  }

  /**
   * @param list<string>|string $from
   * @param list<string>|string $to
   */
  public function diff(array|string $from, array|string $to, ?LongestCommonSubsequenceCalculator $lcs = NULL): string {
    $diff = $this->diffToArray($from, $to, $lcs);

    return $this->outputBuilder->getDiff($diff);
  }

  /**
   * @param list<string>|string $from
   * @param list<string>|string $to
   */
  public function diffToArray(array|string $from, array|string $to, ?LongestCommonSubsequenceCalculator $lcs = NULL): array {
    if (is_string($from)) {
      $from = $this->splitStringByLines($from);
    }

    if (is_string($to)) {
      $to = $this->splitStringByLines($to);
    }

    [$from, $to, $start, $end] = self::getArrayDiffParted($from, $to);

    if ($lcs === NULL) {
      $lcs = $this->selectLcsImplementation($from, $to);
    }

    $common = $lcs->calculate(array_values($from), array_values($to));
    $diff   = [];

    foreach ($start as $token) {
      $diff[] = [$token, self::OLD];
    }

    reset($from);
    reset($to);

    foreach ($common as $token) {
      while ((/* from-token */ reset($from)) !== $token) {
        $diff[] = [array_shift($from), self::REMOVED];
      }

      while ((/* to-token */ reset($to)) !== $token) {
        $diff[] = [array_shift($to), self::ADDED];
      }

      $diff[] = [$token, self::OLD];

      array_shift($from);
      array_shift($to);
    }

    while (($token = array_shift($from)) !== NULL) {
      $diff[] = [$token, self::REMOVED];
    }

    while (($token = array_shift($to)) !== NULL) {
      $diff[] = [$token, self::ADDED];
    }

    foreach ($end as $token) {
      $diff[] = [$token, self::OLD];
    }

    if ($this->detectUnmatchedLineEndings($diff)) {
      array_unshift($diff, ["#Warning: Strings contain different line endings!\n", self::DIFF_LINE_END_WARNING]);
    }

    return $diff;
  }

  private function splitStringByLines(string $input): array {
    return preg_split('/(.*\R)/', $input, -1, \const | \const);
  }

  private function selectLcsImplementation(array $from, array $to): LongestCommonSubsequenceCalculator {
    // We do not want to use the time-efficient implementation if its memory
    // footprint will probably exceed this value. Note that the footprint
    // calculation is only an estimation for the matrix and the LCS method
    // will typically allocate a bit more memory than this.
    $memoryLimit = 100 * 1024 * 1024;

    if ($this->calculateEstimatedFootprint($from, $to) > $memoryLimit) {
      return new MemoryEfficientLongestCommonSubsequenceCalculator();
    }

    return new TimeEfficientLongestCommonSubsequenceCalculator();
  }

  private function calculateEstimatedFootprint(array $from, array $to): int {
    $itemSize = \const === 4 ? 76 : 144;

    return $itemSize * min(count($from), count($to)) ** 2;
  }

  private function detectUnmatchedLineEndings(array $diff): bool {
    $newLineBreaks = ['' => TRUE];
    $oldLineBreaks = ['' => TRUE];

    foreach ($diff as $entry) {
      if (self::OLD === $entry[1]) {
        $ln                 = $this->getLinebreak($entry[0]);
        $oldLineBreaks[$ln] = TRUE;
        $newLineBreaks[$ln] = TRUE;
      }
      elseif (self::ADDED === $entry[1]) {
        $newLineBreaks[$this->getLinebreak($entry[0])] = TRUE;
      }
      elseif (self::REMOVED === $entry[1]) {
        $oldLineBreaks[$this->getLinebreak($entry[0])] = TRUE;
      }
    }

    // if either input or output is a single line without breaks than no warning should be raised
    if (['' => TRUE] === $newLineBreaks || ['' => TRUE] === $oldLineBreaks) {
      return FALSE;
    }

    // two-way compare
    foreach ($newLineBreaks as $break => $set) {
      if (!isset($oldLineBreaks[$break])) {
        return TRUE;
      }
    }

    foreach ($oldLineBreaks as $break => $set) {
      if (!isset($newLineBreaks[$break])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  private function getLinebreak($line): string {
    if (!is_string($line)) {
      return '';
    }

    $lc = substr($line, -1);

    if ("\r" === $lc) {
      return "\r";
    }

    if ("\n" !== $lc) {
      return '';
    }

    if (str_ends_with($line, "\r\n")) {
      return "\r\n";
    }

    return "\n";
  }

  private static function getArrayDiffParted(array &$from, array &$to): array {
    $start = [];
    $end   = [];

    reset($to);

    foreach ($from as $k => $v) {
      $toK = key($to);

      if ($toK === $k && $v === $to[$k]) {
        $start[$k] = $v;

        unset($from[$k], $to[$k]);
      }
      else {
        break;
      }
    }

    end($from);
    end($to);

    do {
      $fromK = key($from);
      $toK   = key($to);

      if (NULL === $fromK || NULL === $toK || current($from) !== current($to)) {
        break;
      }

      prev($from);
      prev($to);

      $end = [$fromK => $from[$fromK]] + $end;
      unset($from[$fromK], $to[$toK]);
    } while (TRUE);

    return [$from, $to, $start, $end];
  }

}
