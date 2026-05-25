'use strict';

/**
 * @file
 * Custom step definitions for the Webshare module test suite.
 *
 * Mirrors the sibling Webship modules (webpage / webblog / webseo): every
 * step drives the site through the browser only — no Drush, no shell. Site
 * provisioning beyond what these steps cover (Drupal install, module enable,
 * Canvas fixtures) is handled by the CI before_script.
 *
 * Navigation and waiting reuse webship-js's own helpers — gotoUrl (friendly
 * navigation errors) and waitForPageLoad (BBR smart-settle: DOM ready,
 * network idle, no pending AJAX/timers, DOM-quiet) — instead of raw
 * Playwright waits, and failures are wrapped with friendly().
 */

const { Given, Then, When } = require('@cucumber/cucumber');
const {
  friendly,
  gotoUrl,
  waitForPageLoad,
} = require('webship-js/tests/step-definitions/webship');

/**
 * Run a step body and rethrow any failure as a tester-friendly error.
 *
 * @param {Function} body  - async function performing the step.
 * @param {string} message - human-readable description for failures.
 */
async function attempt(body, message) {
  try {
    await body();
  } catch (err) {
    throw friendly(message, err);
  }
}

/**
 * Log in as a named test user defined in cucumber.js worldParameters.users.
 *
 * The Webmaster row is the site-install super-admin. Every other row is
 * provisioned by `Given I add testing users` (see below).
 *
 * Example #1: Given I am a logged in user with the "Webmaster" user
 * Example #2: Given I am a logged in user with the "Content editor" user
 * Example #3: Given I am a logged in user with the "Authenticated user" user
 * Example #4: Given I am a logged in user with the username "Content editor" user
 * Example #5: Given I am a logged in user with "Webmaster"
 */
Given(/^I am a logged in user with( the)*( username)* "([^"]*)?"( user)?$/, async function (theCase, usernameCase, key, userCase) {
  const users = this.parameters.users || {};
  if (!(key in users)) {
    throw new Error(`No user named "${key}" in cucumber.js worldParameters.users`);
  }
  const { username, password } = users[key];
  if (!username || !password) {
    throw new Error(`User "${key}" is missing username or password in worldParameters.users`);
  }
  await attempt(async () => {
    await this.context.clearCookies();
    await gotoUrl(this.page, `${this.parameters.launchUrl}/user/login`);
    // Use Drupal's stable field IDs so the step is theme-independent:
    // Olivero's label is "Username", Drupal CMS's Gin theme renders
    // "Username or email address" — getByLabel('Username') is ambiguous.
    await this.page.locator('#edit-name').fill(username);
    await this.page.locator('#edit-pass').fill(password);
    await this.page.locator('input[value="Log in"]').click();
    await waitForPageLoad(this.page, this.minWaitTime && this.minWaitTime.page);
  }, `Could not log in as "${key}"`);
});

/**
 * Provision every non-admin user from cucumber.js worldParameters.users via
 * Drupal's /admin/people/create form. Entries flagged isAdmin: true are
 * skipped (the site-install Webmaster already exists). Idempotent.
 *
 * Example #1: Given I add testing users
 * Example #2: And I add testing users
 * Example #3: When I add testing users
 * Example #4: Given I add the testing users
 * Example #5: And we add testing users
 */
