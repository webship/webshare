'use strict';

/**
 * @file
 * Custom step definitions for the Webshare module test suite.
 *
 * The default webship-js library covers navigation, forms, fields,
 * assertions, etc. The step below adds a Drupal admin login helper so
 * the back-end (admin form) scenarios can authenticate without hard-coded
 * passwords.
 */

const { execSync } = require('child_process');
const { Given } = require('@cucumber/cucumber');
const {
  gotoUrl,
  waitForPageLoad,
} = require('webship-js/tests/step-definitions/webship');

/**
 * Generate a one-time login URL via Drush and visit it.
 *
 * The wrapper command is configurable via environment variables so the
 * same step works inside a DDEV container (`drush`), from a host shell
 * (`ddev drush`), or against a remote site (e.g. `vendor/bin/drush`).
 *
 *   WEBSHARE_DRUSH       Command used to invoke Drush. Defaults to
 *                        "drush".
 *   WEBSHARE_DRUSH_URI   --uri argument passed to `drush uli`. Defaults
 *                        to the launchUrl from webship-js.
 *   WEBSHARE_ADMIN_UID   Drupal user id to log in as. Defaults to 1.
 *
 * Example #1: Given I am logged in as a Drupal admin
 * Example #2: Given we are logged in as a Drupal admin
 */
Given(/^(I am |we are )?logged in as (?:a )?Drupal admin$/, async function () {
  const drush = process.env.WEBSHARE_DRUSH || 'drush';
  const uri = process.env.WEBSHARE_DRUSH_URI || this.launchUrl;
  const uid = process.env.WEBSHARE_ADMIN_UID || '1';
  const raw = execSync(
    `${drush} uli --no-browser --uri="${uri}" --uid=${uid}`,
    { encoding: 'utf8' },
  );
  const url = raw.trim().split('\n').pop();
  await this.context.clearCookies();
  await gotoUrl(this.page, url);
  await waitForPageLoad(this.page, this.minWaitTime.page || 3000);
});
