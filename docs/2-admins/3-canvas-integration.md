# Drupal Canvas Integration

When [Drupal Canvas][canvas] is enabled alongside Webshare, the
`webshare:share` Single-Directory Component is **automatically exposed**
as a Canvas component (config entity id: `sdc.webshare.share`). You can
then place the rail through the Canvas editor exactly as you would any
other Canvas component.

This page covers the administrator-facing flow. For the developer
details (component schema, page_region tree structure), see the
[developer integration guide](../3-developers/4-canvas-integration.md).

## Requirements

- `drupal/canvas: ^1.4` installed and enabled.
- The Webshare module enabled.

Webshare ships `drupal/canvas: ^1.4` under `require-dev` for the test
build; the Canvas integration is **optional** for production sites.
When Canvas is not installed, the SDC is still usable (block, custom
Twig) - only the Canvas-component path is unavailable.

## Where the Share Component Can Be Placed

Drupal Canvas exposes three placement targets, all supported by
Webshare:

1. **Canvas pages** (`canvas_page` entities) - standalone pages
   composed entirely in the Canvas editor.
2. **Canvas content templates** (`content_template` config entities)
   - view-mode templates for content entities, e.g. `node.article.full`
   to render every Article in the Full view mode with a Canvas-driven
   layout.
3. **Canvas page regions** (`page_region` config entities) - site-wide
   regions a theme delegates to Canvas. On Drupal CMS's Mercury theme
   the `mercury.header` and `mercury.footer` page_regions are how the
   classic header / footer regions are populated.

## Placing the Share Component on a Canvas Page

1. **Administration > Content > Canvas pages** and open the page you
   want to edit (or create a new one).
2. Click **Edit in Canvas**.
3. Open the **Components** panel; search for **Share**.
4. Drag the **Share** component into the desired area of the page.
5. With the component selected, configure its props in the right-hand
   panel - same options as the [block settings](2-block-placement.md#block-display-settings):
   Title, Display title, Alignment, Orientation, Mobile visibility,
   Native share, Placement.
6. Click **Save** (or **Save and publish**).

## Placing the Share Component on a Content Template

1. Navigate to the content template you want to edit, e.g.
   **Administration > Structure > Content types > Article > Manage
   display > Full** (when that view mode is delegated to Canvas).
2. Click **Edit template**.
3. Drag **Share** into the layout - typically next to the body field.
4. Configure props (see above).
5. Click **Save**.

Every node of that type viewed in that view mode now renders the rail
through Canvas. There is no further block placement needed.

## Placing the Share Component in a Page Region

This is the path used on Drupal CMS (and any other theme that
delegates header / footer to Canvas via `page_region`).

1. **Administration > Structure > Canvas page regions**.
2. Click **Edit** next to the region you want (e.g. `mercury.header`).
3. Drag **Share** into the region's component tree.
4. Configure props - for the Drupal CMS test suite the Webshare
   suite uses Header (`alignment: end`, `display_title: false`) and
   Footer (`alignment: start`, `display_title: true`, heading
   "Share this page").
5. Click **Save**.

> **Tip:** When placing the SDC programmatically (e.g. in a recipe or
> deployment script), set `share_title` and `share_text` to empty
> strings in the inputs so the `data-webshare-title` / `data-webshare-text`
> attributes are emitted on the rendered `<nav>`. The SDC's twig
> defaults them but explicit empty inputs prevent any theme override
> from stripping them.

## How the SDC Bridges to Webshare

When the `webshare:share` component is rendered by Canvas - outside
the block plugin's `build()` - the component's Twig template calls
`webshare_share_data(url, options)` to retrieve the share URL and the
list of enabled platform links from the Webshare service. The
component-level `inputs` (Title, Alignment, Orientation, etc.) are
forwarded to that call.

The `webshare_share_data()` function also bubbles the
`webshare_platforms` cache tag into the active render context so any
platform change in **/admin/config/services/webshare** invalidates
Canvas-rendered rails the same way it invalidates block-rendered rails.

[canvas]: https://www.drupal.org/project/canvas
