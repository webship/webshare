<?php

namespace Drupal\webshare\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a settings form for Webshare module.
 */
class WebshareConfigForm extends ConfigFormBase
{

  /**
   * The render cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
    protected $renderCache;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
    protected $database;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
    protected $time;

  /**
   * Constructs a WebshareConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $render_cache
   *   The render cache.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config, CacheBackendInterface $render_cache, Connection $database, TimeInterface $time)
  {
      parent::__construct($config_factory, $typed_config);
      $this->renderCache = $render_cache;
      $this->database = $database;
      $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
      return new static(
          $container->get('config.factory'),
          $container->get('config.typed'),
          $container->get('cache.render'),
          $container->get('database'),
          $container->get('datetime.time')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
      return 'webshare_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
      return ['webshare.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
      $config = $this->config('webshare.settings');

      // Platform management section. Heading + display_title + alignment
      // moved to block-level config (and the SDC share component props),
      // so the admin form is now just the platform table + add/edit modal.
      $form['platform_management'] = [
      '#type' => 'container',
      ];

      // Get platforms from database (with error handling)
      $platforms = [];
      try {
        if ($this->database->schema()->tableExists('webshare_platforms')) {
            $platforms = $this->getPlatforms();
        }
      } catch (\Exception $e) {
        // If database access fails, use empty array
          $platforms = [];
      }

      $form['platform_management']['platforms_intro'] = [
      '#markup' => '<div class="platforms-management-intro">' .
      '<p>' . $this->t('Drag to reorder platforms, or use the operations to edit or delete them.') . '</p>' .
      '</div>',
      ];

      $form['platform_management']['platforms_table'] = [
      '#type' => 'table',
      '#header' => [
      $this->t('Platform'),
      $this->t('Enabled'),
      $this->t('Weight'),
      $this->t('Operations'),
      ],
      '#empty' => $this->t('No platforms configured.'),
      '#tabledrag' => [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'platform-weight',
      ],
      ],
      ];

      foreach ($platforms as $platform) {
          $id = $platform->platform_id;
          $form['platform_management']['platforms_table'][$id]['#attributes']['class'][] = 'draggable';
          $form['platform_management']['platforms_table'][$id]['#weight'] = $platform->weight;

          $form['platform_management']['platforms_table'][$id]['info'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['platform-info']],
          ];
          $form['platform_management']['platforms_table'][$id]['info']['name'] = [
          '#markup' => '<strong>' . $this->t($platform->name) . '</strong>',
          ];
          $form['platform_management']['platforms_table'][$id]['info']['description'] = [
          '#markup' => '<div class="platform-description">' . $this->t($platform->title) . '</div>',
          ];

          $form['platform_management']['platforms_table'][$id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enabled'),
          '#title_display' => 'invisible',
          '#default_value' => $platform->enabled,
          '#parents' => ['platforms_data', $id, 'enabled'],
          ];

          $form['platform_management']['platforms_table'][$id]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => $platform->weight,
          '#delta' => 50,
          '#attributes' => ['class' => ['platform-weight']],
          '#parents' => ['platforms_data', $id, 'weight'],
          ];

          $operations = [];
          $operations['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('webshare.platform_edit', ['platform_id' => $id]),
          'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => '{"width":700,"height":600}',
          ],
          ];
          $operations['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('webshare.platform_delete', ['platform_id' => $id]),
          'attributes' => ['class' => ['use-ajax'], 'data-dialog-type' => 'modal'],
          ];

          $form['platform_management']['platforms_table'][$id]['operations'] = [
          '#type' => 'operations',
          '#links' => $operations,
          ];
      }

      // Add new platform section
      $form['platform_management']['add_platform'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['add-platform-section']],
      ];

      $form['platform_management']['add_platform']['add_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Custom Platform'),
      '#url' => Url::fromRoute('webshare.platform_add'),
      '#attributes' => [
      'class' => ['button', 'button--primary', 'use-ajax'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => '{"width":700,"height":600}',
      ],
      ];
      return parent::buildForm($form, $form_state);
  }

  /**
   * Get platforms from database.
   */
  protected function getPlatforms()
  {
    try {
        return $this->database
        ->select('webshare_platforms', 'wp')
        ->fields('wp')
        ->orderBy('weight')
        ->orderBy('name')
        ->execute()
        ->fetchAll();
    } catch (\Exception $e) {
        return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
      $form_values = $form_state->getValues();

    // Update platform data
    if (!empty($form_values['platforms_data'])) {
      foreach ($form_values['platforms_data'] as $platform_id => $platform_data) {
        $this->database->update('webshare_platforms')
        ->fields([
        'enabled' => (int) $platform_data['enabled'],
        'weight' => (int) $platform_data['weight'],
        'updated' => $this->time->getRequestTime(),
        ])
          ->condition('platform_id', $platform_id)
          ->execute();
      }
    }

      // Invalidate the shared platform cache tag so every cached rendering of
      // the share rail — including the anonymous page cache, which the tag
      // bubbles up to — picks up the new enabled/weight values.
      \Drupal\Core\Cache\Cache::invalidateTags(['webshare_platforms']);
      parent::submitForm($form, $form_state);
  }
}
