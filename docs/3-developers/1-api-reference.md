# API Reference

The Webshare public API consists of one service, one service interface,
one Twig function, and one cache tag. There is no event subscriber API
— platforms are stored as data, not as plugins.

## `webshare.service` (`WebshareService`)

### Class

`\Drupal\webshare\WebshareService` (implements
`\Drupal\webshare\WebshareServiceInterface`).

### Service ID

```yaml
webshare.service
```

### Constructor

```php
public function __construct(
  ConfigFactoryInterface $config_factory,
  ModuleExtensionList $module_extension_list,
  Connection $database,
  ?RendererInterface $renderer = NULL,
  $icon_pack_manager = NULL,   // \Drupal\Component\Plugin\PluginManagerInterface
);
```

The `$icon_pack_manager` is fetched from `plugin.manager.icon_pack`
when the service exists (Drupal 11.1+ core, or `ui_icons` contrib).
When the service is missing, the constructor receives `NULL` and the
Icons API integration is inert.

### `build(string $url, string $id, array $options = []): array`

Produce a render array for the share rail.

**Parameters**

- `$url` — absolute URL to share. Substituted into each platform's
  `url_template` via the `[url]` token. Forwarded to the rendered
  component as the `url` prop and the `data-webshare-url` HTML
  attribute.
- `$id` — unique id used to build the `<ul id="webshare-links-{id}">`
  inside the component. The block plugin derives this from the alias
  manager; custom callers can pass any string.
- `$options` — presentation overrides forwarded to the SDC. Keys:
  - `heading` — display heading (string). Empty string suppresses.
  - `alignment` — `start` | `end` (default `end`).
  - `orientation` — `horizontal` | `vertical` (default `vertical`).
  - `mobile_visibility` — `all` | `hide_mobile` | `mobile_only`
    (default `all`).
  - `placement` — `inline` | `rail-end` (default `rail-end`).
  - `native_share` — bool, render the native share button (default
    FALSE).
  - `share_title` — string passed to the Web Share API and substituted
    into `[title]` tokens.
  - `share_text` — string passed to the Web Share API.

**Return value**

A render array of the shape:

```php
[
  '#type' => 'component',
  '#component' => 'webshare:share',
  '#props' => [
    'url' => $url,
    'share_title' => '...',
    'share_text' => '...',
    'webshare_links_id' => 'webshare-links-' . $id,
    'alignment' => '...',
    'orientation' => '...',
    'mobile_visibility' => '...',
    'placement' => '...',
    'native_share' => bool,
    'native_label' => 'Share',
    'native_icon' => '/.../logo.svg',
    'native_icon_html' => '',
    'platforms' => [ /* per-platform link arrays */ ],
  ],
  '#cache' => [
    'tags' => ['config:webshare.settings', 'webshare_platforms'],
    'contexts' => ['url'],
  ],
]
```

### Lower-level helpers

`WebshareService` exposes only `build()` publicly. Token substitution
and icon-pack resolution are private implementation details.

## Twig function — `webshare_share_data(url, options)`

Registered by `WebshareTwigExtension`. Used by the `webshare:share`
SDC twig to obtain share data when the component is rendered outside
the block plugin (e.g. via Drupal Canvas).

**Signature**

```twig
{% set data = webshare_share_data(url, options) %}
```

- `url` *(optional)* — explicit URL to share. Falls back to the current
  request URL when empty.
- `options` *(optional)* — same options hash as `WebshareService::build()`.

**Return value**

```twig
{
  url: '<resolved-url>',
  platforms: [
    {
      key: 'linkedin',
      url: 'https://www.linkedin.com/sharing/share-offsite/?url=...',
      title: 'Share on LinkedIn',
      icon_src: '/modules/contrib/webshare/img/linkedin.svg',
      icon_html: '',          # populated when Icons API matches
      icon_alt: 'Share on LinkedIn',
      label: '',
      is_copy: false,
    },
    ...
  ],
}
```

The Twig function also **bubbles** the cache metadata from
`WebshareService::build()` into the active render context via
`renderer->render($marker)` so the `webshare_platforms` cache tag
reaches the response — without this, Canvas-rendered rails would never
invalidate when platforms change.

## Cache tag — `webshare_platforms`

Invalidate this tag whenever a platform-affecting change is made (your
own code adding / disabling / renaming a platform programmatically):

```php
\Drupal\Core\Cache\Cache::invalidateTags(['webshare_platforms']);
```

Webshare itself invalidates the tag from three places:

- `WebshareConfigForm::submitForm()` — after saving the platform table.
- `PlatformForm::submitForm()` — after add or edit of a platform.
- `PlatformDeleteForm::submitForm()` — after deleting a platform.

The render array emitted by `WebshareService::build()` carries the same
tag, so the tag invalidation flushes both the block render cache and
the anonymous page cache for every page that contains a rail.

## Hooks

Webshare implements only `hook_theme()` in `webshare.module` to
register legacy `webshare-platform.html.twig` templates for backwards
compatibility with the `vartheme_social` icon pack. New integrations
should target the SDC, not these legacy theme hooks.
