# Drupal Canvas Integration (Developer)

This page documents how Webshare integrates with [Drupal Canvas][canvas]
at the code level — for the administrator-facing flow see the
[admin Canvas guide](../2-admins/3-canvas-integration.md).

## How the SDC is Exposed

When the `drupal/canvas` module is enabled, Canvas discovers every
Single-Directory Component in the active modules and creates a
**`component` config entity** for each. Webshare's SDC therefore lands
in the catalog as:

```
Config entity id:  sdc.webshare.share
Provider:          canvas
Plugin id:         sdc.webshare.share
Active version:    <16-char hash, e.g. 64add9738be2892f>
```

The hash is derived from the SDC schema; it changes if you bump the
schema in `share.component.yml`. Code that references the SDC version
should fetch it at runtime rather than hard-code the value:

```php
$component = \Drupal::entityTypeManager()
  ->getStorage('component')
  ->load('sdc.webshare.share');
$version = $component?->getActiveVersion();
```

## Placement Targets

Drupal Canvas accepts the component on three different entity types.
The Webshare test suite (`webship-js-test-canvas` job) seeds all three:

### 1. Canvas page (`canvas_page`)

```php
\Drupal\canvas\Entity\Page::create([
  'title' => 'Webshare Canvas Test',
  'status' => 1,
  'path' => ['alias' => '/webshare-canvas-test'],
  'components' => [
    'uuid' => '11111111-1111-4111-8111-111111111111',
    'component_id' => 'sdc.webshare.share',
    'component_version' => $version,
    'inputs' => [
      'heading' => 'Share this page',
      'display_title' => TRUE,
      'alignment' => 'start',
      'orientation' => 'horizontal',
      'mobile_visibility' => 'all',
      'native_share' => TRUE,
      'placement' => 'inline',
    ],
  ],
])->save();
```

### 2. Content template (`content_template`)

```php
\Drupal\canvas\Entity\ContentTemplate::create([
  'status' => TRUE,
  'content_entity_type_id' => 'node',
  'content_entity_type_bundle' => 'article',
  'content_entity_type_view_mode' => 'full',
  'component_tree' => [
    [
      'uuid' => '22222222-2222-4222-8222-222222222222',
      'component_id' => 'sdc.webshare.share',
      'component_version' => $version,
      'inputs' => [ /* same prop set */ ],
    ],
  ],
])->save();
```

### 3. Page region (`page_region`)

Used by Drupal CMS's Mercury theme to delegate header / footer
rendering to Canvas. The Webshare test setup adds the SDC into
`mercury.header` and `mercury.footer`:

```php
$pr = \Drupal::entityTypeManager()->getStorage('page_region')->load('mercury.header');
$tree = $pr->get('component_tree');
$tree['aaaaaaaa-1111-4111-8111-aaaaaaaaaaaa'] = [
  'uuid' => 'aaaaaaaa-1111-4111-8111-aaaaaaaaaaaa',
  'component_id' => 'sdc.webshare.share',
  'component_version' => $version,
  'inputs' => [
    'heading' => 'Share',
    'display_title' => FALSE,
    'alignment' => 'end',
    'orientation' => 'horizontal',
    'mobile_visibility' => 'all',
    'native_share' => TRUE,
    'placement' => 'inline',
    'share_title' => '',     // explicit empty strings ensure the
    'share_text' => '',      // data-webshare-* attributes are emitted
  ],
];
$pr->set('component_tree', $tree)->save();
```

## Why the `platforms` Prop Is Not Declared

Drupal Canvas matches each SDC prop against a field widget in its
editor. Array-of-objects shapes have no matching field type, so any
SDC declaring such a prop is **excluded** from the Canvas component
catalogue.

To keep `webshare:share` discoverable, the schema deliberately omits
`platforms` and `webshare_links_id`. The Twig fills them in via the
`webshare_share_data()` Twig function whenever the inputs do not
supply them. The block plugin, in contrast, builds the props
explicitly and supplies the full array — both paths render the same
markup.

## Cache Tag Bubbling

When Canvas renders an SDC, it does **not** call
`WebshareService::build()` — instead it calls the Twig template
directly with the prop inputs. The Twig then calls
`webshare_share_data(url, options)`, which invokes
`WebshareService::build()` for its side effect (the render array) and
extracts only the `url` + `platforms` parts.

For cache invalidation to work, the cache metadata of
`WebshareService::build()` has to bubble out of the Twig function and
into the active render context. The bubble is done explicitly:

```php
$marker = [];
BubbleableMetadata::createFromRenderArray($build)->applyTo($marker);
$marker['#markup'] = '';
\Drupal::service('renderer')->render($marker);
```

The marker render array carries only the cache metadata. Rendering it
in the current context causes Drupal's renderer to merge its tags /
contexts into the active frame, so the `webshare_platforms` cache tag
reaches the eventual response. Without this bubble, Canvas-rendered
rails would never invalidate when platforms change.

## Schema Versioning

If you fork the SDC and modify `share.component.yml`, Canvas will
bump the component_version hash automatically. Any existing
`canvas_page`, `content_template`, or `page_region` entries that
reference the old version must be updated to the new version, or
Canvas will refuse to render them with a schema mismatch error.

The Webshare test seed scripts resolve the version at runtime, so they
survive schema bumps without manual edits.

[canvas]: https://www.drupal.org/project/canvas
