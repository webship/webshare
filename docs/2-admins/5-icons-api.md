# Drupal Core Icons API

Webshare can render its platform and native-share icons through Drupal's
**Icons API** (built into Drupal core 11.1+, and also provided by the
[`ui_icons`][ui_icons] contrib module on older sites) instead of the
bundled SVGs.

This is optional - out of the box Webshare uses the SVG files shipped
in `web/modules/contrib/webshare/img/` and the Icons API integration
stays inert.

## Why Use an Icon Pack?

- **Visual consistency** with the rest of your site (Bootstrap icons,
  Phosphor, Material Symbols, etc.).
- **Single source of truth** for icon weights, fills, and colours.
- **Smaller payload** when the same icon pack is already loaded for
  other components.

## Configuration Keys

Two keys on `webshare.settings` control the integration. They live in
config (not the platforms database) so they can be exported with the
rest of your site config.

### `icon_map`

Map of platform id → icon reference.

```yaml
webshare.settings:
  icon_map:
    facebook_share:
      pack: bootstrap_icons
      icon: facebook
    x:
      pack: bootstrap_icons
      icon: twitter-x
    linkedin:
      pack: bootstrap_icons
      icon: linkedin
    whatsapp:
      pack: bootstrap_icons
      icon: whatsapp
    copy:
      pack: bootstrap_icons
      icon: clipboard
```

Any platform id from the [platforms table](1-platforms.md) is a valid
key - including custom platforms you added through the modal. Platforms
without an entry continue to render their stored SVG.

### `native_share_icon`

A single icon reference for the native-share button.

```yaml
webshare.settings:
  native_share_icon:
    pack: bootstrap_icons
    icon: share-fill
```

When unset (the default), the native button renders the bundled
`logo.svg`.

## How Webshare Picks an Icon

`WebshareService::renderIconHtml()` resolves the reference at render
time:

1. Look up `plugin.manager.icon_pack` from the container. If the
   service is not present (e.g. Drupal 11.0 without `ui_icons`),
   return an empty string → fall back to the bundled SVG.
2. Look up `pack_id` in the registered pack definitions. If the pack is
   not registered (e.g. because the icon-pack module is not enabled),
   return an empty string → fall back to the bundled SVG.
3. Render `#type: icon` with the `pack_id` + `icon_id` - its output
   replaces the `<img>` in the rendered list item.

The fallback is **per-platform**: if `facebook_share` has an icon
mapping but the pack is not registered, only that one icon falls back;
the others render through the API.

## Setting Up an Icon Pack

The Icons API is supplied by Drupal core (11.1+) or the contrib
[`ui_icons`][ui_icons] module on older sites. Either way, install at
least one **icon pack** module:

- [Bootstrap Icons UI Icons pack](https://www.drupal.org/project/bootstrap_icons)
- [Phosphor Icons UI Icons pack](https://www.drupal.org/project/phosphor_icons)
- [Font Awesome UI Icons pack](https://www.drupal.org/project/font_awesome_icons)
- [Material Symbols UI Icons pack](https://www.drupal.org/project/material_symbols_icons)

After enabling the pack, find its pack id via:

```bash
drush ev '$m = \Drupal::service("plugin.manager.icon_pack"); foreach($m->getDefinitions() as $id=>$d){ print "$id\n"; }'
```

Then either edit `webshare.settings.yml` and `drush cim`, or set the
keys via drush:

```bash
drush cset webshare.settings icon_map.linkedin.pack bootstrap_icons
drush cset webshare.settings icon_map.linkedin.icon linkedin
```

## Themes and Recipes

Webshare is intentionally icon-library-agnostic - the bundled SVGs are
serviceable but plain. A theme or distribution can ship a config
**preset** that wires `icon_map` to its preferred pack:

- **Vartheme BS5** ships a Bootstrap-icons preset for Webshare.
- A custom site can package the `icon_map` config above into a recipe
  and apply it alongside the theme.

See [Extending Webshare](../3-developers/6-extending.md) for the developer
side of this.

[ui_icons]: https://www.drupal.org/project/ui_icons
