# Drupal CMS feature suite

webship-js BDD scenarios that exercise the Webshare module on the
**Drupal CMS distribution** (`drupal/cms` 2.x, Mercury theme + Gin admin).
Run via:

```bash
LAUNCH_URL=http://<site>:<port> npx cucumber-js --config cucumber.drupalcms.js
```

`cucumber.drupalcms.js` at the project root loads only this folder
(`paths: ['tests/features/drupalcms/**/*.feature']`) and routes reports
/ screenshots / videos into `tests/{reports,screenshots,videos}/drupalcms/`.

## Layout caveats specific to Drupal CMS

- Mercury exposes only `content / header / footer` regions, and the
  `content` region is owned by Canvas-driven page content. The Webshare
  Share component is therefore added to the **Canvas page_region**
  entities (`mercury.header` / `mercury.footer`) at site-setup time -
  classic block-layout placement does not render on Canvas-managed
  pages. The CI `before_script` for `webship-js-test-drupal-cms` seeds
  those page_regions; local DDEV mirrors the same drush call.
- The `drupal_cms_installer` profile ignores `--account-name` /
  `--account-pass` from `drush site:install` and creates uid 1 as
  `admin`. The before_script renames uid 1 to `webmaster` with the
  matching password so the shared `users` registry in
  `cucumber.shared.js` works unchanged.

Every assertion uses a named selector from
`tests/selectors/{webshare,drupal-cms-mercury,cms-drupal-cms-gin}.json`.
