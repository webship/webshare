// cucumber-js config for the Webshare suite.
//
//   npx cucumber-js --config cucumber.js

const baseWorldParameters = require('./cucumber.shared.js');

module.exports = {
  default: {
    timeout: 45000,
    requireModule: ['tsx/cjs'],
    require: [
      'node_modules/webship-js/tests/step-definitions/**/*.js',
      'node_modules/@webship-js/drupal-core-steps/tests/step-definitions/**/*.js', // @webship-js/drupal-core-steps: login by handle, the testing users, named pages, messages, listings, the editor, the media library, access control, site structure.
      'node_modules/@webship-js/drupal-cms-steps/tests/step-definitions/**/*.js',  // @webship-js/drupal-cms-steps: the dashboard, site templates, the cookie consent, Drupal Canvas.
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
