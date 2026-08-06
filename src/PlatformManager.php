<?php

namespace Drupal\webshare;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;

/**
 * Adds, edits, enables, reorders and deletes Webshare platforms.
 *
 * The single write path both the admin forms
 * (\Drupal\webshare\Form\PlatformForm,
 * \Drupal\webshare\Form\PlatformDeleteForm) and the config action plugins
 * under \Drupal\webshare\Plugin\ConfigAction go through, so a platform
 * changed by a recipe's config action is validated and cached exactly the
 * same way as one changed by a site builder through the UI.
 */
class PlatformManager {

  public function __construct(
      protected Connection $database,
      protected TimeInterface $time,
  ) {
  }

  /**
   * Loads one platform row, or NULL if it doesn't exist.
   */
  public function loadPlatform(string $platformId): ?object {
    $platform = $this->database
      ->select('webshare_platforms', 'wp')
      ->fields('wp')
      ->condition('platform_id', $platformId)
      ->execute()
      ->fetchObject();
    return $platform ?: NULL;
  }

  /**
   * Adds or edits a platform, and can reorder it via `weight`.
   *
   * Editing (an existing `platform_id`) only touches the keys present in
   * $values — omitted keys keep their current stored value, so a caller
   * that only wants to reorder a platform can pass just
   * `['platform_id' => ..., 'weight' => ...]`.
   *
   * Adding a new platform requires `platform_id`, `name` and `title`;
   * everything else falls back to the same defaults the form's "Add
   * Platform" button would apply.
   *
   * @param array $values
   *   Platform field values: platform_id, name, title, url_template,
   *   enabled, weight, image.
   *
   * @throws \InvalidArgumentException
   *   If a required value is missing, or `url_template` fails the same
   *   validation the form applies (must start with https://, http:// or
   *   mailto:, and contain the [url] placeholder — empty means
   *   copy-to-clipboard mode).
   */
  public function savePlatform(array $values): void {
    $platform_id = $values['platform_id'] ?? NULL;
    if (!$platform_id) {
      throw new \InvalidArgumentException('savePlatform() requires a platform_id.');
    }

    if (isset($values['url_template']) && $values['url_template'] !== '') {
      $url_template = $values['url_template'];
      if (!preg_match('/^(https?:\/\/|mailto:)/i', $url_template)) {
        throw new \InvalidArgumentException("The sharing URL template for '$platform_id' must start with https://, http:// or mailto:.");
      }
      // [url]/[title] are optional, not required: a platform with neither
      // is a legitimate static link (e.g. Instagram, which has no
      // share-intent URL at all and so always points at a fixed profile
      // rather than the current page) rather than a mistake — the
      // placeholder substitution is a no-op on a template that doesn't
      // use it, so nothing breaks by omitting it.
    }

    $existing = $this->loadPlatform($platform_id);
    $time = $this->time->getRequestTime();

    if ($existing) {
      $fields = [];
      foreach (['name', 'title', 'url_template', 'image'] as $key) {
        if (array_key_exists($key, $values)) {
          $fields[$key] = $values[$key];
        }
      }
      if (array_key_exists('enabled', $values)) {
        $fields['enabled'] = (int) $values['enabled'];
      }
      if (array_key_exists('weight', $values)) {
        $fields['weight'] = (int) $values['weight'];
      }
      if ($fields) {
        $fields['updated'] = $time;
        $this->database->update('webshare_platforms')
          ->fields($fields)
          ->condition('platform_id', $platform_id)
          ->execute();
      }
    } else {
      foreach (['name', 'title'] as $key) {
        if (empty($values[$key])) {
          throw new \InvalidArgumentException("savePlatform() requires '$key' to add the new platform '$platform_id'.");
        }
      }
      $this->database->insert('webshare_platforms')
        ->fields([
          'platform_id' => $platform_id,
          'name' => $values['name'],
          'title' => $values['title'],
          'url_template' => $values['url_template'] ?? '',
          'enabled' => (int) ($values['enabled'] ?? 1),
          'image' => $values['image'] ?? '',
          'weight' => (int) ($values['weight'] ?? 0),
          'is_custom' => 1,
          'created' => $time,
          'updated' => $time,
        ])
        ->execute();
    }

    // Refresh every cached rendering of the share rail (anonymous page
    // cache included) now that the platform set has changed.
    Cache::invalidateTags(['webshare_platforms']);
  }

