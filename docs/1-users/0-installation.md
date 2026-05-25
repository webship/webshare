# Installation and Setup

This guide walks you through installing Webshare on a Drupal site, enabling
the module, and placing the share rail so it appears on the front-end.

## Requirements

### Core Requirements

- Drupal 10.x or 11.x
- PHP 8.1 or higher

### Required Drupal Core Modules

Webshare depends on **`path_alias`** (enabled in every Standard Drupal
install) for the share URL alias lookup. No additional core modules are
required.

### Optional Dependencies

- **Drupal Canvas** (`drupal/canvas`) - required only if you want to place
  the Share component on Canvas pages, page_region entities, or content
  templates. Webshare exposes its SDC as `sdc.webshare.share` to Canvas
  automatically when Canvas is enabled.
- **Drupal Core Icons API** (Drupal 11.1+) or **ui_icons** contrib module -
  required only if you want to render share icons through a registered
  icon pack instead of the bundled SVGs. See
  [Drupal Core Icons API](../2-admins/5-icons-api.md).

## Installation Methods

### Method 1: Using Composer (Recommended)

```bash
composer require drupal/webshare
drush en webshare
```

### Method 2: Using Drush

If the module is already in `web/modules/contrib/`:

```bash
drush en webshare
```

### Method 3: Through the Admin UI

1. Navigate to **Administration > Extend** (`/admin/modules`).
2. Search for "Webshare" under the **Other** category.
3. Tick the checkbox and click **Install**.

## Verifying the Installation

After enabling the module:

1. The settings page becomes reachable at
   **Administration > Configuration > Web services > Webshare**
   (`/admin/config/services/webshare`).
2. A new block plugin called **Share** is registered under
   **Administration > Structure > Block layout** (the block id is
   `share`).
3. The `webshare_platforms` database table is populated with the 13
   default platforms; three (LinkedIn, Facebook, X) are enabled.
4. When Drupal Canvas is also enabled, the SDC is exposed as the
   `sdc.webshare.share` Canvas component.

## Placing the Rail

You can place the share rail three ways. The next step depends on how your
site is built:

- **Classic block layout** - go to
  [Block Placement](../2-admins/2-block-placement.md).
- **Drupal Canvas-managed pages** - go to
  [Drupal Canvas Integration](../2-admins/3-canvas-integration.md).
- **Custom Twig template** - call the `webshare:share` SDC directly
  with `{% embed 'webshare:share' %}`. See
  [The Share SDC Component](../3-developers/3-sdc-component.md).

## Uninstalling

To uninstall Webshare:

1. Remove any placed Share blocks from
   **Structure > Block layout**.
2. Remove any `sdc.webshare.share` components from Canvas pages,
   page_region entities, and content templates.
3. Navigate to **Extend > Uninstall** (`/admin/modules/uninstall`).
4. Tick Webshare and click **Uninstall**.

The `webshare_platforms` table is dropped on uninstall.
