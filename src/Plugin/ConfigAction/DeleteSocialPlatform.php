<?php

declare(strict_types=1);

namespace Drupal\webshare\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Deletes Webshare social platforms from a recipe.
 *
 * The declarative twin of the configuration form's Delete operation: the
 * platform row is removed entirely. To keep a platform configured but
 * hidden, use `disableSocialPlatform` instead. Takes one platform id or a
 * list of them; every id must exist.
 *
 * @code
 * webshare.settings:
 *   deleteSocialPlatform: whatsapp
 * @endcode
 */
#[ConfigAction(
    id: 'deleteSocialPlatform',
    admin_label: new TranslatableMarkup('Delete a Webshare social platform'),
)]
final class DeleteSocialPlatform extends SocialPlatformActionBase {

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    foreach ($this->toPlatformIds($value, 'deleteSocialPlatform') as $platform_id) {
      try {
        $this->platformManager->deletePlatform($platform_id);
      } catch (\InvalidArgumentException $e) {
        throw new ConfigActionException($e->getMessage(), previous: $e);
      }
    }
  }
}