Given(/^(?:I |we )?add( the)? testing users$/, async function (theCase) {
  const users = this.parameters.users || {};
  await attempt(async () => {
    for (const [key, info] of Object.entries(users)) {
      if (info.isAdmin) continue;
      await gotoUrl(this.page, `${this.parameters.launchUrl}/admin/people/create`);
      // Fill values + tick role checkboxes via JS. Drupal CMS's Gin theme
      // wraps the password-confirm widget in an `is-initial` collapsed state
      // that hides the password inputs until an interaction event fires — a
      // normal Playwright fill() then fails actionability. Setting `.value`
      // in the page context bypasses the hidden-input check, and the form
      // posts the assigned values just the same.
      await this.page.evaluate((info) => {
        const set = (sel, val) => { const el = document.querySelector(sel); if (el) el.value = val; };
        set('#edit-name', info.username);
        set('#edit-mail', info.email || `${info.username}@example.test`);
        set('#edit-pass-pass1', info.password);
        set('#edit-pass-pass2', info.password);
        for (const role of info.roles || []) {
          const cb = document.querySelector(`input[name="roles[${role}]"]`);
          if (cb) cb.checked = true;
        }
      }, info);
      // JS-click sidesteps the Gin sticky form-actions overlay (Drupal CMS).
      await this.page.evaluate(() => document.querySelector('#edit-submit').click());
      await waitForPageLoad(this.page);
    }
  }, 'Could not provision the testing users');
});

/**
 * Detect the default (block-layout) theme from a "Place block" library link.
 */
async function detectTheme(world) {
  await gotoUrl(world.page, `${world.parameters.launchUrl}/admin/structure/block`);
  await waitForPageLoad(world.page);
  const href = await world.page
    .locator('a[href*="/admin/structure/block/library/"]')
    .first()
    .getAttribute('href');
  return (href && href.match(/\/library\/([^/?]+)/)) ? href.match(/\/library\/([^/?]+)/)[1] : 'olivero';
}

/**
 * Place one Share block (fixed machine id) in a region, scoped to the front
 * page, with the settings the suite expects. Idempotent — removes any prior
 * instance with the same id first.
 */
async function placeShareBlock(world, theme, id, region) {
  const base = world.parameters.launchUrl;
  // Remove any prior instance with this id so re-runs stay deterministic.
  // A missing block 404s — and Olivero's 404 page carries a Search form whose
  // submit is also #edit-submit, so match the confirm form's "Remove" button.
  await gotoUrl(world.page, `${base}/admin/structure/block/manage/${id}/delete`);
  const removeBtn = world.page.locator('input[value="Remove"], button:has-text("Remove")');
  if (await removeBtn.count() > 0) {
    // JS-click sidesteps the Gin sticky form-actions overlay (Drupal CMS).
    await world.page.evaluate(() => {
      const btn = document.querySelector('input[value="Remove"]')
        || [...document.querySelectorAll('button')].find(b => b.textContent.trim() === 'Remove');
      if (btn) btn.click();
    });
    await waitForPageLoad(world.page);
  }
  await gotoUrl(world.page, `${base}/admin/structure/block/add/share/${theme}?region=${region}`);
  // Set the form values directly. The Block layout admin form carries heavy
  // states/machine-name JS, so toggling controls via the page context is far
  // more reliable than Playwright actionability waits here.
  await world.page.evaluate((opts) => {
    const set = (sel, val) => { const el = document.querySelector(sel); if (el) el.value = val; };
    const check = (sel) => { const el = document.querySelector(sel); if (el) el.checked = true; };
    check('input[name="settings[orientation]"][value="horizontal"]');
    check('input[name="settings[alignment]"][value="start"]');
    check('input[name="settings[placement]"][value="inline"]');
    check('input[name="settings[native_share]"]');
    set('textarea[name="visibility[request_path][pages]"]', '<front>');
    set('select[name="region"]', opts.region);
    set('input[name="id"]', opts.id);
  }, { id, region });
  // Submit by calling .click() inside the page context rather than via
  // Playwright actionability. The Block layout admin form is the same across
  // themes, but Drupal CMS's Gin theme adds a sticky top bar that intercepts
  // pointer events on the bottom save button — a normal Playwright click
  // times out there. JS click skips actionability and submits the same form.
  await world.page.evaluate(() => {
    document.querySelector('#edit-actions-submit').click();
  });
  await waitForPageLoad(world.page);
}

