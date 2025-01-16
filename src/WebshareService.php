<?php

namespace Drupal\webshare;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * Constructs an WebshareService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Configuration Factory.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The extenstion list module.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConditionManager $condition_manager, ModuleExtensionList $module_extension_list) {
    $this->configFactory = $config_factory;
    $this->conditionManager = $condition_manager;
    $this->moduleExtensionList = $module_extension_list;
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

    $share_buttons = $config->get('buttons');
    uasort($share_buttons, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    foreach ($share_buttons as $key => $button) {
      if ($key == $button['enabled']) {
        $build['#attributes']['class'][] = 'webshare-has-like';
      }
      elseif ($button['enabled']) {
        $buttons[$key] = [
          '#theme' => 'webshare_' . $key,
          '#url' => $url,
        ];

        if ($config->get('style') == 'webshare') {
          $buttons[$key]['#content'] = [
            '#type' => 'html_tag',
            '#tag' => 'img',
            '#attributes' => [
              'src' => $base_url . '/' . $module_path . '/img/' . $button['image'],
              'title' => $this->t($button['title']),
              'alt' => $this->t($button['title']),
            ],
          ];
        }
        elseif ($config->get('style') == 'custom') {
          $buttons[$key]['#content'] = $this->t($button['name']);
        }
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
      }
      else {
        $library = [
          'webshare/webshare-styles',
          'webshare/webshare-script',
        ];
      }
    }
    elseif ($config->get('style') == 'custom') {
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

}
