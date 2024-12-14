<?php

namespace Drupal\webshare\Plugin\views\field;

use Drupal\webshare\WebshareServiceInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display Webshare buttons.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("webshare_field")
 */
class WebshareField extends FieldPluginBase {

  /**
   * The WebShare service.
   *
   * @var \Drupal\webshare\WebshareService
   */
  protected $shareService;

  /**
   * Constructs a WebshareField object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\webshare\WebshareServiceInterface $share_service
   *   The module manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WebshareServiceInterface $share_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->shareService = $share_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('webshare.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $values->_entity;
    $url = $node->toUrl()->setAbsolute()->toString();
    $id = $node->getEntityTypeId() . $node->id();

    return $this->shareService->build($url, $id);
  }

}
