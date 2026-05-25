# Testing

Webshare ships a comprehensive webship-js BDD suite (Playwright +
Cucumber) that runs against two Drupal flavours:

| Flavour          | Profile                      | Theme    | Feature folder                       | Config                     |
| ---------------- | ---------------------------- | -------- | ------------------------------------ | -------------------------- |
| Drupal Standard  | `standard` (Olivero theme)   | Olivero  | `tests/features/drupal/`             | `cucumber.js`              |
| Drupal CMS       | `drupal_cms_installer`       | Mercury  | `tests/features/drupalcms/`          | `cucumber.drupalcms.js`    |

Combined: **82 scenarios / 472 steps** at the time of writing, all
passing.

## Running the Suite Locally

Each flavour has a sibling DDEV test site under
`/var/www/html/test/`. Inside that site:

```bash
# Drupal Standard
LAUNCH_URL="http://drupal11webshare.ddev.site:<port>" \
  npx cucumber-js --config cucumber.js

# Drupal CMS
LAUNCH_URL="http://drupalcms2webshare.ddev.site:<port>" \
  npx cucumber-js --config cucumber.drupalcms.js
```

The configs are committed in the module repo (`cucumber.js`,
`cucumber.drupalcms.js`, `cucumber.shared.js`) and route artefacts
into per-flavour subfolders:

```
tests/reports/{drupal,drupalcms}/
tests/screenshots/{drupal,drupalcms}/
tests/videos/{drupal,drupalcms}/
```

## Suite Layout

```
tests/
├── features/
│   ├── drupal/        # 11 .feature files — Standard / Olivero
│   └── drupalcms/     # 5 .feature files — Drupal CMS / Mercury
├── selectors/
│   ├── webshare.json              # Shared component selectors
│   ├── drupal-olivero.json        # Above/Below block selectors
│   ├── drupal-cms-mercury.json    # Header/Footer Canvas selectors
│   ├── cms-drupal-core-claro.json # Sibling-shared Claro admin
│   └── cms-drupal-cms-gin.json    # Sibling-shared Gin admin
├── step-definitions/
│   └── webshare.steps.js          # Custom steps — pure browser only
├── reports/, screenshots/, videos/  # Per-flavour run output dirs
└── README.md
```

## Custom Step Definitions

`tests/step-definitions/webshare.steps.js` provides webshare-specific
steps. All steps drive the browser through Playwright — no Drush,
no shell, no PHP scripts. Site provisioning that can't be done via
the UI (Drupal install, Canvas fixtures, page_region seeding) is
handled by the CI `before_script`, not by Cucumber steps.

| Step                                                                              | Purpose                                                  |
| --------------------------------------------------------------------------------- | -------------------------------------------------------- |
| `Given I am a logged in user with the "<role>" user`                              | Logs in via the `users` registry in `cucumber.shared.js` |
| `Given I add testing users`                                                       | Provisions one user per Standard role via /admin/people/create |
| `Given the Webshare Share blocks are placed in the content regions`               | Places `webshare_above` + `webshare_below` blocks (Olivero) |
| `Given the Webshare Share blocks are placed in the header and footer regions`    | Drupal CMS placement variant (Mercury page_regions are seeded by CI) |
| `Given I enable all Webshare platforms`                                           | Toggles every platform on via the settings form          |
| `Given I enable only the default Webshare platforms`                              | Resets to `linkedin`, `facebook_share`, `x`              |
| `Given I enable the Webshare platform "<id>"`                                    | Enables one named platform on top of the defaults        |
| `Then the "<name>" element should be visible / hidden / …`                        | Named-selector visibility check                          |
| `Then the "<name>" element should have a count of N`                              | Named-selector count check                               |
| `Then the "<name>" element should have class "<cls>"`                             | Named-selector class check                               |
| `Then the "<name>" element should contain text "<txt>"`                           | Named-selector text-contains check                       |
| `When I click [on] [the] "<name>" element`                                        | Click with JS-click fallback for Gin's sticky bar        |
| `Then I should see a "<label>" field`                                             | Form-field visibility by label                           |
| `Then I should see the button "<text>"`                                           | Button visibility by accessible name                     |

The `the "<name>" element …` steps look up `<name>` in
`world.__selectorsCss` (the registry hydrated from
`tests/selectors/*.json`) and **throw with a list of registered
names** when the name is unknown. This makes typos surface as
actionable errors rather than silent CSS-as-string mismatches.

## Named Selectors

281 named selectors are registered across the 5 JSON files. The naming
convention:

- `share <part>` — component piece (no theme prefix). E.g.
  `share rail`, `share item linkedin`, `share native button`.
- `above|below share <part>` — Olivero/Standard block-scoped variant.
- `header|footer share <part>` — Mercury/Canvas region-scoped variant.
- `webshare admin <part>` — admin form selectors.

Every assertion in every `.feature` file uses these names — no raw CSS
appears in scenario lines.

## CI

`.gitlab-ci.yml` defines two browser-test jobs:

| Job                              | Profile      | Modules                          | Features                       |
| -------------------------------- | ------------ | -------------------------------- | ------------------------------ |
| `webship-js-test`                | Standard     | `webshare`, `canvas`             | `tests/features/drupal/`       |
| `webship-js-test-drupal-cms`    | Drupal CMS   | `webshare` (Canvas via drupal/cms) | `tests/features/drupalcms/`  |

Both jobs use the drupalci `composer` artefact for the Drupal codebase,
install with drush, seed Canvas / page_region fixtures via `drush php:eval`
in `before_script`, install Node 20 + Playwright Chromium, then run
the cucumber-js suite.

Plus three validate-stage jobs (cspell / eslint / stylelint) that also
run locally through `npx gitlab-ci-local --file .gitlab-ci-local.yml`.

## Adding a Scenario

1. Edit or create the relevant `.feature` file under
   `tests/features/drupal/` or `tests/features/drupalcms/`.
2. Use only **named selectors** in `Then the "<name>" element …`
   assertions. If the selector you need doesn't exist, add it to the
   appropriate JSON file under `tests/selectors/` first.
3. Run the suite locally and confirm green.
4. Open a merge request.

For new step phrasings (rare — most things can be expressed with the
existing vocabulary), add them to `webshare.steps.js`. Follow the
"pure browser, no Drush/bash/PHP" rule: any site state your step
needs that can't be set via the UI should be handled by the CI
before_script or a developer-side setup command, not by the step.
