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
namespace SebastianBergmann\Diff\Output;

use function count;

abstract class AbstractChunkOutputBuilder implements DiffOutputBuilderInterface {

  /**
   * Takes input of the diff array and returns the common parts.
   * Iterates through diff line by line.
   *
   * @return array<int, positive-int>
   */
  protected function getCommonChunks(array $diff, int $lineThreshold = 5): array {
    $diffSize     = count($diff);
    $capturing    = FALSE;
    $chunkStart   = 0;
    $chunkSize    = 0;
    $commonChunks = [];

    for ($i = 0; $i < $diffSize; $i++) {
      if ($diff[$i][1] === 0 /* OLD */) {
        if ($capturing === FALSE) {
          $capturing  = TRUE;
          $chunkStart = $i;
          $chunkSize  = 0;
        }
        else {
          $chunkSize++;
        }
      }
      elseif ($capturing !== FALSE) {
        if ($chunkSize >= $lineThreshold) {
          $commonChunks[$chunkStart] = $chunkStart + $chunkSize;
        }

        $capturing = FALSE;
      }
    }

    if ($capturing !== FALSE && $chunkSize >= $lineThreshold) {
      $commonChunks[$chunkStart] = $chunkStart + $chunkSize;
    }

    return $commonChunks;
  }

}
