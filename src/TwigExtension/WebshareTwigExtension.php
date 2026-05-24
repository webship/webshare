<?php

declare(strict_types=1);

namespace Drupal\webshare\TwigExtension;

use Drupal\Core\Url;
use Drupal\webshare\WebshareServiceInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the Webshare share payload to Twig.
 *
 * Primarily for Single Directory Components (e.g. the Vartheme BS5
 * `share` SDC) rendered through Drupal Canvas, where no Webshare block is
 * present to supply data. The SDC cannot declare a `platforms`
 * array-of-objects prop (Canvas has no matching field widget), so the template
 * calls `webshare_share_data()` to obtain the resolved share URL and the
 * enabled platform links for the current request.
 */
final class WebshareTwigExtension extends AbstractExtension {

  public function __construct(
    protected readonly WebshareServiceInterface $webshareService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('webshare_share_data', [$this, 'shareData']),
    ];
  }

  /**
   * Builds the share payload for the current request.
   *
   * @param string $url
   *   (optional) Explicit share URL; when empty the current request URL is
   *   used.
   * @param array $options
   *   (optional) Presentation options forwarded to the Webshare service.
   *
   * @return array
   *   Array with `url` (resolved absolute share URL) and `platforms` (list of
   *   platform link definitions).
   */
  public function shareData(string $url = '', array $options = []): array {
    if ($url === '') {
      try {
        $url = Url::fromRoute('<current>')->setAbsolute()->toString();
      }
      catch (\Throwable $e) {
        $url = '';
      }
    }

    $id = 'sdc-' . substr(hash('xxh3', $url !== '' ? $url : uniqid('', TRUE)), 0, 8);

    try {
      $build = $this->webshareService->build($url, $id, $options);
    }
    catch (\Throwable $e) {
      return ['url' => $url, 'platforms' => []];
    }

    $props = $build['#props'] ?? [];
    return [
      'url' => $props['url'] ?? $url,
      'platforms' => $props['platforms'] ?? [],
    ];
  }

}
