// cucumber-js config for the Drupal CMS suite.
//
// Loads only the `tests/features/drupalcms/` features and routes reports /
// screenshots / videos into Drupal-CMS-specific subdirectories so they do
// not collide with the Drupal Standard run.
//
// Run as:
//   npx cucumber-js --config cucumber.drupalcms.js

const baseWorldParameters = require('./cucumber.shared.js');

module.exports = {
  default: {
    timeout: 45000,
    requireModule: ['tsx/cjs'],
    require: [
      'node_modules/webship-js/tests/step-definitions/**/*.js',
      'tests/step-definitions/**/*.js',
    ],
    paths: ['tests/features/drupalcms/**/*.feature'],
    format: [
      '@cucumber/pretty-formatter',
      'json:tests/reports/drupalcms/cucumber_report.json',
    ],
    worldParameters: Object.assign({}, baseWorldParameters, {
      screenshot: Object.assign({}, baseWorldParameters.screenshot, {
        dir: './tests/screenshots/drupalcms',
      }),
      video: Object.assign({}, baseWorldParameters.video, {
        dir: './tests/videos/drupalcms',
      }),
    }),
  },
};
