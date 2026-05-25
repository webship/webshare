# Webshare Documentation

A Drupal module that adds social-sharing buttons to your site as a
Single-Directory Component (SDC), a placeable Block, and a Drupal Canvas
component - with smart defaults, native-share support, and a
no-Javascript-required configuration UI.

## What is Webshare?

Webshare renders a small "share this page" rail (icons + links) that
visitors can click to share the current page on LinkedIn, Facebook, X
(Twitter), WhatsApp, Telegram, Reddit, Pinterest, Threads, Bluesky,
Tumblr, Mastodon, or copy the URL to the clipboard. On supporting
browsers it can additionally invoke the [Web Share API][webshareapi] so
visitors share through their device's native share sheet.

The same component can be placed three ways depending on how you build
pages: as a classic Block in a theme region, as a Drupal Canvas
component on a Canvas page or content template, or directly via the
`webshare:share` Single-Directory Component in a custom Twig template.

### Key Features

- **Single source of truth**: One SDC component (`webshare:share`)
  renders the rail wherever it is placed - block, Canvas page, content
  template, or custom Twig.
- **13 platforms out of the box**: LinkedIn, Facebook, X, WhatsApp,
  Copy URL, Email, Telegram, Reddit, Pinterest, Threads, Bluesky,
  Tumblr, Mastodon. Three (LinkedIn, Facebook, X) are enabled on a
  fresh install; the rest can be toggled on per site.
- **Custom platforms**: Site builders can add their own platforms
  through the Webshare settings form - no code required.
- **Drupal Canvas-native**: The component is exposed to Drupal Canvas
  as `sdc.webshare.share` so it can be placed via the Canvas editor.
- **Native Web Share API**: Optional native share button that opens the
  device share sheet on supporting browsers; falls back to copying the
  URL on desktop.
- **Drupal Core Icons API**: Optional icon-pack integration. Use
  Bootstrap Icons, Phosphor, Font Awesome, or any registered icon pack
  in place of the bundled SVGs.
- **Cacheable**: The rendered rail carries the `webshare_platforms`
  cache tag so platform changes invalidate the anonymous page cache.

## Getting Started

### For Site Visitors and Content Editors

If you visit a Webshare-enabled site or are tasked with placing the share
rail on content:

- [Installation and Setup](1-users/0-installation.md) - install Webshare and
  enable the module.
- [Using the Share Rail](1-users/1-using-the-share-rail.md) - what each
  button does and how the native share sheet behaves.
- [Content Editor Guide](1-users/2-content-editor-guide.md) - placing the
  rail on a page, a Canvas page, or a content view mode.

### For Site Administrators

If you're configuring Webshare for your site:

- [Configuration](2-admins/0-configuration.md) - find the settings form
  and what every setting does.
- [Managing Platforms](2-admins/1-platforms.md) - enable / disable /
  reorder built-in platforms and add custom ones.
- [Block Placement](2-admins/2-block-placement.md) - place the Share
  block in any theme region with the block layout UI.
- [Drupal Canvas Integration](2-admins/3-canvas-integration.md) - use
  the Share component on Canvas pages and content templates.
- [Permissions](2-admins/4-permissions.md) - who can administer
  Webshare.
- [Drupal Core Icons API](2-admins/5-icons-api.md) - wire the rail to a
  registered icon pack.

### For Developers

If you're extending Webshare or integrating it into a theme:

- [Architecture Overview](3-developers/0-architecture.md) - module layout
  + the three rendering paths.
- [API Reference](3-developers/1-api-reference.md) - `WebshareService`,
  the `webshare_share_data()` Twig function, and cache metadata.
- [Database Schema](3-developers/2-database-schema.md) - the
  `webshare_platforms` table.
- [The Share SDC Component](3-developers/3-sdc-component.md) - props,
  defaults, and what the Twig expects.
- [Drupal Canvas Integration](3-developers/4-canvas-integration.md) -
  how the SDC is exposed to Canvas, page_region and content_template
  examples.
- [Testing](3-developers/5-testing.md) - running the webship-js suite
  (Drupal Standard + Drupal CMS).
- [Extending Webshare](3-developers/6-extending.md) - adding platforms
  programmatically, custom icons, theming the rail.

## Frequently Asked Questions

See the [FAQ](faq.md) for common questions about Webshare.

[webshareapi]: https://developer.mozilla.org/en-US/docs/Web/API/Web_Share_API
