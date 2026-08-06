<?php

declare(strict_types=1);

namespace Drupal\webshare\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Adds or edits a Webshare social platform from a recipe.
 *
 * The declarative twin of the UI's "Add/Edit Platform" form. Editing (an
 * existing `platform_id`) only touches the keys given — anything omitted
 * keeps its current stored value. Adding a new platform needs at least
 * `platform_id`, `name` and `title` (mirroring the form's own required
 * fields); `url_template` empty means copy-to-clipboard mode, same as any
 * other platform without one.
 *
 * @code
 * webshare.settings:
 *   saveSocialPlatform:
 *     platform_id: instagram
 *     name: Instagram
 *     title: 'Follow us on Instagram'
 *     url_template: 'https://www.instagram.com/example/'
 * @endcode
 *
 * A recipe can only list one `saveSocialPlatform` key per config name, so
 * to add or edit several platforms in one action, pass a list of
 * platform-value arrays instead of a single one:
 *
 * @code
 * webshare.settings:
 *   saveSocialPlatform:
 *     - platform_id: instagram
 *       name: Instagram
 *       title: 'Follow us on Instagram'
 *       url_template: 'https://www.instagram.com/example/'
 *     - platform_id: linkedin
 *       enabled: 1
 * @endcode
 *
 * To reorder platforms, use `reorderSocialPlatforms` instead of setting
 * weights by hand; to enable, disable or delete, use
 * `enableSocialPlatform`, `disableSocialPlatform` or
 * `deleteSocialPlatform`.
 */
#[ConfigAction(
    id: 'saveSocialPlatform',
    admin_label: new TranslatableMarkup('Add or edit a Webshare social platform'),
)]
final class SaveSocialPlatform extends SocialPlatformActionBase {

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    if (!is_array($value)) {
      throw new ConfigActionException('The saveSocialPlatform config action requires an array value.');
    }
    // A single platform (has its own platform_id key) vs. a list of them.
    $platforms = array_key_exists('platform_id', $value) ? [$value] : $value;
    foreach ($platforms as $platform_values) {
      if (!is_array($platform_values) || empty($platform_values['platform_id'])) {
        throw new ConfigActionException('The saveSocialPlatform config action requires each platform to have at least a platform_id key.');
      }
      try {
        $this->platformManager->savePlatform($platform_values);
      } catch (\InvalidArgumentException $e) {
        throw new ConfigActionException($e->getMessage(), previous: $e);
      }
    }
  }
}
