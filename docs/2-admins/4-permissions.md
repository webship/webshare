# Permissions

Webshare ships **one permission**: *Administer Webshare*.

## The "Administer Webshare" Permission

| Field        | Value                                                            |
| ------------ | ---------------------------------------------------------------- |
| Machine name | `administer webshare`                                            |
| Title        | Administer Webshare                                              |
| Description  | Allow the user to administer the Webshare module settings.       |
| Restricted?  | **Yes**. Required for the configuration page and platform CRUD.  |

This permission is required to:

- Reach the settings form at `/admin/config/services/webshare`.
- Add a custom platform via the modal.
- Edit an existing platform (built-in or custom).
- Delete a custom platform.
- Save the platform table (enable / disable / weight changes).

Without this permission Drupal returns a **403 Access denied** on every
admin route under `/admin/config/services/webshare/*`.

## Assigning the Permission

1. Navigate to **Administration > People > Permissions**
   (`/admin/people/permissions`).
2. Scroll to the **Webshare** section.
3. Tick **Administer Webshare** for any role that should manage the
   share rail.
4. Click **Save permissions**.

### Default Assignments

On a fresh install only the **Administrator** role (which by default
has *all* permissions) holds this permission. Standard roles —
*Anonymous user*, *Authenticated user*, and the Standard-profile
*Content editor* — do not.

The Webshare automated test suite asserts exactly this matrix in
`tests/features/drupal/04-01-01-access-control.feature`.

## What Doesn't Require This Permission

- **Viewing** the rendered share rail on the front end. The rail is
  rendered for any visitor — anonymous or authenticated.
- **Placing the Share block** in a region. This requires the
  *Administer blocks* permission, which is a separate (core) right.
- **Placing the Share component in Canvas**. This requires the
  Canvas-specific *Administer components* / *Edit Canvas pages* style
  permissions provided by the `drupal/canvas` module.

## Cache Implications

Visibility of the settings page is **per-permission**, so the standard
`user.permissions` cache context naturally varies the rendered admin
toolbar (the Webshare item only appears for users who hold the
permission). The settings page itself is not cached for anonymous
visitors because it requires the permission.

## Custom Roles

To create a role that can administer Webshare but not the rest of the
site (e.g. a "Social-share editor"):

1. **People > Roles > Add role**, name it.
2. **People > Permissions**, tick **Administer Webshare** for the new
   role.
3. Assign the role to relevant users from **People > List**.

This separates Webshare administration from the broader admin role
without granting any other elevated rights.