/**
 * Place the Share block in BOTH the Content Above and Content Below regions of
 * the default theme, each scoped to the front page so node / Canvas pages
 * render only their own component instance. The blocks get the deterministic
 * ids `webshare_above` and `webshare_below`, exposed in the DOM as
 * `#block-webshare-above` / `#block-webshare-below`. Idempotent. Must be
 * invoked while logged in as the Webmaster.
 *
 * Used by the Drupal Standard test site (Olivero theme).
 *
 * Example #1: Given the Webshare Share blocks are placed in the content regions
 * Example #2: And the Webshare Share blocks are placed in the content regions
 */
Given(/^the Webshare Share blocks are placed in the content regions$/, async function () {
  await attempt(async () => {
    const theme = await detectTheme(this);
    await placeShareBlock(this, theme, 'webshare_above', 'content_above');
    await placeShareBlock(this, theme, 'webshare_below', 'content_below');
  }, 'Could not place the Webshare Share blocks in the content regions');
});

/**
 * Place the Share block in the Header and Footer regions of the default
 * theme. This is the Drupal CMS variant — the Mercury theme (Drupal CMS
 * 2.x default) exposes only content / header / footer regions, and the
 * `content` region is owned by Canvas-driven page content. The blocks get
 * the deterministic ids `webshare_header` and `webshare_footer`, exposed
 * in the DOM as `#block-webshare-header` / `#block-webshare-footer`.
 * Idempotent. Must be invoked while logged in as the Webmaster.
 *
 * Example #1: Given the Webshare Share blocks are placed in the header and footer regions
 * Example #2: And the Webshare Share blocks are placed in the header and footer regions
 */
Given(/^the Webshare Share blocks are placed in the header and footer regions$/, async function () {
  await attempt(async () => {
    const theme = await detectTheme(this);
    await placeShareBlock(this, theme, 'webshare_header', 'header');
    await placeShareBlock(this, theme, 'webshare_footer', 'footer');
  }, 'Could not place the Webshare Share blocks in the header and footer regions');
});

/**
 * Set the enabled Webshare platforms through the settings form.
 *
 * Must be invoked while logged in as a user with "administer webshare"
 * (e.g. the Webmaster). The form's submit handler invalidates the
 * webshare_platforms cache tag, so the rendered rail — including the
 * anonymous page cache — reflects the new set immediately.
 *
 * @param {object} world      - Cucumber world (provides page + parameters).
 * @param {string[]|"all"} on - Platform ids to enable, or "all".
 */
async function setEnabledPlatforms(world, on) {
  await gotoUrl(world.page, `${world.parameters.launchUrl}/admin/config/services/webshare`);
  await waitForPageLoad(world.page);
  const boxes = world.page.locator('input[type="checkbox"][name^="platforms_data"]');
  const total = await boxes.count();
  if (total === 0) {
    throw new Error('No platform checkboxes found — are you logged in as an administrator?');
  }
  for (let i = 0; i < total; i++) {
    const box = boxes.nth(i);
    const name = (await box.getAttribute('name')) || '';
    const match = name.match(/platforms_data\[([^\]]+)\]/);
    const id = match ? match[1] : '';
    const enable = on === 'all' || on.includes(id);
    if (enable) {
      if (!(await box.isChecked())) await box.check();
    }
    else if (await box.isChecked()) {
      await box.uncheck();
    }
  }
  // JS-click sidesteps the Gin sticky form-actions overlay (Drupal CMS).
  await world.page.evaluate(() => document.querySelector('#edit-submit').click());
  await waitForPageLoad(world.page);
}

const defaultPlatforms = ['linkedin', 'facebook_share', 'x'];

/**
 * Enable every Webshare platform via the settings form.
 *
 * Example: Given I enable all Webshare platforms
 */
Given(/^I enable all Webshare platforms$/, async function () {
  await attempt(() => setEnabledPlatforms(this, 'all'),
    'Could not enable all Webshare platforms');
});

/**
 * Restore the fresh-install default platform set (LinkedIn, Facebook, X).
 *
 * Example: Given I enable only the default Webshare platforms
 */
Given(/^I enable only the default Webshare platforms$/, async function () {
  await attempt(() => setEnabledPlatforms(this, defaultPlatforms),
    'Could not reset to the default Webshare platforms');
});

