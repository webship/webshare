// Default cucumber-js config — runs the Drupal Standard suite.
//
// The Webshare feature set is split by Drupal flavour:
//   tests/features/drupal/      — Drupal Standard profile (Olivero theme)
//   tests/features/drupalcms/   — Drupal CMS distribution (Mercury theme)
//
// This config loads only the `drupal/` features. The Drupal CMS suite lives
// in `cucumber.drupalcms.js`:
//
//   npx cucumber-js --config cucumber.js              # Drupal Standard (default)
//   npx cucumber-js --config cucumber.drupalcms.js    # Drupal CMS

const baseWorldParameters = require('./cucumber.shared.js');

module.exports = {
  default: {
    timeout: 45000,
    requireModule: ['tsx/cjs'],
    require: [
      'node_modules/webship-js/tests/step-definitions/**/*.js',
      'tests/step-definitions/**/*.js',
    ],
    paths: ['tests/features/drupal/**/*.feature'],
    format: [
      '@cucumber/pretty-formatter',
      'json:tests/reports/drupal/cucumber_report.json',
    ],
    worldParameters: Object.assign({}, baseWorldParameters, {
      screenshot: Object.assign({}, baseWorldParameters.screenshot, {
        dir: './tests/screenshots/drupal',
      }),
      video: Object.assign({}, baseWorldParameters.video, {
        dir: './tests/videos/drupal',
      }),
    }),
  },
};
