# Extending Webshare

Webshare is intentionally compact - most extension work happens through
**data** (platforms in the database, icon mappings in config) rather
than through plugins or events. This page collects the most common
extension scenarios.

## Adding a Platform Programmatically

Custom platforms can be inserted directly into the `webshare_platforms`
table (`hook_install()` / `hook_update_N()` / a deployment recipe):

```php
\Drupal::database()->insert('webshare_platforms')
  ->fields([
    'platform_id' => 'signal',
    'name' => 'Signal',
    'title' => 'Share on Signal',
    'enabled' => 1,
    'image' => 'signal.svg',
    'weight' => 13,
    'url_template' => 'https://signal.me/#p=eu&body=[title]%20[url]',
    'is_custom' => 1,
    'created' => \Drupal::time()->getRequestTime(),
    'updated' => \Drupal::time()->getRequestTime(),
  ])
  ->execute();

\Drupal\Core\Cache\Cache::invalidateTags(['webshare_platforms']);
```

Mind the cache-tag invalidation at the end - otherwise existing
anonymous-cache entries will keep rendering the rail without the new
platform.

## Shipping a Recipe / Distribution Preset

A distribution that wants to ship a preset (specific platform set +
icon-pack mapping) can:

1. Provide `config/install/webshare.settings.yml` with the
   `icon_map` block populated for the distribution's preferred icon
   pack.
2. Provide a `hook_install()` (or a recipe action) that inserts the
   platform rows the distribution wants enabled by default.

For example, the Vartheme BS5 distribution ships a preset that wires
the platforms to Bootstrap Icons:

```yaml
# config/install/webshare.settings.yml
icon_map:
  linkedin:
    pack: bootstrap_icons
    icon: linkedin
  facebook_share:
    pack: bootstrap_icons
    icon: facebook
  x:
    pack: bootstrap_icons
    icon: twitter-x
  whatsapp:
    pack: bootstrap_icons
    icon: whatsapp
  copy:
    pack: bootstrap_icons
    icon: clipboard
native_share_icon:
  pack: bootstrap_icons
  icon: share-fill
```

See [Drupal Core Icons API](../2-admins/5-icons-api.md) for the
runtime resolution.

## Replacing the Bundled Icon Without the Icons API

For a single platform you can override the stored SVG path via the
Edit modal (Admin > Webshare > Edit) or by updating the row:

```php
\Drupal::database()->update('webshare_platforms')
  ->fields(['image' => 'my-theme://icons/linkedin.svg'])
  ->condition('platform_id', 'linkedin')
  ->execute();
\Drupal\Core\Cache\Cache::invalidateTags(['webshare_platforms']);
```

`WebshareService::build()` accepts any of:

- A bare filename (resolved against `<module>/img/`).
- An absolute path beginning with `/`.
- An absolute URL starting with `http`.

## Theming the Rail

The SDC's CSS uses CSS custom properties for every visual concern.
Override them from your theme without forking the SDC:

```css
:root {
  --webshare-bg:                #f6f7f9;
  --webshare-color:             currentColor;
  --webshare-item-size:         32px;
  --webshare-item-radius:       6px;
  --webshare-rail-top:          2rem;
  --webshare-rail-gap:          1rem;
  --webshare-copied-bg:         #1f883d;
  --webshare-copied-color:      #fff;
}
```

For deeper changes (extra markup around each list item, alternative
heading element, etc.), copy `components/share/share.twig` into your
theme's `components/webshare/share/` directory - Drupal core's SDC
theme-override discovery will pick it up.

## Embedding the SDC From Custom Twig

```twig
{# Minimal - service fills in platforms + url. #}
{% embed 'webshare:share' with {
  heading: 'Share',
  orientation: 'horizontal',
  native_share: true,
} only %}{% endembed %}
```

Useful when a theme wants to place the rail in a region without going
through the Block layout UI - for example, in the same Twig file as
the page title.

## Hooking Into a Share Click (JS)

The bundled `share.js` exposes no JS hooks. To react to a share click
in your own behaviour, attach a listener at the document level:

```js
Drupal.behaviors.myShareTracker = {
  attach(context) {
    once('my-share-tracker', '.webshare a, .webshare__native-button, .webshare__copy-button', context)
      .forEach((el) => {
        el.addEventListener('click', (event) => {
          const platform = event.currentTarget.closest('.webshare__item');
          const key = (platform?.className.match(/webshare__item--(\S+)/) || [])[1];
          if (key) gtag?.('event', 'share_click', { platform: key });
        });
      });
  },
};
```

## Adding New Step Definitions to the Test Suite

See [Testing](5-testing.md#adding-a-scenario). The rule of thumb:
steps stay **pure browser**. Any state setup that needs Drush or PHP
belongs in the CI before_script (or a local dev setup script), not in
a Cucumber step.

## Reporting Bugs / Submitting Patches

- Issue queue:
  [drupal.org/project/issues/webshare](https://www.drupal.org/project/issues/webshare)
- Source: [git.drupalcode.org/project/webshare](https://git.drupalcode.org/project/webshare)

When reporting a bug, include the cucumber-js scenario that reproduces
it (or describe the click-by-click flow on a fresh Drupal Standard
install).
