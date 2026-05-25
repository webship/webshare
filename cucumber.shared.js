// Shared cucumber-js worldParameters for both Drupal flavours.
//
// `cucumber.js` (Drupal Standard) and `cucumber.drupalcms.js` (Drupal CMS)
// import this and layer their own report / screenshot / video paths on
// top so artefacts from the two suites do not collide.

module.exports = {
  launchUrl: process.env.LAUNCH_URL || 'http://localhost',
  // Test users for each Drupal Standard profile role.
  //
  // On Drupal Standard, the Webmaster row is the `drush site:install`
  // super-admin created with `--account-name=webmaster --account-pass=…`.
  // On Drupal CMS, the installer profile ignores those flags and creates
  // uid 1 as `admin`; the CI before_script renames uid 1 to `webmaster`
  // with the matching password so the same registry is reused.
  users: {
    'Webmaster': {
      username: 'webmaster',
      email: 'webmaster@example.test',
      password: 'dD.123123ddd',
      isAdmin: true,
    },
    'Content editor': {
      username: 'content_editor_user',
      email: 'content_editor_user@example.test',
      password: 'dD.123123ddd',
      roles: ['content_editor'],
    },
    'Authenticated user': {
      username: 'authenticated_user',
      email: 'authenticated_user@example.test',
      password: 'dD.123123ddd',
      roles: [],
    },
  },
  minWaitTime: {
    page: 3000,
    before_scenario: 0,
    after_scenario: 0,
    before_step: 0,
    after_step: 0,
  },
  selectors: {
    css: {},
    xpath: {},
    filesPath: './tests/selectors/',
    files: [
      'cms-drupal-core-claro.json',
      'cms-drupal-cms-gin.json',
      'webshare.json',
      'drupal-olivero.json',
      'drupal-cms-mercury.json',
    ],
    offset: 60,
    breakpoints: {
      xs:  { width: 375,  height: 667  },
      sm:  { width: 576,  height: 800  },
      md:  { width: 768,  height: 1024 },
      lg:  { width: 992,  height: 768  },
      xl:  { width: 1200, height: 900, default: true },
      xxl: { width: 1400, height: 900 },
    },
  },
  screenshot: {
    dir: './tests/screenshots',
    purge: false,
    onFailed: true,
    onEveryStep: false,
    alwaysFullscreen: false,
    failedPrefix: 'failed_',
    filenamePattern: '{datetime}.{feature_file}.feature_{step_line}.{ext}',
    filenamePatternFailed: '{failed_prefix}{datetime}.{feature_file}.feature_{step_line}.{ext}',
    infoTypes: '',
  },
  video: {
    mode: 'on-failure',
    dir: './tests/videos',
    size: { width: 1280, height: 720 },
    filenamePattern: '{datetime}.{feature_file}.{scenario}.{status}.{ext}',
  },
  javascript: {
    mode: 'warn',
    levels: ['error'],
    ignore: '',
    beforeScenario: false,
    afterScenario: true,
  },
};
