# Content Editor Guide

This guide is for content editors who need to place the share rail on
specific pages — landing pages, blog posts, or other content view modes.

You do not need developer access for any of these tasks. Webshare ships
two browser-based placement paths plus a third (custom Twig) for site
builders who want to embed the rail directly into a theme.

## Path 1 — Classic Block Layout

Use this when your site is built on a classic Drupal theme (Olivero,
Claro, Bartik, etc.) without Drupal Canvas.

1. Navigate to **Administration > Structure > Block layout**
   (`/admin/structure/block`).
2. Find the region where you want the rail (Content Above, Sidebar,
   Footer, etc.) and click **Place block** for that region.
3. Search for "Share" in the dialog and click **Place block** next to
   it.
4. In the block configuration form, choose:
    - **Title** — heading shown above the rail (or empty / hidden).
    - **Orientation** — horizontal or vertical.
    - **Alignment** — start or end.
    - **Mobile visibility** — show on all devices / hide on mobile /
      mobile-only.
    - **Show native share button** — toggle the device-share button.
    - **Placement** — `inline` (flows with content) or `rail-end`
      (sticky column at the content end).
5. Under **Visibility**, restrict the block to specific pages if you
   only want it on certain URLs (e.g. `/blog/*`).
6. Click **Save block**.

The rail appears on the next page load. No cache rebuild is needed —
Webshare invalidates the `webshare_platforms` cache tag so the
anonymous page cache picks up the new placement.

## Path 2 — Drupal Canvas Page

Use this when your site uses the Drupal Canvas distribution (e.g. Drupal
CMS) or you are building a Canvas-managed page on a Standard site that
has Canvas enabled.

1. Open the Canvas editor for your page.
2. Open the **Components** panel and locate the **Share** component
   (registered as `sdc.webshare.share`).
3. Drag the component into the area of the page where you want the
   rail.
4. Configure the same props as the classic block — heading, orientation,
   alignment, mobile visibility, native share, placement.
5. Save the page.

The rail renders on the published page exactly as if it were placed
through the classic block layout.

## Path 3 — Drupal Canvas Content Template

Use this when you want the rail to appear on **every node of a given
content type** in a specific view mode (e.g. on every Article in the
Full view mode).

1. Navigate to **Administration > Structure > Content types > Article >
   Manage display** and ensure the **Full** view mode uses a Canvas
   content template (`canvas.content_template.node.article.full`).
2. Edit the content template through the Canvas editor.
3. Drop the **Share** component into the desired slot — Webshare's SDC
   exposes the same props as the block.
4. Save the template.

Every Article node viewed in Full mode now renders the rail with the
configured presentation.

## Path 4 — Custom Twig Template

This path is for themers and module developers — see
[The Share SDC Component](../3-developers/3-sdc-component.md) for the
embed syntax. The rail renders with its own scoped CSS and JavaScript
auto-attached; no extra library include is needed.

## Switching Platforms

The set of platforms that appears in the rail (LinkedIn, Facebook, X,
etc.) is controlled centrally on the
[Webshare settings page](../2-admins/0-configuration.md). The rail
placement (block / Canvas / Twig) only controls *where* the rail
appears, not *which* platforms it contains. Use the settings page to
toggle and reorder platforms once, and every placement picks up the
change.
