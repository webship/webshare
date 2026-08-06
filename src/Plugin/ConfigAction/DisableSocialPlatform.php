<?php

declare(strict_types=1);

namespace Drupal\webshare\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Disables Webshare social platforms from a recipe.
 *
 * The declarative twin of clearing a platform's "Enabled" checkbox on the
 * Webshare configuration form: the platform stays configured, it just
 * stops rendering. Takes one platform id or a list of them; every id must
 * exist.
 *
 * @code
 * webshare.settings:
 *   disableSocialPlatform: email
 * @endcode
 */
#[ConfigAction(
    id: 'disableSocialPlatform',
    admin_label: new TranslatableMarkup('Disable a Webshare social platform'),
)]
final class DisableSocialPlatform extends SocialPlatformActionBase {

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    foreach ($this->toPlatformIds($value, 'disableSocialPlatform') as $platform_id) {
      try {
        $this->platformManager->setPlatformEnabled($platform_id, FALSE);
      } catch (\InvalidArgumentException $e) {
        throw new ConfigActionException($e->getMessage(), previous: $e);
      }
    }
  }
}
