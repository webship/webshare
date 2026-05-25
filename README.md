# Webshare Module

Enabling effortless content sharing on popular social media platforms. It provides users with customizable share buttons for networks like Facebook, X, LinkedIn, and more, helping you amplify your content's reach with ease.

With an intuitive setup, the Webshare module allows site administrators to customize button design, placement, and behavior, ensuring seamless alignment with your website’s branding and user experience.

Key Features:

* Seamless integration with major social platforms.
* Native [Web Share API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Share_API)
  support - visitors on supporting browsers/devices share through the operating
  system share sheet (`navigator.share`). On unsupported browsers the platform
  link buttons remain as a graceful fallback. The implementation follows the
  [W3C Web Share spec](https://w3c.github.io/web-share/): the share is only
  triggered from a user gesture (transient activation), passes `title`, `text`
  and `url` (`text` + `url` being the fields most reliably honoured by mobile
  and tablet share targets), guards against concurrent shares, and silently
  ignores the expected `AbortError` / `NotAllowedError` / `InvalidStateError`
  outcomes.
* Customizable, responsive button styles.
* Flexible placement options, including inline or floating buttons.
* Optional analytics to track sharing activity and performance.

The Webshare module is perfect for blogs, news sites, and any content-driven website looking to boost audience engagement and increase social media visibility effortlessly.

## Single-directory component (SDC)

The buttons are rendered through the `webshare:webshare` single-directory
component (`components/webshare/`). It exposes these props:

* `url`, `share_title` - data forwarded to the Web Share API.
* `orientation` - `horizontal` or `vertical`.
* `mobile_visibility` - `all`, `hide_mobile` or `mobile_only`.
* `native_share` / `native_label` - toggle and label the native share button.
* `heading`, `webshare_links_id` - optional heading and list id.

The `buttons` slot receives the render array of platform `<li>` buttons. The
component can be reused directly from any Twig template:

```twig
{{ include('webshare:webshare', {
  url: url,
  orientation: 'vertical',
  mobile_visibility: 'hide_mobile',
}, with_context = false) }}
```

## Webshare block

The **Webshare Block** can be placed in any region. Its block settings let you:

* Pick the **orientation** - horizontal (row) or vertical (column).
* Control **mobile visibility** - show everywhere, hide on mobile, or show on
  mobile only (breakpoint: 768px).
* Toggle the **native share button**.

