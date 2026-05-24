# Webshare — webship-js feature scenarios

| File | Tags | Covers |
|------|------|--------|
| `check-homepage.feature` | `@smoke` | Smoke test — the test site loads and the Webshare component is present. |
| `webshare-frontend.feature` | `@webshare @frontend` | Front-end render: SDC markup, default platforms (3 enabled), share URLs, native share button, `data-webshare-*` payload, JS-error check. |
| `webshare-frontend-blog.feature` | `@webshare @frontend` | Front-end render on a blog node (vertical / rail-end placement). |
| `webshare-backend.feature` | `@webshare @admin` | Back-end administration: `/admin/config/services/webshare` form loads, General + Platforms tabs, 5 platforms in the management table, Edit / Delete / Add modal links, save persists, no JS errors. |

## Running the suite

```bash
yarn test                                  # all features
yarn test -- --tags @frontend              # only front-end
yarn test -- --tags @admin                 # only back-end
yarn test -- --tags @smoke                 # only smoke
```

## Admin login

`webshare-backend.feature` uses the custom step:

```
Given I am logged in as a Drupal admin
```

It shells out to Drush (`drush uli`) to obtain a one-time login URL.
Override the defaults via env vars when needed:

| Variable | Default | Purpose |
|----------|---------|---------|
| `WEBSHARE_DRUSH` | `drush` | Command used to call Drush (e.g. `ddev drush`). |
| `WEBSHARE_DRUSH_URI` | `LAUNCH_URL` | `--uri` argument forwarded to `drush uli`. |
| `WEBSHARE_ADMIN_UID` | `1` | Drupal user id to log in as. |
