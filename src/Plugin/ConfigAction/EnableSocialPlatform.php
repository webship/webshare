<?php

declare(strict_types=1);

namespace Drupal\webshare\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Enables Webshare social platforms from a recipe.
 *
 * The declarative twin of ticking a platform's "Enabled" checkbox on the
 * Webshare configuration form. Takes one platform id or a list of them;
 * every id must exist (add new platforms with `saveSocialPlatform` first).
 *
 * @code
 * webshare.settings:
 *   enableSocialPlatform:
 *     - facebook_share
 *     - linkedin
 * @endcode
 */
#[ConfigAction(
    id: 'enableSocialPlatform',
    admin_label: new TranslatableMarkup('Enable a Webshare social platform'),
)]
final class EnableSocialPlatform extends SocialPlatformActionBase {

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    foreach ($this->toPlatformIds($value, 'enableSocialPlatform') as $platform_id) {
      try {
        $this->platformManager->setPlatformEnabled($platform_id, TRUE);
      } catch (\InvalidArgumentException $e) {
        throw new ConfigActionException($e->getMessage(), previous: $e);
      }
    }
  }
}
