<?php

declare(strict_types=1);

namespace Drupal\webshare\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Declares the enabled Webshare platform set from a recipe.
 *
 * The desired-state action, matching how a site template thinks about the
 * share rail: "exactly these platforms, in this order". Every listed
 * platform is saved (added or edited), enabled, and ordered by its list
 * position; every platform NOT listed is disabled — not deleted, so a
 * site builder can still re-enable it from the UI. One action call covers
 * the whole rail.
 *
 * An entry can be just a platform id (an existing platform) or a full
 * platform-value array (required when adding a new platform, same keys as
 * `saveSocialPlatform`):
 *
 * @code
 * webshare.settings:
 *   setSocialPlatforms:
 *     - facebook_share
 *     - platform_id: instagram
 *       name: Instagram
 *       title: 'Follow us on Instagram'
 *       url_template: 'https://www.instagram.com/example/'
 *     - linkedin
 *     - x
 * @endcode
 *
 * For finer-grained changes that shouldn't touch the rest of the rail,
 * use `saveSocialPlatform`, `enableSocialPlatform`,
 * `disableSocialPlatform`, `reorderSocialPlatforms` or
 * `deleteSocialPlatform` instead.
 */
#[ConfigAction(
    id: 'setSocialPlatforms',
    admin_label: new TranslatableMarkup('Set the enabled Webshare social platforms and their order'),
)]
final class SetSocialPlatforms extends SocialPlatformActionBase {

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    if (!is_array($value) || !$value) {
      throw new ConfigActionException('The setSocialPlatforms config action requires a list of platforms in the desired order.');
    }
    $platforms = [];
    foreach ($value as $entry) {
      if (is_string($entry) && $entry !== '') {
        $platforms[] = ['platform_id' => $entry];
      } elseif (is_array($entry) && !empty($entry['platform_id'])) {
        $platforms[] = $entry;
      } else {
        throw new ConfigActionException('The setSocialPlatforms config action requires each entry to be a platform id or an array with a platform_id key.');
      }
    }
    try {
      $this->platformManager->setPlatforms($platforms);
    } catch (\InvalidArgumentException $e) {
      throw new ConfigActionException($e->getMessage(), previous: $e);
    }
  }
}
