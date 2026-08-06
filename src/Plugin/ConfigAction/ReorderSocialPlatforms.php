<?php

declare(strict_types=1);

namespace Drupal\webshare\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Reorders Webshare social platforms from a recipe.
 *
 * The declarative twin of dragging rows on the Webshare configuration
 * form: list the platform ids in the order they should render, and each
 * gets its position as its weight. Platforms not listed keep their stored
 * weight, so to fully control the order, list every enabled platform.
 * Every id must exist (add new platforms with `saveSocialPlatform` first).
 *
 * @code
 * webshare.settings:
 *   reorderSocialPlatforms:
 *     - facebook_share
 *     - instagram
 *     - linkedin
 *     - x
 * @endcode
 */
#[ConfigAction(
    id: 'reorderSocialPlatforms',
    admin_label: new TranslatableMarkup('Reorder the Webshare social platforms'),
)]
final class ReorderSocialPlatforms extends SocialPlatformActionBase {

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    if (!is_array($value)) {
      throw new ConfigActionException('The reorderSocialPlatforms config action requires a list of platform ids in the desired order.');
    }
    try {
      $this->platformManager->reorderPlatforms($this->toPlatformIds($value, 'reorderSocialPlatforms'));
    } catch (\InvalidArgumentException $e) {
      throw new ConfigActionException($e->getMessage(), previous: $e);
    }
  }
}
