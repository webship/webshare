<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig;

use Twig\Node\Expression\FilterExpression;
use Twig\Node\Node;

/**
 * Represents a template filter.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @see https://twig.symfony.com/doc/templates.html#filters
 */
final class TwigFilter extends AbstractTwigCallable {

  /**
   * @param callable|array{class-string, string}|null $callable
   *   A callable implementing the filter. If null, you need to overwrite the "node_class" option to customize compilation.
   */
  public function __construct(string $name, $callable = NULL, array $options = []) {
    parent::__construct($name, $callable, $options);

    $this->options = array_merge([
      'is_safe' => NULL,
      'is_safe_callback' => NULL,
      'pre_escape' => NULL,
      'preserves_safety' => NULL,
      'node_class' => FilterExpression::class,
    ], $this->options);
  }

  public function getType(): string {
    return 'filter';
  }

  public function getSafe(Node $filterArgs): ?array {
    if (NULL !== $this->options['is_safe']) {
      return $this->options['is_safe'];
    }

    if (NULL !== $this->options['is_safe_callback']) {
      return $this->options['is_safe_callback']($filterArgs);
    }

    return [];
  }

  public function getPreservesSafety(): array {
    return $this->options['preserves_safety'] ?? [];
  }

  public function getPreEscape(): ?string {
    return $this->options['pre_escape'];
  }

  public function getMinimalNumberOfRequiredArguments(): int {
    return parent::getMinimalNumberOfRequiredArguments() + 1;
  }

}
