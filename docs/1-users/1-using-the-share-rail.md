# Using the Share Rail

The Webshare share rail is the small bar of social-network buttons that
appears on a page configured to use Webshare. This page describes what
each button does from a visitor's point of view.

## Anatomy of the Rail

The rail is a `<nav>` landmark with an accessible name (default: "Share").
Inside, an unordered list (`<ul>`) holds one `<li>` per enabled platform,
plus an optional **native share** button.

```
┌───────────────────────────────────────────────────┐
│ Share                                              │  ← optional heading
│ ┌──┐ ┌──┐ ┌──┐ ┌──┐ ┌──┐                          │
│ │ in│ │ f │ │ X │ │ ⇧ │ │ 📋│   (icons → links)    │
│ └──┘ └──┘ └──┘ └──┘ └──┘                          │
└───────────────────────────────────────────────────┘
```

## Platform Buttons

Each enabled platform button is a link that opens the platform's share
endpoint in a **new tab**, with `target="_blank"` and
`rel="noopener noreferrer"` for safe cross-origin navigation. The current
page URL (and optionally a title) is passed to the platform.

| Platform   | Share URL pattern                                              |
| ---------- | -------------------------------------------------------------- |
| LinkedIn   | `https://www.linkedin.com/sharing/share-offsite/?url=<url>`    |
| Facebook   | `https://www.facebook.com/sharer/sharer.php?u=<url>`           |
| X (Twitter)| `https://twitter.com/intent/tweet?url=<url>&text=<title>`      |
| WhatsApp   | `https://api.whatsapp.com/send?text=<title>%20<url>`           |
| Email      | `mailto:?subject=<title>&body=<url>`                           |
| Telegram   | `https://t.me/share/url?url=<url>&text=<title>`                |
| Reddit     | `https://www.reddit.com/submit?url=<url>&title=<title>`        |
| Pinterest  | `https://www.pinterest.com/pin/create/button/?url=<url>...`    |
| Threads    | `https://www.threads.net/intent/post?text=<title>%20<url>`     |
| Bluesky    | `https://bsky.app/intent/compose?text=<title>%20<url>`         |
| Tumblr     | `https://www.tumblr.com/share/link?url=<url>&name=<title>`     |
| Mastodon   | `https://mastodonshare.com/?url=<url>&text=<title>`            |

## Copy URL Button

If the **Copy URL** platform is enabled, its button is rendered as a
`<button>`, not a link. Clicking it copies the current page URL to the
clipboard via the browser's [Clipboard API][clipboard]. The button is
labelled with the platform's title (default: "Copy site URL") and
exposes the URL as a `data-webshare-copy` attribute for any custom JS
that wants to observe the copy event.

## Native Share Button

When the block / Canvas component has **Show native share button** enabled,
an extra `<button class="webshare__native-button">` is rendered at the
start of the list. Clicking it invokes
[`navigator.share()`][webshareapi] with a payload of:

```js
{
  url:   document.querySelector('.webshare').dataset.webshareUrl,
  title: document.querySelector('.webshare').dataset.webshareTitle,
  text:  document.querySelector('.webshare').dataset.webshareText,
}
```

On supporting browsers — typically mobile Safari / Chrome on iOS, Android,
and recent desktop builds — this opens the OS share sheet so the visitor
can share through any installed app (Mail, Messages, Slack, AirDrop, etc.).

On browsers without Web Share API support the button is hidden via CSS.

## Layout Variants

The block / Canvas component supports four presentation modes the visitor
will see:

| Setting             | Visual effect                                            |
| ------------------- | -------------------------------------------------------- |
| Orientation         | `horizontal` (row) or `vertical` (column)                |
| Alignment           | `start` or `end` (RTL-aware; "end" floats right in LTR)  |
| Placement           | `inline` (flows with content) or `rail-end` (sticky col) |
| Mobile visibility   | Show on all devices / hide on mobile / mobile-only       |

## Accessibility

- The `<nav>` element exposes the rail as an ARIA navigation landmark
  with an `aria-label`.
- Each platform link has its own `aria-label` (e.g. *"Share on LinkedIn
  (opens in a new tab)"*).
- Icons are marked `aria-hidden="true"` so screen readers read the label
  rather than the SVG content.
- The native share button has its own `aria-label` (default: "Share").

[clipboard]: https://developer.mozilla.org/en-US/docs/Web/API/Clipboard_API
[webshareapi]: https://developer.mozilla.org/en-US/docs/Web/API/Web_Share_API
