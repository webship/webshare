# The Share SDC Component

`webshare:share` is the Single-Directory Component that renders the share
rail. It lives in `components/share/` of the module and is the single
template every rendering path goes through.

## Files

```
components/share/
├── share.component.yml   # Schema (props, libraryOverrides)
├── share.twig            # Template
├── share.css             # Scoped styles (auto-attached)
└── share.js              # Scoped behaviour: copy + native share (auto-attached)
```

The SDC follows the standard Drupal core SDC layout: the `.css` and
`.js` are auto-attached when the component is rendered.

## Schema (component.yml)

Schema is declared in `components/share/share.component.yml`. The keys
below are the **publicly documented props** intended for callers
(block, Canvas, Twig embed). A few props (`webshare_links_id`,
`native_label`, `native_icon`, `native_icon_html`) are intentionally
undeclared in the schema — they are implementation details handled by
the Twig's `|default(...)` filters and are filled in by
`WebshareService::build()` or `webshare_share_data()`.

| Prop                | Type    | Default      | Allowed values                              |
| ------------------- | ------- | ------------ | ------------------------------------------- |
| `heading`           | string  | `Share`      | Any text. Empty suppresses the heading.     |
| `display_title`     | boolean | `true`       |                                             |
| `alignment`         | string  | `end`        | `start`, `end`                              |
| `orientation`       | string  | `vertical`   | `horizontal`, `vertical`                    |
| `mobile_visibility` | string  | `all`        | `all`, `hide_mobile`, `mobile_only`         |
| `native_share`      | boolean | `false`      |                                             |
| `placement`         | string  | `rail-end`   | `inline`, `rail-end`                        |
| `url`               | string  | `''`         | Absolute URL (or empty → current request).  |
| `share_title`       | string  | `''`         | Forwarded to Web Share API + `[title]` token. |
| `share_text`        | string  | `''`         | Forwarded to Web Share API.                 |

The `platforms` array prop is **deliberately undeclared** — Drupal
Canvas cannot match an array-of-objects shape to a field widget, so
declaring it would disqualify the SDC from the Canvas component
catalogue. The Twig falls back to the `webshare_share_data()` Twig
function to obtain the list when no `platforms` prop is passed.

## Twig contract

Inside `share.twig`:

```twig
{% set _share_data = (platforms is not defined or not platforms) or (url is not defined or not url)
  ? webshare_share_data(url|default(''), {
      alignment: alignment|default('end'),
      orientation: orientation|default('vertical'),
      mobile_visibility: mobile_visibility|default('all'),
      placement: placement|default('rail-end'),
      native_share: native_share|default(false),
    })
  : null %}
{% set url = (url is defined and url) ? url : (_share_data ? _share_data.url : '') %}
{% set platforms = (platforms is defined and platforms) ? platforms : (_share_data ? _share_data.platforms : []) %}
```

This means callers can either:

1. **Pass `platforms` + `url` directly** — full control, no service
   call. This is what `WebshareBlock` / `WebshareService::build()`
   does.
2. **Pass only the presentation props** — `platforms` and `url` are
   resolved by the Twig function. This is what Drupal Canvas does.

## DOM Output

```html
<nav class="webshare webshare--horizontal webshare--inline webshare--align-start"
     aria-label="Share"
     data-webshare-url="https://example.com/page"
     data-webshare-title=""
     data-webshare-text=""
     data-component-id="webshare:share"
     data-once="webshare-native">
  <h3 id="webshare-heading-…" class="webshare__heading">Share</h3>
  <ul id="webshare-links-…" class="webshare__list" aria-labelledby="webshare-heading-…">
    <li class="webshare__item webshare__item--native" data-webshare-native>
      <button type="button" class="webshare__native-button" aria-label="Share">
        <img src="/modules/contrib/webshare/logo.svg" aria-hidden="true" class="webshare__native-icon" width="20" height="20">
        <span class="visually-hidden">Share</span>
      </button>
    </li>
    <li class="webshare__item webshare__item--linkedin">
      <a href="https://www.linkedin.com/..." target="_blank" rel="noopener noreferrer"
         aria-label="Share on LinkedIn (opens in a new tab)">
        <img src="/modules/contrib/webshare/img/linkedin.svg" aria-hidden="true" width="20" height="20">
        <span class="visually-hidden">(opens in a new tab)</span>
      </a>
    </li>
    <!-- one <li> per enabled platform -->
  </ul>
</nav>
```

### Copy button variant

The `copy` platform renders as a `<button>` instead of `<a>`, with a
`data-webshare-copy="<url>"` attribute. The bundled `share.js` attaches
a `click` listener that calls `navigator.clipboard.writeText(url)` and
toggles a `webshare--copied` class on the button for a moment.

### Icons API output

When `webshare.settings.icon_map.<platform>.pack` resolves to a
registered icon pack, the `<img>` is replaced by the Icons API render
output (typically an inline `<svg>`).

## Using the SDC From Custom Twig

```twig
{# Minimal — service fills in platforms + url #}
{% embed 'webshare:share' with {
  heading: 'Share this story',
  orientation: 'horizontal',
  native_share: true,
} only %}{% endembed %}

{# Full control — pass everything #}
{% set my_platforms = [
  { key: 'linkedin', url: 'https://...', title: 'Share on LinkedIn',
    icon_src: '/path/to/icon.svg', icon_html: '', icon_alt: 'Share',
    label: '', is_copy: false },
] %}
{% embed 'webshare:share' with {
  heading: 'Custom rail',
  platforms: my_platforms,
  url: 'https://example.com/page',
} only %}{% endembed %}
```

## Styling

`share.css` defines its own scoped variables; override them from your
theme's CSS to restyle the rail without forking the SDC:

```css
.webshare {
  --webshare-bg:                #f6f7f9;
  --webshare-color:             currentColor;
  --webshare-item-size:         32px;
  --webshare-item-radius:       6px;
  --webshare-rail-top:          2rem;     /* sticky offset for rail-end */
  --webshare-rail-gap:          1rem;
  --webshare-copied-bg:         #1f883d;
  --webshare-copied-color:      #fff;
}
```