  /**
   * Enables or disables a platform, same as the config form's checkbox.
   *
   * @throws \InvalidArgumentException
   *   If no platform with that id exists.
   */
  public function setPlatformEnabled(string $platformId, bool $enabled): void {
    $this->requirePlatform($platformId);
    $this->database->update('webshare_platforms')
      ->fields([
        'enabled' => (int) $enabled,
        'updated' => $this->time->getRequestTime(),
      ])
      ->condition('platform_id', $platformId)
      ->execute();
    Cache::invalidateTags(['webshare_platforms']);
  }

  /**
   * Reorders platforms, same as dragging rows on the config form.
   *
   * Each listed platform gets its position in the list as its weight
   * (0, 1, 2, …). Platforms not listed keep their stored weight, so to
   * fully control the rendered order, list every enabled platform.
   *
   * @param string[] $platformIds
   *   Platform ids in the desired order.
   *
   * @throws \InvalidArgumentException
   *   If any of the ids doesn't exist.
   */
  public function reorderPlatforms(array $platformIds): void {
    foreach ($platformIds as $platform_id) {
      $this->requirePlatform($platform_id);
    }
    $time = $this->time->getRequestTime();
    foreach (array_values($platformIds) as $weight => $platform_id) {
      $this->database->update('webshare_platforms')
        ->fields(['weight' => $weight, 'updated' => $time])
        ->condition('platform_id', $platform_id)
        ->execute();
    }
    Cache::invalidateTags(['webshare_platforms']);
  }

  /**
   * Declares the enabled platform set: exactly these, in this order.
   *
   * The desired-state counterpart to the imperative methods above, matching
   * how a site template thinks about the share rail: every listed platform
   * is saved (added or edited), enabled, and weighted by its list position;
   * every platform NOT listed is disabled (not deleted, so a site builder
   * can still re-enable it from the UI).
   *
   * @param array $platforms
   *   Platform value arrays in the desired render order. Each needs at
   *   least a `platform_id`; new platforms also need `name` and `title`,
   *   same as savePlatform().
   *
   * @throws \InvalidArgumentException
   *   If an entry is invalid, propagated from savePlatform().
   */
  public function setPlatforms(array $platforms): void {
    $listed = [];
    foreach (array_values($platforms) as $weight => $values) {
      $platform_id = $values['platform_id'] ?? NULL;
      if (!$platform_id) {
        throw new \InvalidArgumentException('setPlatforms() requires each platform to have a platform_id.');
      }
      $values['enabled'] = 1;
      $values['weight'] = $weight;
      $this->savePlatform($values);
      $listed[] = $platform_id;
    }
    if (!$listed) {
      throw new \InvalidArgumentException('setPlatforms() requires at least one platform.');
    }
    $this->database->update('webshare_platforms')
      ->fields([
        'enabled' => 0,
        'updated' => $this->time->getRequestTime(),
      ])
      ->condition('platform_id', $listed, 'NOT IN')
      ->execute();
    Cache::invalidateTags(['webshare_platforms']);
  }

  /**
   * Deletes a platform, same as the config form's Delete operation.
   *
   * @throws \InvalidArgumentException
   *   If no platform with that id exists.
   */
  public function deletePlatform(string $platformId): void {
    $this->requirePlatform($platformId);
    $this->database->delete('webshare_platforms')
      ->condition('platform_id', $platformId)
      ->execute();
    Cache::invalidateTags(['webshare_platforms']);
  }

  /**
   * Throws unless a platform with that id exists.
   *
   * @throws \InvalidArgumentException
   */
  protected function requirePlatform(string $platformId): void {
    if (!$this->loadPlatform($platformId)) {
      throw new \InvalidArgumentException("No Webshare platform '$platformId' exists.");
    }
  }
}
