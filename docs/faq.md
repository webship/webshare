# Frequently Asked Questions

Common questions about installing, configuring, and extending Webshare.

## General

### What is Webshare?

A Drupal module that renders a social-sharing rail (icons + links to
LinkedIn, Facebook, X, etc.) as a Single-Directory Component. It can be
placed as a classic block, as a Drupal Canvas component, or embedded
directly from custom Twig — the same component drives all three paths.

### Which Drupal versions are supported?

Drupal 10.x and Drupal 11.x. PHP 8.1+.

### Is Drupal Canvas required?

No. Canvas is **optional**. When it is enabled, Webshare's SDC is
auto-exposed as a Canvas component (`sdc.webshare.share`) so you can
place the rail through the Canvas editor. Without Canvas the SDC is
still usable via the classic block plugin and custom Twig.

### Does Webshare work on Drupal CMS?

Yes — Webshare is tested against the `drupal/cms` distribution
(Mercury theme) on every push. Because Mercury delegates header /
footer rendering to Canvas's `page_region` entities, the rail on
Drupal CMS is placed by adding `sdc.webshare.share` to those
page_regions instead of using classic block placement. The
[admin Canvas guide](2-admins/3-canvas-integration.md) and the
[developer integration guide](3-developers/4-canvas-integration.md)
both cover this.

## Configuration

### Where are the settings?

`/admin/config/services/webshare` — under **Configuration > Web
services**. You need the *Administer Webshare* permission.

### Where are presentation settings (heading, alignment, orientation)?

On the **block** / Canvas component instance, not on the module-level
settings form. This lets the same component be placed multiple times
with different presentations.

### Where is "which platforms are enabled" stored?

In the `webshare_platforms` database table (one row per platform).
Custom platforms added through the modal also live there. This is
intentional — see [Database Schema](3-developers/2-database-schema.md).

### How do I add a custom platform without writing code?

Open the settings page, click **Add Custom Platform**, fill in the
modal (name, title, URL template, icon), and Save. See
[Managing Platforms](2-admins/1-platforms.md#adding-a-custom-platform).

### Can I export the platform table with config?

No — the platforms live in the database, not config. If you need
deterministic platform state across environments, ship the rows from
a `hook_install()` or recipe. See
[Extending Webshare](3-developers/6-extending.md#shipping-a-recipe-distribution-preset).

### My platform changes aren't reflected on the front page.

The settings form invalidates the `webshare_platforms` cache tag on
save, which should refresh the anonymous page cache automatically.
If that doesn't happen:

1. Confirm the cache tag is in the rail's render array (look for
   `webshare_platforms` in `WebshareService::build()`).
2. Manually invalidate via
   `Drush ev '\Drupal\Core\Cache\Cache::invalidateTags(["webshare_platforms"]);'`
3. Last resort: `drush cr`.

## Block Placement

### Can I have two share blocks on the same page?

Yes. Place the **Share** block twice with different machine names
(e.g. `webshare_above` and `webshare_below`). Each placement has its
own `#block-<machine-name>` DOM id so CSS and tests can target each
individually. The webship-js test suite uses exactly this pattern.

### My block visibility doesn't match the front page on Drupal CMS.

Drupal CMS's homepage is a Canvas page (`/page/1` aliased to `<front>`)
and Mercury delegates header/footer to Canvas page_regions —
**classic block placement does not render at all on Canvas-managed
pages**. Use the
[Canvas integration path](2-admins/3-canvas-integration.md) instead.

## Drupal Canvas

### How do I install Drupal Canvas?

`composer require drupal/canvas: ^1.4` then `drush en canvas`. Webshare
ships Canvas under `require-dev`, so the test build always has it
available, but production sites must opt in explicitly.

### The share component renders but with the wrong heading on a Canvas page.

The heading is one of the component inputs. Edit the page in Canvas,
select the Share component, and change **Title** in the right-hand
panel. Don't forget to Save and publish.

### Why isn't `platforms` declared as an SDC prop?

Drupal Canvas excludes any SDC that declares an array-of-objects prop
(it cannot match such a shape to a field widget). To stay in Canvas's
catalogue, Webshare leaves `platforms` undeclared and resolves it via
the `webshare_share_data()` Twig function at render time. See
[Drupal Canvas Integration (Developer)](3-developers/4-canvas-integration.md).

## Permissions

### Who can administer Webshare?

Users with the **Administer Webshare** permission. By default this is
the Administrator role only. You can assign it to other roles via
**People > Permissions**. See [Permissions](2-admins/4-permissions.md).

### What permission do I need to place the Share block?

The standard **Administer blocks** permission — independent of the
*Administer Webshare* permission. A site builder can place Share
blocks without being able to add custom platforms, and vice versa.

## Icons

### Can I use Bootstrap Icons / Phosphor / Font Awesome instead of the bundled SVGs?

Yes — Webshare integrates with the [Drupal Core Icons API][icons]
(Drupal 11.1+) or the `ui_icons` contrib module. Map each platform to
a `pack` + `icon` reference in `webshare.settings.icon_map`. See
[Drupal Core Icons API](2-admins/5-icons-api.md).

### I mapped a platform to an icon pack but the bundled SVG still renders.

Webshare's resolver falls back to the bundled SVG when **any** of:

- The Icons API service isn't available (Drupal < 11.1 without
  `ui_icons`).
- The mapped pack isn't registered (the pack module isn't enabled).
- The `pack` or `icon` key is empty.

Run `drush ev '$m=\Drupal::service("plugin.manager.icon_pack");
print_r(array_keys($m->getDefinitions()));'` to see which packs are
actually registered.

## Testing

### How do I run the tests locally?

You need a DDEV test site with the module installed. See
[Testing](3-developers/5-testing.md#running-the-suite-locally). Two
parallel sites exist on the development machine —
`drupal11webshare` (Standard) and `drupalcms2webshare` (Drupal CMS) —
plus the suite is also runnable inside the canonical module repo.

### Are the tests pure browser-driven?

Yes. Every Cucumber step uses Playwright — no Drush, no shell, no
PHP scripts. Site-state setup that can't be done via the UI (Drupal
install, Canvas content-entity seeding) is handled by the CI
`before_script` (or a local dev script), never by a Cucumber step.

[icons]: https://www.drupal.org/node/3408276
