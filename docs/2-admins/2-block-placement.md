# Block Placement

The classic way to add the Webshare share rail to your site is to place
the **Share** block in a theme region via Drupal's Block layout UI.

This page covers all the block-level options, plus visibility scoping
and how to place multiple instances on the same site.

## Quick Start

1. **Administration > Structure > Block layout**
   (`/admin/structure/block`).
2. Click **Place block** next to the region you want (e.g. *Content Above*).
3. Search for **Share** in the dialog, click **Place block**.
4. Set the block's display options (described below), pick a visibility
   rule if needed, and click **Save block**.

The Share block plugin id is `share`. Inside the database/config its
plugin reference is `share`; in the DOM the block renders inside a
wrapper `<div id="block-<machine-name>">`.

## Block Display Settings

These props are stored on the block instance and forwarded to the
`webshare:share` SDC component as schema-validated inputs.

| Setting             | Values                              | Default      |
| ------------------- | ----------------------------------- | ------------ |
| Title               | text                                | `Share`      |
| Display title       | checkbox                            | enabled      |
| Alignment           | `start` / `end`                     | `end`        |
| Orientation         | `horizontal` / `vertical`           | `vertical`   |
| Mobile visibility   | `all` / `hide_mobile` / `mobile_only` | `all`      |
| Show native share button | checkbox                       | disabled     |
| Placement           | `inline` / `rail-end`               | `rail-end`   |

### Title and Display Title

Setting **Display title** to off suppresses the visible `<h3>` heading
but **keeps** the value as the `aria-label` on the `<nav>` so screen
readers still announce the landmark. Leave the Title blank only if you
also unset Display title.

### Alignment

`start` and `end` are logical, RTL-aware values. In English (LTR), `end`
means the rail sticks to the right side of the content; in Arabic
(RTL), `end` means the left side.

### Orientation

`horizontal` lays the buttons in a row; `vertical` stacks them in a
column. Pick whichever fits the region you placed the block into.

### Placement: inline vs rail-end

- `inline` keeps the rail in the normal content flow — it sits where
  the block is placed in the region. Best for top-of-article share
  bars.
- `rail-end` makes the rail a sticky column floating beside the
  content. The CSS uses `position: sticky` + `float: inline-end` and
  is only applied on viewports ≥ 992px (medium+). On smaller
  viewports the rail collapses back to inline flow. Best for
  long-form blog posts where the rail follows the reader.

### Mobile Visibility

- `all` — visible at every viewport (default).
- `hide_mobile` — hidden at viewports ≤ 768px.
- `mobile_only` — visible only at viewports ≤ 768px.

The breakpoint is the same 768px Drupal uses for the Olivero theme.

### Native Share Button

When enabled, the rail renders an extra `<button>` at the start of the
list. On supporting browsers it invokes the Web Share API and opens
the device share sheet. On non-supporting browsers the button is hidden
by CSS. See [Using the Share Rail](../1-users/1-using-the-share-rail.md#native-share-button).

## Visibility Restrictions

Use the standard Block visibility tabs at the bottom of the block form
to control where the rail appears. Common patterns:

- **Pages**: scope to `/blog/*` if you only want the rail on blog
  content.
- **Pages**: set to `<front>` to limit the block to the front page.
- **Content types**: tick *Article* to show only on article nodes.
- **User roles**: tick *Anonymous user* to hide from authenticated
  visitors.

## Multiple Instances

Place the **Share** block multiple times to render the rail in more
than one region (e.g. above and below the content) or with different
settings per region. Each placement has its own machine-name-derived
DOM id (`#block-<machine-name>`), so CSS and assertions can target
each individually.

The webship-js test suite uses exactly this pattern — see the
[Testing guide](../3-developers/5-testing.md).

## Removing a Block

To remove a Share block:

1. **Structure > Block layout**, find the row.
2. Click the **Disable** or **Remove** operation.

`Remove` deletes the block instance; `Disable` keeps the configuration
but hides the block. Either invalidates the `webshare_platforms` cache
tag.
