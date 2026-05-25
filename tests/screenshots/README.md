# Failed-scenario screenshots

webship-js captures a PNG every time a scenario fails. The `screenshot`
worldParameter in `cucumber.shared.js` controls the filename pattern.

- `drupal/` — Drupal Standard suite captures (configured by `cucumber.js`).
- `drupalcms/` — Drupal CMS suite captures (configured by `cucumber.drupalcms.js`).

PNGs are gitignored; the folders + READMEs are tracked.