/**
 * Enable a single named platform on top of the defaults.
 *
 * Example: Given I enable the Webshare platform "whatsapp"
 */
Given(/^I enable the Webshare platform "([^"]*)"$/, async function (platformId) {
  await attempt(() => setEnabledPlatforms(this, [...defaultPlatforms, platformId]),
    `Could not enable the Webshare platform "${platformId}"`);
});

/**
 * Resolve a webship-js named selector from the world registry.
 *
 * The registry (`world.__selectorsCss`) is hydrated by webship-js from
 * `cucumber.shared.js`'s `selectors.files` list — see tests/selectors/*.json
 * for the catalog. Throws when the name is unknown so a typo never silently
 * passes through to Playwright as a literal CSS string.
 */
function resolveName(world, name) {
  const css = world.__selectorsCss || {};
  const key = name.trim();
  if (Object.prototype.hasOwnProperty.call(css, key)) {
    return css[key];
  }
  const known = Object.keys(css).sort().join(', ');
  throw new Error(`Unknown named selector "${key}". Registered names: ${known}`);
}

/**
 * Assert a named selector is visible / hidden / attached / focused / enabled /
 * disabled / editable. Mirrors webship-js's raw-CSS `should be …` phrasing.
 *
 * Example #1: Then the "share rail" element should be visible
 * Example #2: Then the "above share list" element should be visible within 5 seconds
 * Example #3: Then the "modal dialog" element should be hidden
 */
Then(/^the "([^"]*)" element should be (visible|hidden|attached|focused|enabled|disabled|editable)(?: within (\d+) seconds?)?$/, async function (name, state, sec) {
  const sel = resolveName(this, name);
  const loc = this.page.locator(sel);
  const timeout = sec ? Number(sec) * 1000 : 10000;
  await attempt(async () => {
    if (state === 'visible' || state === 'attached') {
      await loc.first().waitFor({ state, timeout });
    }
    else if (state === 'hidden') {
      await loc.first().waitFor({ state: 'hidden', timeout });
    }
    else if (state === 'focused') {
      await this.page.waitForFunction(s => document.activeElement && document.activeElement.matches(s), sel, { timeout });
    }
    else {
      const fn = { enabled: 'isEnabled', disabled: 'isDisabled', editable: 'isEditable' }[state];
      const ok = await loc.first()[fn]();
      if (!ok) throw new Error(`"${name}" not ${state}`);
    }
  }, `Expected "${name}" (${sel}) to be ${state}`);
});

/**
 * Assert the count of elements matching a named selector.
 *
 * Example #1: Then the "share rail" element should have a count of 2
 * Example #2: Then the "above share item" element should have a count of 4
 * Example #3: Then the "above share item whatsapp" element should have a count of 0
 */
Then(/^the "([^"]*)" element should have a count of (\d+)(?: within (\d+) seconds?)?$/, async function (name, expected, sec) {
  const sel = resolveName(this, name);
  const target = Number(expected);
  const timeout = sec ? Number(sec) * 1000 : 10000;
  // Use Playwright's locator engine so selector extensions like
  // `:has-text('X')` (used for table-cell content matches) resolve.
  const loc = this.page.locator(sel);
  const deadline = Date.now() + timeout;
  let last = -1;
  await attempt(async () => {
    while (Date.now() < deadline) {
      last = await loc.count();
      if (last === target) return;
      await this.page.waitForTimeout(100);
    }
    throw new Error(`count was ${last}`);
  }, `Expected "${name}" (${sel}) count to be ${target}`);
});

/**
 * Assert the first element matching a named selector carries a CSS class.
 *
 * Example #1: Then the "above share rail" element should have class "webshare--horizontal"
 * Example #2: Then the "below share rail" element should have class "webshare--vertical"
 */
