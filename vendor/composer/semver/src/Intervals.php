<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Semver;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MatchAllConstraint;
use Composer\Semver\Constraint\MatchNoneConstraint;
use Composer\Semver\Constraint\MultiConstraint;

/**
 * Helper class generating intervals from constraints.
 *
 * This contains utilities for:
 *
 *  - compacting an existing constraint which can be used to combine several into one
 * by creating a MultiConstraint out of the many constraints you have.
 *
 *  - checking whether one subset is a subset of another.
 *
 * Note: You should call clear to free memoization memory  usage when you are done using this class
 */
class Intervals {
  /**
   * @phpstan-var array<string, array{'numeric': Interval[], 'branches': array{'names': string[], 'exclude': bool}}>
   */
  private static $intervalsCache = [];

  /**
   * @phpstan-var array<string, int>
   */
  private static $opSortOrder = [
    '>=' => -3,
    '<' => -2,
    '>' => 2,
    '<=' => 3,
  ];

  /**
   * Clears the memoization cache once you are done.
   *
   * @return void
   */
  public static function clear() {
    self::$intervalsCache = [];
  }

  /**
   * Checks whether $candidate is a subset of $constraint.
   *
   * @return bool
   */
  public static function isSubsetOf(ConstraintInterface $candidate, ConstraintInterface $constraint) {
    if ($constraint instanceof MatchAllConstraint) {
      return TRUE;
    }

    if ($candidate instanceof MatchNoneConstraint || $constraint instanceof MatchNoneConstraint) {
      return FALSE;
    }

    $intersectionIntervals = self::get(new MultiConstraint([$candidate, $constraint], TRUE));
    $candidateIntervals = self::get($candidate);
    if (\count($intersectionIntervals['numeric']) !== \count($candidateIntervals['numeric'])) {
      return FALSE;
    }

    foreach ($intersectionIntervals['numeric'] as $index => $interval) {
      if (!isset($candidateIntervals['numeric'][$index])) {
        return FALSE;
      }

      if ((string) $candidateIntervals['numeric'][$index]->getStart() !== (string) $interval->getStart()) {
        return FALSE;
      }

      if ((string) $candidateIntervals['numeric'][$index]->getEnd() !== (string) $interval->getEnd()) {
        return FALSE;
      }
    }

    if ($intersectionIntervals['branches']['exclude'] !== $candidateIntervals['branches']['exclude']) {
      return FALSE;
    }
    if (\count($intersectionIntervals['branches']['names']) !== \count($candidateIntervals['branches']['names'])) {
      return FALSE;
    }
    foreach ($intersectionIntervals['branches']['names'] as $index => $name) {
      if ($name !== $candidateIntervals['branches']['names'][$index]) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Checks whether $a and $b have any intersection, equivalent to $a->matches($b)
   *
   * @return bool
   */
  public static function haveIntersections(ConstraintInterface $a, ConstraintInterface $b) {
    if ($a instanceof MatchAllConstraint || $b instanceof MatchAllConstraint) {
      return TRUE;
    }

    if ($a instanceof MatchNoneConstraint || $b instanceof MatchNoneConstraint) {
      return FALSE;
    }

    $intersectionIntervals = self::generateIntervals(new MultiConstraint([$a, $b], TRUE), TRUE);

    return \count($intersectionIntervals['numeric']) > 0 || $intersectionIntervals['branches']['exclude'] || \count($intersectionIntervals['branches']['names']) > 0;
  }

  /**
   * Attempts to optimize a MultiConstraint.
   *
   * When merging MultiConstraints together they can get very large, this will
   * compact it by looking at the real intervals covered by all the constraints
   * and then creates a new constraint containing only the smallest amount of rules
   * to match the same intervals.
   *
   * @return \Composer\Semver\Constraint\ConstraintInterface
   */
  public static function compactConstraint(ConstraintInterface $constraint) {
    if (!$constraint instanceof MultiConstraint) {
      return $constraint;
    }

    $intervals = self::generateIntervals($constraint);
    $constraints = [];
    $hasNumericMatchAll = FALSE;

    if (\count($intervals['numeric']) === 1 && (string) $intervals['numeric'][0]->getStart() === (string) Interval::fromZero() && (string) $intervals['numeric'][0]->getEnd() === (string) Interval::untilPositiveInfinity()) {
      $constraints[] = $intervals['numeric'][0]->getStart();
      $hasNumericMatchAll = TRUE;
    }
    else {
      $unEqualConstraints = [];
      for ($i = 0, $count = \count($intervals['numeric']); $i < $count; $i++) {
        $interval = $intervals['numeric'][$i];

        // if current interval ends with < N and next interval begins with > N we can swap this out for != N
        // but this needs to happen as a conjunctive expression together with the start of the current interval
        // and end of next interval, so [>=M, <N] || [>N, <P] => [>=M, !=N, <P] but M/P can be skipped if
        // they are zero/+inf
        if ($interval->getEnd()->getOperator() === '<' && $i + 1 < $count) {
          $nextInterval = $intervals['numeric'][$i + 1];
          if ($interval->getEnd()->getVersion() === $nextInterval->getStart()->getVersion() && $nextInterval->getStart()->getOperator() === '>') {
            // only add a start if we didn't already do so, can be skipped if we're looking at second
            // interval in [>=M, <N] || [>N, <P] || [>P, <Q] where unEqualConstraints currently contains
            // [>=M, !=N] already and we only want to add !=P right now
            if (\count($unEqualConstraints) === 0 && (string) $interval->getStart() !== (string) Interval::fromZero()) {
              $unEqualConstraints[] = $interval->getStart();
            }
            $unEqualConstraints[] = new Constraint('!=', $interval->getEnd()->getVersion());
            continue;
          }
        }

        if (\count($unEqualConstraints) > 0) {
          // this is where the end of the following interval of a != constraint is added as explained above
          if ((string) $interval->getEnd() !== (string) Interval::untilPositiveInfinity()) {
            $unEqualConstraints[] = $interval->getEnd();
          }

          // count is 1 if entire constraint is just one != expression
          if (\count($unEqualConstraints) > 1) {
            $constraints[] = new MultiConstraint($unEqualConstraints, TRUE);
          }
          else {
            $constraints[] = $unEqualConstraints[0];
          }

          $unEqualConstraints = [];
          continue;
        }

        // convert back >= x - <= x intervals to == x
        if ($interval->getStart()->getVersion() === $interval->getEnd()->getVersion() && $interval->getStart()->getOperator() === '>=' && $interval->getEnd()->getOperator() === '<=') {
          $constraints[] = new Constraint('==', $interval->getStart()->getVersion());
          continue;
        }

        if ((string) $interval->getStart() === (string) Interval::fromZero()) {
          $constraints[] = $interval->getEnd();
        }
        elseif ((string) $interval->getEnd() === (string) Interval::untilPositiveInfinity()) {
          $constraints[] = $interval->getStart();
        }
        else {
          $constraints[] = new MultiConstraint([$interval->getStart(), $interval->getEnd()], TRUE);
        }
      }
    }

    $devConstraints = [];

    if (0 === \count($intervals['branches']['names'])) {
      if ($intervals['branches']['exclude']) {
        if ($hasNumericMatchAll) {
          return new MatchAllConstraint();
        }
        // otherwise constraint should contain a != operator and already cover this
      }
    }
    else {
      foreach ($intervals['branches']['names'] as $branchName) {
        if ($intervals['branches']['exclude']) {
          $devConstraints[] = new Constraint('!=', $branchName);
        }
        else {
          $devConstraints[] = new Constraint('==', $branchName);
        }
      }

      // excluded branches, e.g. != dev-foo are conjunctive with the interval, so
      // > 2.0 != dev-foo must return a conjunctive constraint
      if ($intervals['branches']['exclude']) {
        if (\count($constraints) > 1) {
          return new MultiConstraint(array_merge(
                [new MultiConstraint($constraints, FALSE)],
                $devConstraints
            ), TRUE);
        }

        if (\count($constraints) === 1 && (string) $constraints[0] === (string) Interval::fromZero()) {
          if (\count($devConstraints) > 1) {
            return new MultiConstraint($devConstraints, TRUE);
          }
          return $devConstraints[0];
        }

        return new MultiConstraint(array_merge($constraints, $devConstraints), TRUE);
      }

      // otherwise devConstraints contains a list of == operators for branches which are disjunctive with the
      // rest of the constraint
      $constraints = array_merge($constraints, $devConstraints);
    }

    if (\count($constraints) > 1) {
      return new MultiConstraint($constraints, FALSE);
    }

    if (\count($constraints) === 1) {
      return $constraints[0];
    }

    return new MatchNoneConstraint();
  }

  /**
   * Creates an array of numeric intervals and branch constraints representing a given constraint.
   *
   * If the returned numeric array is empty it means the constraint matches nothing in the numeric range (0 - +inf)
   * if the returned branches array is empty it means no dev-* versions are matched
   * if a constraint matches all possible dev-* versions, branches will contain Interval::anyDev()
   *
   * @return array
   *
   * @phpstan-return array{'numeric': Interval[], 'branches': array{'names': string[], 'exclude': bool}}
   */
  public static function get(ConstraintInterface $constraint) {
    $key = (string) $constraint;

    if (!isset(self::$intervalsCache[$key])) {
      self::$intervalsCache[$key] = self::generateIntervals($constraint);
    }

    return self::$intervalsCache[$key];
  }

  /**
   * @param bool $stopOnFirstValidInterval
   *
   * @phpstan-return array{'numeric': Interval[], 'branches': array{'names': string[], 'exclude': bool}}
   */
  private static function generateIntervals(ConstraintInterface $constraint, $stopOnFirstValidInterval = FALSE) {
    if ($constraint instanceof MatchAllConstraint) {
      return ['numeric' => [new Interval(Interval::fromZero(), Interval::untilPositiveInfinity())], 'branches' => Interval::anyDev()];
    }

    if ($constraint instanceof MatchNoneConstraint) {
      return ['numeric' => [], 'branches' => ['names' => [], 'exclude' => FALSE]];
    }

    if ($constraint instanceof Constraint) {
      return self::generateSingleConstraintIntervals($constraint);
    }

    if (!$constraint instanceof MultiConstraint) {
      throw new \UnexpectedValueException('The constraint passed in should be an MatchAllConstraint, Constraint or MultiConstraint instance, got ' . \get_class($constraint) . '.');
    }

    $constraints = $constraint->getConstraints();

    $numericGroups = [];
    $constraintBranches = [];
    foreach ($constraints as $c) {
      $res = self::get($c);
      $numericGroups[] = $res['numeric'];
      $constraintBranches[] = $res['branches'];
    }

    if ($constraint->isDisjunctive()) {
      $branches = Interval::noDev();
      foreach ($constraintBranches as $b) {
        if ($b['exclude']) {
          if ($branches['exclude']) {
            // disjunctive constraint, so only exclude what's excluded in all constraints
            // !=a,!=b || !=b,!=c => !=b
            $branches['names'] = array_intersect($branches['names'], $b['names']);
          }
          else {
            // disjunctive constraint so exclude all names which are not explicitly included in the alternative
            // (==b || ==c) || !=a,!=b => !=a
            $branches['exclude'] = TRUE;
            $branches['names'] = array_diff($b['names'], $branches['names']);
          }
        }
        else {
          if ($branches['exclude']) {
            // disjunctive constraint so exclude all names which are not explicitly included in the alternative
            // !=a,!=b || (==b || ==c) => !=a
            $branches['names'] = array_diff($branches['names'], $b['names']);
          }
          else {
            // disjunctive constraint, so just add all the other branches
            // (==a || ==b) || ==c => ==a || ==b || ==c
            $branches['names'] = array_merge($branches['names'], $b['names']);
          }
        }
      }
    }
    else {
      $branches = Interval::anyDev();
      foreach ($constraintBranches as $b) {
        if ($b['exclude']) {
          if ($branches['exclude']) {
            // conjunctive, so just add all branch names to be excluded
            // !=a && !=b => !=a,!=b
            $branches['names'] = array_merge($branches['names'], $b['names']);
          }
          else {
            // conjunctive, so only keep included names which are not excluded
            // (==a||==c) && !=a,!=b => ==c
            $branches['names'] = array_diff($branches['names'], $b['names']);
          }
        }
        else {
          if ($branches['exclude']) {
            // conjunctive, so only keep included names which are not excluded
            // !=a,!=b && (==a||==c) => ==c
            $branches['names'] = array_diff($b['names'], $branches['names']);
            $branches['exclude'] = FALSE;
          }
          else {
            // conjunctive, so only keep names that are included in both
            // (==a||==b) && (==a||==c) => ==a
            $branches['names'] = array_intersect($branches['names'], $b['names']);
          }
        }
      }
    }

    $branches['names'] = array_unique($branches['names']);

    if (\count($numericGroups) === 1) {
      return ['numeric' => $numericGroups[0], 'branches' => $branches];
    }

    $borders = [];
    foreach ($numericGroups as $group) {
      foreach ($group as $interval) {
        $borders[] = ['version' => $interval->getStart()->getVersion(), 'operator' => $interval->getStart()->getOperator(), 'side' => 'start'];
        $borders[] = ['version' => $interval->getEnd()->getVersion(), 'operator' => $interval->getEnd()->getOperator(), 'side' => 'end'];
      }
    }

    $opSortOrder = self::$opSortOrder;
    usort($borders, function ($a, $b) use ($opSortOrder) {
        $order = version_compare($a['version'], $b['version']);
      if ($order === 0) {
            return $opSortOrder[$a['operator']] - $opSortOrder[$b['operator']];
      }

        return $order;
    });

    $activeIntervals = 0;
    $intervals = [];
    $index = 0;
    $activationThreshold = $constraint->isConjunctive() ? \count($numericGroups) : 1;
    $start = NULL;
    foreach ($borders as $border) {
      if ($border['side'] === 'start') {
        $activeIntervals++;
      }
      else {
        $activeIntervals--;
      }
      if (!$start && $activeIntervals >= $activationThreshold) {
        $start = new Constraint($border['operator'], $border['version']);
      }
      elseif ($start && $activeIntervals < $activationThreshold) {
        // filter out invalid intervals like > x - <= x, or >= x - < x
        if (
              version_compare($start->getVersion(), $border['version'], '=')
              && (
                  ($start->getOperator() === '>' && $border['operator'] === '<=')
                  || ($start->getOperator() === '>=' && $border['operator'] === '<')
              )
          ) {
          unset($intervals[$index]);
        }
        else {
          $intervals[$index] = new Interval($start, new Constraint($border['operator'], $border['version']));
          $index++;

          if ($stopOnFirstValidInterval) {
            break;
          }
        }

        $start = NULL;
      }
    }

    return ['numeric' => $intervals, 'branches' => $branches];
  }

  /**
   * @phpstan-return array{'numeric': Interval[], 'branches': array{'names': string[], 'exclude': bool}}
   */
  private static function generateSingleConstraintIntervals(Constraint $constraint) {
    $op = $constraint->getOperator();

    // handle branch constraints first
    if (strpos($constraint->getVersion(), 'dev-') === 0) {
      $intervals = [];
      $branches = ['names' => [], 'exclude' => FALSE];

      // != dev-foo means any numeric version may match, we treat >/< like != they are not really defined for branches
      if ($op === '!=') {
        $intervals[] = new Interval(Interval::fromZero(), Interval::untilPositiveInfinity());
        $branches = ['names' => [$constraint->getVersion()], 'exclude' => TRUE];
      }
      elseif ($op === '==') {
        $branches['names'][] = $constraint->getVersion();
      }

      return [
        'numeric' => $intervals,
        'branches' => $branches,
      ];
    }

    // > & >=
    if ($op[0] === '>') {
      return ['numeric' => [new Interval($constraint, Interval::untilPositiveInfinity())], 'branches' => Interval::noDev()];
    }
    // < & <=
    if ($op[0] === '<') {
      return ['numeric' => [new Interval(Interval::fromZero(), $constraint)], 'branches' => Interval::noDev()];
    }
    if ($op === '!=') {
      // convert !=x to intervals of 0 - <x && >x - +inf + dev*
      return [
        'numeric' => [
          new Interval(Interval::fromZero(), new Constraint('<', $constraint->getVersion())),
          new Interval(new Constraint('>', $constraint->getVersion()), Interval::untilPositiveInfinity()),
        ],
        'branches' => Interval::anyDev(),
      ];
    }

    // convert ==x to an interval of >=x - <=x
    return [
      'numeric' => [
        new Interval(new Constraint('>=', $constraint->getVersion()), new Constraint('<=', $constraint->getVersion())),
      ],
      'branches' => Interval::noDev(),
    ];
  }

}
