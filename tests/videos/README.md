# Scenario videos

webship-js records a Playwright `.webm` for each scenario in
`mode: 'on-failure'` (see `video` worldParameter in `cucumber.shared.js`).
Passing scenarios discard their recording; failing scenarios keep it.

- `drupal/` — Drupal Standard captures.
- `drupalcms/` — Drupal CMS captures.

`.webm` files are gitignored; the folders + READMEs are tracked.
