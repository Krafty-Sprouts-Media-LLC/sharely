# Features: Novashare 1.5.6 and Sharely 1.0.0

Every feature of the WordPress plugin Novashare (read for its behaviour only;
no code, markup, CSS, icons or wording were taken), and what Sharely does.

Marks: **Teme already does this** · **same in Sharely** · **different in
Sharely** (how) · **better in Sharely** · **can't yet** (the missing Teme
socket, with its FINDINGS number).

## Networks

| Novashare | Sharely |
|---|---|
| 27 share networks and utilities (X, Facebook, LinkedIn, Bluesky, Threads, Mastodon, WhatsApp, Telegram, Reddit, Pinterest, Flipboard, Tumblr, Buffer, LINE, Messenger, VK, Hacker News, Email, SMS, Print, Copy, Pocket, Mix, Xing, Yummly, Subscribe, Share window) | **Different.** The 22 the brief names, plus the device's own share sheet. Pocket left out: it closed in July 2025 (D-06). Mix, Xing, Yummly and Subscribe left out (not asked for; Subscribe is a follow link in Sharely). |
| Each network on or off, in a chosen order | **Same**, as a reorderable list (Teme's repeater, Move up and Move down). |
| Icons from Font Awesome | **Better.** Drawn fresh for Sharely as small inline SVGs; no icon font, nothing loaded from elsewhere. |
| Brand colours | **Better.** Each button's text colour is worked out for contrast (WCAG), so WhatsApp's green gets dark text. |
| Copy link: copies, then shows a browser alert | **Better.** Copies, and tells screen-reader users "Link copied" in a polite status; the icon turns into a tick. The button only appears when the browser can copy. |
| Print | **Same**, hidden without a script; Sharely's buttons also hide when printing. |
| Share window (modal of all networks) | **Different.** A More button made of `<details>`: opens the rest with no script, keyboard and screen-reader friendly. |
| — | **Better.** The phone's own share sheet as a button, shown only where the device has one. |
| Messenger: desktop dialog with a Facebook app ID | **Different.** Messenger's app link, labelled "phones only" in the settings; Sharely needs no Facebook app. |

## Inline buttons

| Novashare | Sharely |
|---|---|
| Above, below, both or none; per post type | **Same**, through Teme's article insertions; Posts and Pages to tick, other types typed (W-15). |
| In feeds and archives | **Teme already does this**: Teme never puts insertions in feeds or the Markdown version; Sharely's buttons are on articles only. |
| Hide above or below a breakpoint | **Different.** A separate phone set with its own width; each set shows on one side of it. |
| Six styles, colours per state, button margin, columns | **Different.** Shape (square, rounded, pill, circle), size, icons or labels, colours (network colours, the site's accent from Settings › Design, or outlined in the theme's colours), alignment including "fill the width"; all drawn with the theme's tokens so it fits any theme. No per-colour pickers. |
| Call-to-action line above | **Same**: a heading, which also names the group for screen readers. |
| Shortcode and block for buttons anywhere | **Can't yet**: a plugin can't add an editor card (W-01). |
| Per-post "hide inline buttons" | **Different.** "No share buttons on this item" in the editor's Sharing panel (W-02). Core's "No inserted content" also works, for every plugin at once. |

## Floating bar

| Novashare | Sharely |
|---|---|
| Fixed column on the left or right | **Same**, through Teme's fixed insertions, which show a side rail only where the screen has room. |
| Becomes a bottom bar on phones | **Can't yet** (W-05): a fixed insertion can't be limited to a screen size. Choose a bottom bar for every screen, or a rail with in-article buttons on phones. |
| Show on scroll, top and edge offsets, colours | **Different.** Core places and labels the bar, with a close button readers can use, and nothing moves or animates. |
| On the posts page, archives, home page | **Different.** Articles only. |

## Share counts

| Novashare | Sharely |
|---|---|
| Counts fetched while the page loads | **Better.** Never at page load: a scheduled job (hourly, daily or weekly, chosen in the settings) or the "Refresh share counts now" button. |
| Facebook (app token), Pinterest, Buffer, Reddit, Tumblr, VK, Yummly | **Different.** Facebook (an access token kept as a secret, sent in a header), Pinterest, Reddit and Hacker News, each ticked on its own and off by default. Buffer, Tumblr and VK no longer answer; Yummly isn't a network Sharely has. |
| — | **Better.** Sharely's own count of share clicks, kept on the site with no cookies and no addresses: item, network, day, number (D-03). Shown for networks Sharely doesn't ask (D-05). |
| Minimum count, total, counts on buttons | **Same.** |
| A stored count never goes down | **Same.** |
| Recover counts from http/https, an old permalink structure, an old domain, up to 5 URLs a post | **Better.** Every address an item has had on Teme is recorded as it changes, plus the http/https twin of each and the old domain, so counts follow a moved item with nothing typed. An old WordPress permalink pattern isn't needed: imported items keep their addresses. |
| Refresh one post's counts; purge all counts | **Different.** Refresh all now from the settings page; counts go with a deleted item. Purge: Teme's "remove data" for the plugin. |
| Counts on the page change at once | **Can't yet** (W-07): pages cached whole show new counts when next purged. |

## Pinterest

| Novashare | Sharely |
|---|---|
| Pin button on images, position, shape, always shown | **Same**: four corners, shown on hover and focus, always on touch screens, keyboard reachable, named "Save “alt text” to Pinterest". |
| Skip small images, excluded images | **Same**: a minimum width; decorative images (empty alt) are skipped. |
| Per-image pin description, no-pin switch, repin ID | **Can't yet** (W-04): no per-image attributes; the alt text is the description, and `data-sharely-nopin` or a `nopin` class in HTML opts out. |
| Per-post Pinterest image and description | **Different.** In the editor's Sharing panel; the image as an address (W-03). |
| Hidden Pinterest images | **Can't yet**: no way to place images only Pinterest sees without per-item media fields (W-02, W-03). |
| Image grid (Pinterest's own picker script) | **Better.** Sharely's own picker of the article's images, in an accessible dialog, with no script from Pinterest. |

## Quotes and highlights

| Novashare | Sharely |
|---|---|
| Click-to-post box (block, shortcode, editor button) | **Can't yet** as a card (W-01). **Different:** every quote an editor writes with Teme's quote gets a "Share this quote" button when "Share buttons on quotes" is on. |
| Highlight to share, mouse only, desktop only | **Better.** By mouse, touch or keyboard: for keyboard users the bar is placed right after the paragraph, announced, and reached with Tab; Escape closes it. Works on phones. |
| Hashtags from the quote | **Different.** Not split out: the quote is posted as written. |

## Follow buttons

| Novashare | Sharely |
|---|---|
| Widget, block and shortcode with 60 networks | **Different.** The site's profiles on the same networks as sharing, plus RSS (the site's own feed) and email, placed before or after a theme region, or after each article (W-09: not inside a region). |
| Custom SVG networks, colours per widget | **Different.** Network colours; the shape follows the share buttons. |
| — | **Better.** Profile links carry `rel="me"`, so Mastodon and others can verify the site. |

## Per-item options

| Novashare | Sharely |
|---|---|
| Social image, title, description per post | **Teme already does this**: core's social title, description and image fields; Sharely reads them. |
| Hide inline, hide floating, disable pins, disable highlight | **Different.** One switch, "No share buttons on this item", in the Sharing panel (W-02). |
| Recovery URLs per post | **Better**: recorded by Sharely itself. |
| Character counters | **Better.** "Preview sharing" in the editor shows, for each network, the title its card shows and what is cut or missing, and offers a title that fits (W-12). |

## Links

| Novashare | Sharely |
|---|---|
| UTM tags with `{{network}}` | **Same**, with `{network}`. |
| Bitly short links | **Same**, made by a job, never while a page loads; the token is a secret sent in a header. Short links replace UTM tags (D-09). |
| X username as via | **Same.** |
| Mastodon: a popup asks for the instance each time | **Better.** Works without a script (Sharely's own page with a form), remembers the reader's server on their device, accepts a handle like `@me@example.social`, and works with Misskey, Sharkey, Firefish and Iceshrimp too. |

## Open Graph

| Novashare | Sharely |
|---|---|
| Open Graph and X card tags, SEO plugin integration | **Teme already does this**: core writes the social tags from its own fields. |

## Admin, tools, import

| Novashare | Sharely |
|---|---|
| Settings page with tabs | **Teme already does this**: Sharely declares its settings in sections; Teme draws, checks and records them. |
| Export, import, restore defaults | **Teme already does this**: settings history. |
| Clean uninstall | **Teme already does this**: "remove data" drops Sharely's tables. |
| Multisite network settings | **Can't yet**: Teme has no multisite. |
| Licence, updater, promo banner | Not in Sharely. |
| — | **Better.** A share report in Sharely's own area: share clicks over 30 days and all time, counts the networks report, a chart of clicks a day, and tables of articles and networks shared most. |
| Import from Social Warfare and others (an add-on) | **Different.** Import from Novashare itself, through Teme's WordPress import: its settings and the follow widget. Saved when a person presses "Apply the Novashare settings"; the network lists, follow profiles and tokens are listed to set by hand; per-post data and counts can't come across (W-10). |
| Accessibility mode | **Better.** Accessible by default: named controls, visible focus, keyboard use, reduced motion; axe finds nothing at 1280 and 375 pixels. |
| Analytics events | Not in Novashare either; Sharely's report replaces it without tracking. |
