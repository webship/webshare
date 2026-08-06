<?php

declare(strict_types=1);

namespace Drupal\webshare\Plugin\ConfigAction;

use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\webshare\PlatformManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for the Webshare social platform config actions.
 *
 * Webshare platforms live in the `webshare_platforms` database table, not
 * in config, so these actions don't read or write the config object named
 * in the recipe (conventionally `webshare.settings` — any existing config
 * name works equally well as the anchor). They all go through the same
 * \Drupal\webshare\PlatformManager the admin UI itself uses, so a platform
 * changed by a recipe is validated and cached exactly the same way as one
 * changed by a site builder through the UI.
 */
abstract class SocialPlatformActionBase implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
      protected readonly PlatformManager $platformManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
        $container->get('webshare.platform_manager'),
    );
  }

  /**
   * Normalizes a value to a list of platform id strings.
   *
   * Accepts a single platform id (`instagram`) or a list of them
   * (`[facebook_share, instagram]`).
   *
   * @return string[]
   *   The platform ids.
   *
   * @throws \Drupal\Core\Config\Action\ConfigActionException
   *   If the value is neither a non-empty string nor a list of them.
   */
  protected function toPlatformIds(mixed $value, string $actionId): array {
    $ids = is_array($value) ? $value : [$value];
    if (!$ids) {
      throw new ConfigActionException("The $actionId config action requires at least one platform id.");
    }
    foreach ($ids as $id) {
      if (!is_string($id) || $id === '') {
        throw new ConfigActionException("The $actionId config action requires each platform id to be a non-empty string.");
      }
    }
    return array_values($ids);
  }
}