Then(/^the "([^"]*)" element should have class "([^"]*)"(?: within (\d+) seconds?)?$/, async function (name, cls, sec) {
  const sel = resolveName(this, name);
  const timeout = sec ? Number(sec) * 1000 : 10000;
  await attempt(async () => {
    await this.page.waitForFunction(
      ([s, c]) => { const el = document.querySelector(s); return el && el.classList.contains(c); },
      [sel, cls],
      { timeout, polling: 100 },
    );
  }, `Expected "${name}" (${sel}) to have class "${cls}"`);
});

/**
 * Assert the first element matching a named selector contains the given text.
 *
 * Example #1: Then the "above share heading" element should contain text "Share this page"
 * Example #2: Then the "modal title" element should contain text "Add Platform"
 */
Then(/^the "([^"]*)" element should contain text "([^"]*)"(?: within (\d+) seconds?)?$/, async function (name, text, sec) {
  const sel = resolveName(this, name);
  const timeout = sec ? Number(sec) * 1000 : 10000;
  await attempt(async () => {
    await this.page.waitForFunction(
      ([s, t]) => { const el = document.querySelector(s); return el && el.textContent.includes(t); },
      [sel, text],
      { timeout, polling: 100 },
    );
  }, `Expected "${name}" (${sel}) to contain text "${text}"`);
});

/**
 * Click the first element matching a named selector. Falls back to a JS
 * `.click()` after a couple of failed Playwright actionability retries so
 * Gin's sticky form-actions overlay on Drupal CMS does not stall the click.
 *
 * Example #1: When I click the "modal dropbutton toggle" element
 * Example #2: When I click on the "webshare admin delete linkedin link" element
 */
When(/^(?:I |we )?click(?: on)?(?: the)? "([^"]*)" element$/, async function (name) {
  const sel = resolveName(this, name);
  await attempt(async () => {
    const loc = this.page.locator(sel).first();
    await loc.waitFor({ state: 'visible', timeout: 10000 });
    try {
      await loc.click({ timeout: 4000 });
    }
    catch (e) {
      // Sticky form-actions overlays (Gin) sometimes intercept the click —
      // fall back to a JS click which bypasses Playwright actionability.
      await this.page.evaluate((s) => {
        const el = document.querySelector(s);
        if (el) el.click();
      }, sel);
    }
  }, `Could not click the "${name}" element`);
});

/**
 * Resolve a form field locator by label, falling back to the label element
 * itself for inputs that are visually replaced by rich editors / widgets.
 */
function fieldLocator(page, label) {
  return page
    .locator('label.form-item__label, label.form-required, label')
    .filter({ hasText: new RegExp(`^\\s*${label.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\$&')}(\\s|$)`, 'i') })
    .first();
}

/**
 * Assert that a form field with the given label is visible on the page.
 *
 * Example #1: Then I should see a "Platform Name" field
 * Example #2: Then I should see a "Sharing URL Template" field
 */
Then(/^(?:I |we )?should see a "([^"]*)" field$/, async function (label) {
  await attempt(async () => {
    const locator = fieldLocator(this.page, label);
    await locator.waitFor({ state: 'visible', timeout: 10000 });
  }, `Expected to find a field labeled "${label}"`);
});

/**
 * Assert that a form field with the given label (article "an") is visible.
 *
 * Example #1: Then I should see an "Image" field
 * Example #2: Then I should see an "Icon" field
 */
Then(/^(?:I |we )?should see an "([^"]*)" field$/, async function (label) {
  await attempt(async () => {
    const locator = fieldLocator(this.page, label);
    await locator.waitFor({ state: 'visible', timeout: 10000 });
  }, `Expected to find a field labeled "${label}"`);
});

/**
 * Assert that a button with the given text is visible on the page.
 *
 * Example #1: Then I should see the button "Save configuration"
 * Example #2: Then I should see the button "Add Custom Platform"
 */
Then(/^(?:I |we )?should see the button "([^"]*)"$/, async function (text) {
  await attempt(async () => {
    const locator = this.page.getByRole('button', { name: text, exact: false }).first();
    await locator.waitFor({ state: 'visible', timeout: 10000 });
  }, `Expected to find a button with text "${text}"`);
});
