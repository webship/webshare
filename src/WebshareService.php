<?php

namespace Drupal\webshare;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The Drupal Core Icon Pack plugin manager, when available.
   *
   * Provided by Drupal core (11.1+) — and also by the `ui_icons` contrib
   * module on older sites — under the `plugin.manager.icon_pack` service
   * id. We hold a nullable reference so the module works on Drupal versions
   * that pre-date the Icons API.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|null
   */
  protected $iconPackManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an WebshareService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Configuration Factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The extension module list.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Render\RendererInterface|null $renderer
   *   The renderer.
   * @param \Drupal\Component\Plugin\PluginManagerInterface|null $icon_pack_manager
   *   The Icon Pack plugin manager. NULL when neither Drupal Core 11.1+
   *   nor the ui_icons contrib module is enabled.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleExtensionList $module_extension_list,
    Connection $database,
    ?RendererInterface $renderer = NULL,
    $icon_pack_manager = NULL,
  ) {
    $this->configFactory = $config_factory;
    $this->moduleExtensionList = $module_extension_list;
    $this->database = $database;
    $this->renderer = $renderer ?: \Drupal::service('renderer');
    $this->iconPackManager = $icon_pack_manager;
  }

  /**
   * Factory for the Drupal service container.
   *
   * The Icons API is not yet a hard dependency, so the matching plugin
   * manager is injected only when present.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('extension.list.module'),
      $container->get('database'),
      $container->get('renderer'),
      $container->has('plugin.manager.icon_pack')
        ? $container->get('plugin.manager.icon_pack')
        : NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build($url, $id, array $options = []) {
    global $base_url;
    $config = $this->configFactory->get('webshare.settings');
    $module_path = $this->moduleExtensionList->getPath('webshare');
    $buttons = [];

    // Resolve presentation options. Heading / alignment / orientation /
    // mobile_visibility / placement / native_share all live on the block
    // (and any future callers) — the module config no longer carries them.
    $alignment = $options['alignment'] ?? 'end';
    if (!in_array($alignment, ['start', 'end'], TRUE)) {
      $alignment = 'end';
    }
    $orientation = $options['orientation'] ?? 'vertical';
    if (!in_array($orientation, ['horizontal', 'vertical'], TRUE)) {
      $orientation = 'vertical';
    }
    $mobile_visibility = $options['mobile_visibility'] ?? 'all';
    if (!in_array($mobile_visibility, ['all', 'hide_mobile', 'mobile_only'], TRUE)) {
      $mobile_visibility = 'all';
    }
    $native_share = $options['native_share'] ?? FALSE;
    $placement = $options['placement'] ?? 'rail-end';
    if (!in_array($placement, ['inline', 'rail-end'], TRUE)) {
      $placement = 'rail-end';
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

    $icon_map = $config->get('icon_map') ?: [];
    $platform_items = [];
    foreach ($platforms as $platform) {
      $key = $platform->platform_id;
      $is_copy = empty($platform->url_template);

      // Resolve share URL — placeholder substitution for templated platforms;
      // '#' for the copy-to-clipboard platform (JS handles the click).
      $share_url = '#';
      if (!$is_copy) {
        $share_url = strtr($platform->url_template, [
          '[url]' => rawurlencode($url),
          '[title]' => rawurlencode((string) ($options['share_title'] ?? '')),
        ]);
      }

      // Prefer a Drupal Core Icons API icon when one is mapped to this
      // platform and the referenced icon pack is registered. Fall back to
      // the legacy bundled SVG image otherwise.
      $icon_html = $this->renderIconHtml($icon_map[$key] ?? NULL);
      $icon_src = '';
      if ($icon_html === '') {
        $image_src = $platform->image;
        if (!str_contains($image_src, $module_path) && !str_starts_with($image_src, 'http') && !str_starts_with($image_src, '/')) {
          $image_src = $module_path . '/img/' . $image_src;
        }
        if (!str_starts_with($image_src, 'http') && !str_starts_with($image_src, '/')) {
          $image_src = $base_url . '/' . $image_src;
        }
        elseif (str_starts_with($image_src, '/')) {
          $image_src = $base_url . $image_src;
        }
        $icon_src = $image_src;
      }

      $platform_items[] = [
        'key' => $key,
        'url' => $share_url,
        'title' => (string) $this->t($platform->title),
        'icon_src' => $icon_src,
        'icon_html' => $icon_html,
        'icon_alt' => (string) $this->t($platform->title),
        'label' => '',
        'is_copy' => $is_copy,
      ];
    }

    // Native share button icon: Icons API entry first, module logo as fall.
    $native_icon_html = $this->renderIconHtml($config->get('native_share_icon'));
    $native_icon_src = $native_icon_html === '' ? '/' . $module_path . '/logo.svg' : '';

    // Render through the Webshare single-directory component.
    $build = [
      '#type' => 'component',
      '#component' => 'webshare:share',
      '#props' => [
        'url' => $url,
        'share_title' => (string) ($options['share_title'] ?? ''),
        'share_text' => (string) ($options['share_text'] ?? ''),
        'webshare_links_id' => 'webshare-links-' . $id,
        'alignment' => $alignment,
        'orientation' => $orientation,
        'mobile_visibility' => $mobile_visibility,
        'placement' => $placement,
        'native_share' => (bool) $native_share,
        'native_label' => (string) $this->t('Share'),
        // Module-relative path to the Webshare logo, used as the native
        // share button icon when the Icons API has no mapping.
        'native_icon' => $native_icon_src,
        'native_icon_html' => $native_icon_html,
        'platforms' => $platform_items,
      ],
      // The single-directory component (webshare:share) auto-attaches its
      // own scoped CSS and JS, so no #attached library is required here.
    ];

    // Heading is now exclusively a caller-provided prop. The block builds
    // it from its own "Display title" + "Title" settings; an empty string
    // suppresses the heading entirely.
    if (!empty($options['heading'])) {
      $build['#props']['heading'] = (string) $options['heading'];
    }

    return $build;
  }

  /**
   * Renders an icon via the Drupal Core Icons API if one is configured.
   *
   * Supports both Drupal core's built-in `#type: icon` render element
   * (Drupal 11.1+) and the matching plugin manager exposed by the
   * `ui_icons` contrib module. When neither service is available, or the
   * referenced icon pack is not registered, an empty string is returned
   * so callers fall back to the legacy `<img>` rendering.
   *
   * @param array|null $reference
   *   Icon reference array with `pack` and `icon` keys, or NULL.
   *
   * @return string
   *   The rendered icon markup, or an empty string when no icon can be
   *   resolved.
   */
  protected function renderIconHtml($reference): string {
    if (!$this->iconPackManager || !is_array($reference)) {
      return '';
    }
    $pack_id = $reference['pack'] ?? '';
    $icon_id = $reference['icon'] ?? '';
    if ($pack_id === '' || $icon_id === '') {
      return '';
    }
    // Only render when the icon pack is actually registered.
    if (!$this->iconPackManager->hasDefinition($pack_id)) {
      return '';
    }
    $build = [
      '#type' => 'icon',
      '#pack_id' => $pack_id,
      '#icon_id' => $icon_id,
    ];
    return (string) $this->renderer->renderInIsolation($build);
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
