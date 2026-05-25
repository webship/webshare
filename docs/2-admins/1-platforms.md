# Managing Platforms

This page covers everything you can do with the **platforms table** on
the Webshare settings form: toggling the built-in 13, reordering them,
editing their labels and icons, and adding your own custom platforms.

## The 13 Built-In Platforms

Webshare ships these platforms out of the box. The first three (★) are
enabled on a fresh install; the rest are disabled.

| ★ | Platform ID      | Display name      | Sharing URL pattern (`[url]` and `[title]` are substituted) |
| - | ---------------- | ----------------- | ----------------------------------------------------------- |
| ★ | `linkedin`       | LinkedIn          | `linkedin.com/sharing/share-offsite/?url=[url]`            |
| ★ | `facebook_share` | Facebook share    | `facebook.com/sharer/sharer.php?u=[url]`                    |
| ★ | `x`              | X                 | `twitter.com/intent/tweet?url=[url]&text=[title]`           |
|   | `whatsapp`       | WhatsApp          | `api.whatsapp.com/send?text=[title]%20[url]`                |
|   | `copy`           | Copy URL          | *(clipboard button - no URL template)*                      |
|   | `email`          | Email             | `mailto:?subject=[title]&body=[url]`                        |
|   | `telegram`       | Telegram          | `t.me/share/url?url=[url]&text=[title]`                     |
|   | `reddit`         | Reddit            | `reddit.com/submit?url=[url]&title=[title]`                 |
|   | `pinterest`      | Pinterest         | `pinterest.com/pin/create/button/?url=[url]...`             |
|   | `threads`        | Threads           | `threads.net/intent/post?text=[title]%20[url]`              |
|   | `bluesky`        | Bluesky           | `bsky.app/intent/compose?text=[title]%20[url]`              |
|   | `tumblr`         | Tumblr            | `tumblr.com/share/link?url=[url]&name=[title]`              |
|   | `mastodon`       | Mastodon          | `mastodonshare.com/?url=[url]&text=[title]`                 |

## Enabling / Disabling Platforms

1. Open **/admin/config/services/webshare**.
2. Tick or untick the **Enabled** checkbox next to each platform you
   want to add to or remove from the rail.
3. Click **Save configuration** at the bottom.

The change takes effect immediately; the `webshare_platforms` cache tag
is invalidated on save so anonymous page caches refresh on the next
request.

## Reordering Platforms

The platforms table is `#tabledrag`-enabled.

1. Hover over the drag handle (▥) on the **Weight** column.
2. Drag the row up or down to the desired position.
3. Click **Save configuration**.

Platforms with a lower weight appear earlier in the rendered rail.

## Editing a Built-In Platform

Click the **Edit** operation in the dropbutton on the row you want to
change. An AJAX modal opens with the following fields:

- **Platform Name** - display name shown to administrators.
- **Platform Title** - accessible label / hover title shown on the
  rendered share link (`aria-label`).
- **Sharing URL Template** - pattern with `[url]` / `[title]` tokens;
  empty for the clipboard-style "Copy URL" platform.
- **Platform Icon** - optional file upload to override the bundled SVG.
- **Enabled** - same checkbox as the table column.
- **Weight** - same weight as the table column.

Saves go directly to the database; you do not need to click **Save
configuration** on the parent form.

## Adding a Custom Platform

Some networks (e.g. a specific Mastodon instance or a closed-room
sharer) are not bundled. To add one:

1. Click **Add Custom Platform** at the bottom of the table.
2. Fill in the same fields as the Edit modal:
    - **Platform Name** - required.
    - **Platform ID** - auto-generated machine name from the name (you
      can override it; must be unique).
    - **Platform Title** - accessible label.
    - **Sharing URL Template** - e.g.
      `https://my.mastodon.host/share?text=[title]%20[url]`.
    - **Platform Icon** - upload an SVG or PNG.
3. Click **Save**. The new row appears in the table marked as
   `is_custom`.

Custom platforms behave exactly like built-in ones - the same
enable / weight / edit / delete operations apply.

## Deleting a Platform

Click the **Delete** operation in the dropbutton. A confirmation modal
opens; clicking **Delete Platform** removes the row from the database.

You can delete both custom and built-in platforms; if you delete a
built-in by mistake, re-enable the module to restore it from the
default config (`webshare.install` migrates the defaults on every fresh
install).
