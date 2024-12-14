<?php

namespace Drupal\webshare\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\webshare\WebshareServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Webshare' Block.
 *
 * @Block(
 *   id = "webshare_block",
 *   admin_label = @Translation("Webshare Block"),
 * )
 */
class WebshareBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The WebShare service.
   *
   * @var \Drupal\webshare\WebshareServiceInterface
   */
  protected $shareService;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs an WebshareBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\webshare\WebshareServiceInterface $share_service
   *   The module manager service.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WebshareServiceInterface $share_service, AliasManagerInterface $alias_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->shareService = $share_service;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('webshare.service'),
      $container->get('path_alias.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = Url::fromRoute('<current>');
    $id = str_replace('/', '', $this->aliasManager->getPathByAlias($url->toString()));

    return $this->shareService->build($url->setAbsolute()->toString(), $id);
  }

}
