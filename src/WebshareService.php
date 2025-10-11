<?php

namespace Drupal\webshare;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a WebshareService service.
 */
class WebshareService implements WebshareServiceInterface {
  use StringTranslationTrait;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The extension module list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an WebshareService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Configuration Factory.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The extenstion list module.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConditionManager $condition_manager, ModuleExtensionList $module_extension_list, Connection $database) {
    $this->configFactory = $config_factory;
    $this->conditionManager = $condition_manager;
    $this->moduleExtensionList = $module_extension_list;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function build($url, $id) {
    global $base_url;
    $config = $this->configFactory->get('webshare.settings');
    $module_path = $this->moduleExtensionList->getPath('webshare');
    $build = ['#theme' => 'webshare'];
    $buttons = [];
    $library = [];

    switch ($config->get('alignment')) {
      case 'left':
        $build['#attributes']['class'] = [
          'webshare-left',
        ];
          break;

      case 'right':
        $build['#attributes']['class'] = [
          'webshare-right',
        ];
          break;
    }

    // Get enabled platforms from database (fallback to config if table doesn't exist)
    $platforms = [];
    try {
      if ($this->database->schema()->tableExists('webshare_platforms')) {
        $platforms = $this->database
          ->select('webshare_platforms', 'wp')
          ->fields('wp')
          ->condition('enabled', 1)
          ->orderBy('weight')
          ->orderBy('name')
          ->execute()
          ->fetchAll();
      }
    } catch (\Exception $e) {
      // If database access fails, fall back to empty array
    }

    // Fallback to legacy config if no platforms in database
    if (empty($platforms)) {
      $share_buttons = $config->get('buttons');
      if ($share_buttons) {
        uasort($share_buttons, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
        foreach ($share_buttons as $key => $button) {
          if ($key != 'facebook_like' && $button['enabled']) {
            // Create a pseudo-platform object for backward compatibility
            $platform = (object) [
              'platform_id' => $key,
              'name' => $button['name'],
              'title' => $button['title'] ?? $button['name'],
              'enabled' => $button['enabled'],
              'image' => $module_path . '/img/' . $button['image'],
              'weight' => $button['weight'] ?? 0,
              'url_template' => $this->getDefaultUrlTemplate($key),
            ];
            $platforms[] = $platform;
          }
        }
      }
    }

    foreach ($platforms as $platform) {
      $key = $platform->platform_id;
      $buttons[$key] = [
        '#theme' => 'webshare_' . $key,
        '#url' => $url,
        '#platform' => $platform,
      ];

      if ($config->get('style') == 'webshare') {
        $image_src = $platform->image;
        // If image path doesn't contain module path, prepend it
        if (!str_contains($image_src, $module_path) && !str_starts_with($image_src, 'http') && !str_starts_with($image_src, '/')) {
          $image_src = $module_path . '/img/' . $image_src;
        }
        // Handle both relative and absolute paths
        if (!str_starts_with($image_src, 'http') && !str_starts_with($image_src, '/')) {
          $image_src = $base_url . '/' . $image_src;
        } elseif (str_starts_with($image_src, '/')) {
          $image_src = $base_url . $image_src;
        }

        $buttons[$key]['#content'] = [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'src' => $image_src,
            'title' => $this->t($platform->title),
            'alt' => $this->t($platform->title),
          ],
        ];
      } elseif ($config->get('style') == 'custom') {
        $buttons[$key]['#content'] = $this->t($platform->name);
      }
    }
    $build['#buttons'] = $buttons;
    $build['#webshare_links_id'] = 'webshare-links-' . $id;

    if ($config->get('display_title')) {
      $build['#title'] = $this->t($config->get('title'));
    }

    $build['#share_icon'] = [
      'id' => 'webshare-trigger-' . $id,
      'src' => $base_url . '/' . $module_path . '/img/' . $config->get('share_icon.image'),
      'alt' => $this->t($config->get('share_icon.alt')),
    ];

    if ($config->get('style') == 'webshare') {
      if ($config->get('collapsible')) {
        $library = [
          'webshare/webshare-styles',
          'webshare/webshare-script',
        ];
      } else {
        $library = [
          'webshare/webshare-styles',
          'webshare/webshare-script',
        ];
      }
    } elseif ($config->get('style') == 'custom') {
      if ($config->get('include_css')) {
        $library = [
          'webshare/webshare-styles',
        ];
      }
    }

    if (!empty($library)) {
      $build['#attached'] = [
        'library' => $library,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function isRestricted($view_mode) {
    $config = $this->configFactory->get('webshare.settings');

    switch ($view_mode) {
      case 'search_result':
      case 'search_index':
      case 'rss':
          return TRUE;
    }

    $restricted_pages = $config->get('restricted_pages.pages');

    if (is_array($restricted_pages) && !empty($restricted_pages)) {
      $restriction_type = $config->get('restricted_pages.type');

      // Replace a single / with <front> so it matches with the front path.
      if (($index = array_search('/', $restricted_pages)) !== FALSE) {
        $restricted_pages[$index] = '<front>';
      }

      /** @var \Drupal\system\Plugin\Condition\RequestPath $request_path_condition */
      $request_path_condition = $this->conditionManager->createInstance('request_path', [
        'pages' => implode("\n", $restricted_pages),
        'negate' => $restriction_type == 'show',
      ]);

      return $request_path_condition->execute();
    }

    return FALSE;
  }

  /**
   * Get default URL template for legacy platforms.
   */
  private function getDefaultUrlTemplate($platform_id) {
    $templates = [
      'facebook_share' => 'https://www.facebook.com/sharer/sharer.php?u=[url]',
      'x' => 'https://twitter.com/intent/tweet?url=[url]&text=[title]',
      'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=[url]',
      'whatsapp' => 'https://api.whatsapp.com/send?text=[title]%20[url]',
      'copy' => '',
    ];
    return $templates[$platform_id] ?? '';
  }
}
