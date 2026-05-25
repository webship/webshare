# Database Schema

Webshare stores its platforms in a single database table —
`webshare_platforms` — rather than in config. The table is populated by
`hook_install()` and updated by the platform CRUD forms.

## Why database, not config?

The platforms table contains **environment-specific** state (custom
platforms with site-specific URLs, icons uploaded to local file storage,
toggles that differ between staging and prod). Storing it in
`config/install/webshare.settings.yml` would add config-export noise on
every save and force operators to commit per-environment fixtures into
version control. The database table avoids both.

The Drupal Core Icons API mapping (`icon_map`, `native_share_icon`) is
the **only** cross-environment Webshare state — that does live in
config so it can be exported.

## Table: `webshare_platforms`

Defined in `webshare_schema()` in `webshare.install`:

| Column         | Type            | Null | Default | Notes                                          |
| -------------- | --------------- | ---- | ------- | ---------------------------------------------- |
| `id`           | serial          | no   | —       | Primary key.                                   |
| `platform_id`  | varchar(64)     | no   | —       | Machine name — unique. Indexed.                |
| `name`         | varchar(255)    | no   | —       | Human-readable name (toolbar / table display). |
| `title`        | varchar(255)    | no   | —       | `aria-label` / hover title on the share link.  |
| `enabled`      | tinyint         | no   | 1       | 1 = enabled, 0 = disabled.                     |
| `image`        | varchar(255)    | no   | —       | Path or filename of the platform icon (SVG).   |
| `weight`       | int             | no   | 0       | `#tabledrag` ordering. Lower = earlier.        |
| `url_template` | varchar(512)    | yes  | NULL    | Template with `[url]` / `[title]` tokens.      |
| `is_custom`    | tinyint         | no   | 0       | 1 = added through the admin modal.             |
| `created`      | int (timestamp) | no   | 0       | Unix timestamp of insert.                      |
| `updated`      | int (timestamp) | no   | 0       | Unix timestamp of last save.                   |

### Indexes

- **Primary key**: `id`.
- **Unique key**: `platform_id`.
- **Index `enabled_weight`**: `(enabled, weight)` — covers the
  default-sorted read query.

### URL Template Tokens

`WebshareService::build()` substitutes two tokens at render time:

| Token     | Substituted with                                                |
| --------- | --------------------------------------------------------------- |
| `[url]`   | `rawurlencode($url)` (the current page absolute URL).           |
| `[title]` | `rawurlencode($share_title)` — empty string when not provided.  |

A platform with an empty `url_template` is treated as a **clipboard
button** (the Copy URL platform). It renders as a `<button>` instead of
a link and gets a `data-webshare-copy` attribute carrying the URL.

## Default Rows (post-install)

`hook_install()` calls `_webshare_migrate_default_platforms()`, which
reads `webshare.settings.buttons` (the default-config fallback) and
inserts one row per entry. The post-install table looks like:

| platform_id      | enabled | weight | url_template                                                 |
| ---------------- | ------- | ------ | ------------------------------------------------------------ |
| `linkedin`       | 1       | 0      | `https://www.linkedin.com/sharing/share-offsite/?url=[url]` |
| `facebook_share` | 1       | 1      | `https://www.facebook.com/sharer/sharer.php?u=[url]`         |
| `x`              | 1       | 2      | `https://twitter.com/intent/tweet?url=[url]&text=[title]`    |
| `whatsapp`       | 0       | 3      | `https://api.whatsapp.com/send?text=[title]%20[url]`         |
| `copy`           | 0       | 4      | *(empty — clipboard mode)*                                   |
| `email`          | 0       | 5      | `mailto:?subject=[title]&body=[url]`                         |
| `telegram`       | 0       | 6      | `https://t.me/share/url?url=[url]&text=[title]`              |
| `reddit`         | 0       | 7      | `https://www.reddit.com/submit?url=[url]&title=[title]`      |
| `pinterest`      | 0       | 8      | `https://www.pinterest.com/pin/create/button/?url=[url]...`  |
| `threads`        | 0       | 9      | `https://www.threads.net/intent/post?text=[title]%20[url]`   |
| `bluesky`        | 0       | 10     | `https://bsky.app/intent/compose?text=[title]%20[url]`       |
| `tumblr`         | 0       | 11     | `https://www.tumblr.com/share/link?url=[url]&name=[title]`   |
| `mastodon`       | 0       | 12     | `https://mastodonshare.com/?url=[url]&text=[title]`          |

## Update Hooks

Existing schema migrations in `webshare.install`:

- `webshare_update_8001()` — creates the `webshare_platforms` table
  (legacy upgrade path from the pre-2.0.x config-only storage).
- `webshare_update_8002()` — reorders default platforms to match the
  approved 2.0.x design (LinkedIn, Facebook, X first).
- `webshare_update_8003()` — migrates `alignment` config values from
  `left` / `right` (legacy) to `start` / `end` (logical).
- `webshare_update_8004()` — initialises empty `icon_map` and
  `native_share_icon` config keys.

Run any pending hooks with:

```bash
drush updatedb -y
```

## Querying Platforms Yourself

```php
$platforms = \Drupal::database()
  ->select('webshare_platforms', 'wp')
  ->fields('wp')
  ->condition('enabled', 1)
  ->orderBy('weight')
  ->orderBy('name')
  ->execute()
  ->fetchAll();
```

Each row is a stdClass with the column names as properties. The same
ordering is what `WebshareService::build()` uses internally.
