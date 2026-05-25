# Cucumber reports

Run output from the webship-js suite. Each flavour writes into its own
subdirectory so artefacts from a Drupal Standard run and a Drupal CMS run
do not collide:

- `drupal/` — `cucumber.js` writes `cucumber_report.json` and
  `cucumber_report.html` here.
- `drupalcms/` — `cucumber.drupalcms.js` writes its reports here.

Generated files inside these subfolders are gitignored (see
`tests/reports/*/.gitignore`); the folders and READMEs are tracked so
the layout is reproducible after a fresh clone.
