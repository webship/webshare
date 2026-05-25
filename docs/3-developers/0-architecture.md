# Architecture Overview

Webshare is a small Drupal module (~5 PHP classes + 1 SDC + 1 Twig
extension + 1 install file) deliberately structured around **one
canonical render path** so the same share rail appears wherever it is
placed.

## Module Layout

```
webshare/
├── components/share/
│   ├── share.component.yml      # SDC schema (props, libraryOverrides)
│   ├── share.twig               # SDC template
│   ├── share.css                # Auto-attached scoped styles
│   └── share.js                 # Auto-attached scoped JS (copy + native share)
├── config/
│   ├── install/webshare.settings.yml   # Default config (icon_map, fallback buttons)
│   └── schema/                         # Config + block.settings.share schema
├── img/                         # Bundled platform SVGs (fallback when no icon pack)
├── src/
│   ├── WebshareService.php             # Renders the rail via the SDC
│   ├── WebshareServiceInterface.php
│   ├── Form/
│   │   ├── WebshareConfigForm.php      # /admin/config/services/webshare
│   │   ├── PlatformForm.php            # Add/Edit platform modal
│   │   └── PlatformDeleteForm.php      # Delete platform modal
│   ├── Plugin/
│   │   ├── Block/WebshareBlock.php     # Block plugin id "share"
│   │   └── views/field/WebshareField.php
│   └── TwigExtension/WebshareTwigExtension.php  # webshare_share_data() Twig fn
├── templates/                   # Legacy hook_theme templates (per-platform)
├── tests/                       # webship-js BDD suite (see Testing)
├── webshare.install             # Schema for webshare_platforms + updates
├── webshare.module              # hook_theme() (legacy)
├── webshare.routing.yml         # Settings + platform CRUD routes
├── webshare.services.yml        # webshare.service + Twig extension
└── webshare.permissions.yml     # "administer webshare"
```

## The Three Rendering Paths

All three paths end up rendering the **same** `webshare:share` SDC with
the same set of props.

```
┌─────────────────────────┐
│ Block plugin "share"    │ ──┐
└─────────────────────────┘   │
                              │
┌─────────────────────────┐   │  ┌────────────────────────┐  ┌──────────────────┐
│ Canvas component        │   ├──│ WebshareService::build │──│ webshare:share   │
│ sdc.webshare.share      │ ──┤   │  → #type: component   │  │ SDC (.twig/.css/ │
└─────────────────────────┘   │  └────────────────────────┘  │  .js)            │
                              │                              └──────────────────┘
┌─────────────────────────┐   │
│ Custom Twig embed       │ ──┘
│ {% embed 'webshare:share' ...
└─────────────────────────┘
```

### Path 1 - The Share block

`WebshareBlock::build()` ([src/Plugin/Block/WebshareBlock.php][block])
collects the per-instance settings (heading, alignment, orientation,
…), calls `WebshareService::build($url, $id, $options)`, and returns
the resulting render array. The block plugin id is `share`.

### Path 2 - Drupal Canvas

The SDC is auto-exposed as `sdc.webshare.share` to Canvas's component
catalogue. When Canvas renders the component, its Twig template calls
the `webshare_share_data(url, options)` Twig function (registered by
`WebshareTwigExtension`) to obtain the resolved share URL and the
enabled platform link list from `WebshareService::build()`.

### Path 3 - Custom Twig embed

A theme or sibling module can call the SDC directly:

```twig
{% embed 'webshare:share' with {
  heading: 'Share',
  orientation: 'horizontal',
  native_share: true,
} only %}{% endembed %}
```

The Twig's `_share_data` fallback fills in the resolved `url` and
`platforms` array from the Webshare service - the embed only needs to
pass the presentation props.

## Single Source of Truth - WebshareService

[`WebshareService::build()`][service] is the one place that:

- Reads enabled platforms from the `webshare_platforms` database table
  (falling back to `webshare.settings.buttons` if the table is empty).
- Substitutes `[url]` / `[title]` tokens into each platform's
  `url_template` to produce the final share link.
- Resolves the icon: prefers the Icons API mapping
  (`webshare.settings.icon_map`), falls back to the bundled SVG.
- Returns a render array with `#type: component`, `#component:
  webshare:share`, the full prop set, and a `#cache` block that
  includes the `webshare_platforms` cache tag and the `url` cache
  context.

Every rendering path goes through this method (block, Canvas, custom
Twig). Cache tag bubbling is handled by the render array's `#cache`
block - see [API Reference](1-api-reference.md#cache-tag-webshare_platforms).

## Cache Strategy

The share rail has two cache concerns:

1. **Cross-instance state**: which platforms are enabled, their order,
   their templates, icon mappings. Invalidated by the
   `webshare_platforms` cache tag, which is set on the rail's render
   array and invalidated by:
    - `WebshareConfigForm::submitForm()`,
    - `PlatformForm::submitForm()` (add + edit),
    - `PlatformDeleteForm::submitForm()`.

2. **Per-request state**: the share URL changes per page. Handled by
   the `url` cache context on the render array.

This means a single edit on `/admin/config/services/webshare`
invalidates the anonymous page cache for every page that contains a
share rail - block, Canvas, or otherwise.

## Next

- [API Reference](1-api-reference.md) - class signatures + Twig
  function.
- [Database Schema](2-database-schema.md) - the `webshare_platforms`
  table.
- [The Share SDC Component](3-sdc-component.md) - props, defaults, and
  Twig contract.

[block]: https://git.drupalcode.org/project/webshare/-/blob/2.0.x/src/Plugin/Block/WebshareBlock.php
[service]: https://git.drupalcode.org/project/webshare/-/blob/2.0.x/src/WebshareService.php
