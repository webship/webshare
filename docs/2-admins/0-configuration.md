# Configuration

Webshare ships one configuration form under
**Administration > Configuration > Web services > Webshare**
(`/admin/config/services/webshare`). This page explains every section
of that form and what it controls.

## Accessing the Settings

### Required Permission

You must have the **"Administer Webshare"** permission to reach the
settings page. By default this is granted to the Administrator role
only - see [Permissions](4-permissions.md) to assign it to other roles.

### Via Admin Menu

1. Click **Configuration** in the toolbar.
2. Under the **Web services** group, click **Webshare**.

### Direct URL

`/admin/config/services/webshare`

## Form Structure

The form has two sections (no vertical tabs - both are visible at once):

1. **Platform management** - the drag-and-drop table of all platforms.
2. **Add Custom Platform** - the AJAX-modal launcher for adding your
   own platforms.

## Platform Management Table

The platforms table has one row per registered platform (13 built-in
out of the box) with four columns:

| Column        | What it controls                                                  |
| ------------- | ----------------------------------------------------------------- |
| Platform      | Display name + helper title. Bold name on top, description below. |
| Enabled       | Checkbox. Unchecked platforms are hidden from the rendered rail.  |
| Weight        | Drag handle. Lower weight = appears earlier in the rail.          |
| Operations    | Dropbutton: **Edit** + **Delete** (both open AJAX modals).        |

The table is `#tabledrag`-enabled - drag rows by the weight handle to
reorder. The form does not save until you click **Save configuration**.

### Built-in Default Set (post-install)

On a fresh install the following platforms are **enabled** in this
order: LinkedIn, Facebook share, X. The remaining 10 (WhatsApp, Copy
URL, Email, Telegram, Reddit, Pinterest, Threads, Bluesky, Tumblr,
Mastodon) are listed but **disabled** - toggle their **Enabled**
checkboxes and Save to add them to the rail.

See [Managing Platforms](1-platforms.md) for the full per-platform
walkthrough including custom-platform creation.

## Add Custom Platform

The **Add Custom Platform** button at the bottom of the platforms table
opens a modal that lets you register a new platform without writing
code. See [Managing Platforms](1-platforms.md#adding-a-custom-platform).

## Block-level vs Module-level Settings

Webshare deliberately keeps presentation settings (title, orientation,
alignment, mobile visibility, native share, placement) **on the block /
Canvas component**, not on this module-level form. The same component
can therefore be placed multiple times with different settings.

The module-level settings page contains only the cross-instance state:

- The **enabled / disabled** flag per platform.
- The **weight** (order) per platform.
- Custom platforms added through the modal.
- The Drupal Core Icons API mapping
  (see [Drupal Core Icons API](5-icons-api.md)) - these keys live in
  `webshare.settings.icon_map` and are not exposed in the form by
  default. They are intended to be set via configuration import or
  in a deployment recipe.

## Caching

Submitting the settings form invalidates the `webshare_platforms` cache
tag. The share rail is rendered with that tag, so:

- Every cached page that contains the rail (via block, Canvas page,
  Canvas page_region, or content template) is refreshed on the next
  request.
- The anonymous page cache picks up the change without a manual cache
  rebuild.

The same tag is invalidated when you add, edit, or delete a platform
via the modal.

## Configuration Storage

The configuration form mutates **two** stores:

1. **Database** - `webshare_platforms` table (one row per platform).
   See [Database Schema](../3-developers/2-database-schema.md).
2. **Drupal config** - `webshare.settings` (icon map + native share
   icon mapping + legacy fallback button list).

The platform table is intentionally stored in the database rather than
config so each environment can carry its own custom platforms without
generating config-export noise.
