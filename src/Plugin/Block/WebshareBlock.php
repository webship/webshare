<?php

namespace Drupal\webshare\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\webshare\WebshareServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Webshare' Block.
 *
 * @Block(
 *   id = "share",
 *   admin_label = @Translation("Share"),
 * )
 */
class WebshareBlock extends BlockBase implements ContainerFactoryPluginInterface
{

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WebshareServiceInterface $share_service, AliasManagerInterface $alias_manager)
  {
      parent::__construct($configuration, $plugin_id, $plugin_definition);
      $this->shareService = $share_service;
      $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
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
  public function defaultConfiguration()
  {
      return [
          'heading' => 'Share',
          'display_title' => TRUE,
          'alignment' => 'end',
          'orientation' => 'vertical',
          'mobile_visibility' => 'all',
          'native_share' => FALSE,
          'placement' => 'rail-end',
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
      $config = $this->getConfiguration();

      $form['heading'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Title'),
          '#description' => $this->t('The title displayed above the sharing buttons.'),
          '#default_value' => $config['heading'] ?? 'Share',
          '#maxlength' => 255,
      ];

      $form['display_title'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Display title'),
          '#default_value' => $config['display_title'] ?? TRUE,
      ];

      $form['alignment'] = [
          '#type' => 'radios',
          '#title' => $this->t('Alignment'),
          '#description' => $this->t('Choose whether the buttons sit on the start or end side of the content (RTL-aware: start is left in LTR languages, right in RTL languages).'),
          '#options' => [
              'start' => $this->t('Start side'),
              'end' => $this->t('End side'),
          ],
          '#default_value' => $config['alignment'] ?? 'end',
      ];

      $form['orientation'] = [
          '#type' => 'radios',
          '#title' => $this->t('Orientation'),
          '#description' => $this->t('Lay the share buttons out in a row or a column.'),
          '#options' => [
              'horizontal' => $this->t('Horizontal'),
              'vertical' => $this->t('Vertical'),
          ],
          '#default_value' => $config['orientation'] ?? 'vertical',
      ];

      $form['mobile_visibility'] = [
          '#type' => 'radios',
          '#title' => $this->t('Mobile visibility'),
          '#description' => $this->t('Choose whether the block is shown on small screens (max-width 768px).'),
          '#options' => [
              'all' => $this->t('Show on all devices'),
              'hide_mobile' => $this->t('Hide on mobile'),
              'mobile_only' => $this->t('Show on mobile only'),
          ],
          '#default_value' => $config['mobile_visibility'] ?? 'all',
      ];

      $form['native_share'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show native share button'),
          '#description' => $this->t('Render a button that opens the device share sheet via the Web Share API when the browser supports it. Falls back to copying the page URL on desktop.'),
          '#default_value' => $config['native_share'] ?? FALSE,
      ];

      $form['placement'] = [
          '#type' => 'radios',
          '#title' => $this->t('Placement'),
          '#description' => $this->t('Inline keeps the buttons in the content flow; rail-end floats them as a sticky column beside the content (used in the approved article design).'),
          '#options' => [
              'inline' => $this->t('Inline'),
              'rail-end' => $this->t('Rail at the end of the content'),
          ],
          '#default_value' => $config['placement'] ?? 'rail-end',
      ];

      return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
      $this->configuration['heading'] = (string) $form_state->getValue('heading');
      $this->configuration['display_title'] = (bool) $form_state->getValue('display_title');
      $this->configuration['alignment'] = $form_state->getValue('alignment');
      $this->configuration['orientation'] = $form_state->getValue('orientation');
      $this->configuration['mobile_visibility'] = $form_state->getValue('mobile_visibility');
      $this->configuration['native_share'] = (bool) $form_state->getValue('native_share');
      $this->configuration['placement'] = $form_state->getValue('placement');
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
      $config = $this->getConfiguration();
      $url = Url::fromRoute('<current>');
      $id = str_replace('/', '', $this->aliasManager->getPathByAlias($url->toString()));

      // Heading / display_title / alignment now live on the block itself -
      // pass them through as service options so the component receives them
      // as props.
      $heading = '';
    if (!empty($config['display_title'])) {
        $heading = (string) ($config['heading'] ?? 'Share');
    }

      return $this->shareService->build($url->setAbsolute()->toString(), $id, [
          'heading' => $heading,
          'alignment' => $config['alignment'] ?? 'start',
          'orientation' => $config['orientation'] ?? 'horizontal',
          'mobile_visibility' => $config['mobile_visibility'] ?? 'all',
          'native_share' => $config['native_share'] ?? TRUE,
          'placement' => $config['placement'] ?? 'inline',
      ]);
  }
}
