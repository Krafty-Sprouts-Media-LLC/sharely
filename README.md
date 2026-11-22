# Sharely

Share buttons for TemeCMS that fit your theme, work without JavaScript, and
count shares without watching your readers.

By Krafty Sprouts Media, LLC · version 1.0.0 · needs Teme 1.0 or later ·
GPL-2.0-or-later.

## What it does

- **Share buttons in your articles**: above, below or both, on the content
  types you choose. Each is a plain link to the network's own share page,
  so it works with scripts off.
- **A floating share bar** on the left or right of wide screens, or along
  the bottom. Teme draws it with a label and a close button.
- **A different set for phones**, shown below a width you choose, such as
  the phone's own share menu, WhatsApp and Copy link.
- **A More button** that opens the rest of the buttons, with no script.
- **Your own follow buttons**: your profiles, your email address and your
  site's feed, before or after one of your theme's regions, or after each
  article. Profile links carry `rel="me"`, so Mastodon can verify your site.
- **Highlight to share**: a reader selects words in an article, by mouse,
  touch or keyboard, and a small bar offers to share them.
- **Share buttons on quotes**: every quote in an article gets a "Share this
  quote" button.
- **Pinterest**: a pin button on your article's images, and a picker that lets
  the reader choose which image to pin.
- **Mastodon done properly**: readers type their own server once; their device
  remembers it. Works without a script, and with Misskey, Sharkey, Firefish
  and Iceshrimp too. Sharely only sends a reader to a public server name (never
  an address number, `localhost`, a port or a path), and always shows a page
  that says "You’re leaving for that server to share this post" with a
  Continue link first.
- **Share counts**: off until you turn them on. Counting share clicks on your
  own site is one setting; asking Facebook, Pinterest, Reddit or Hacker News
  how often each article was shared there, with a background job, is
  another. Counts follow an article to a new address.
- **A share report** under Site › Sharely: share clicks over the last 30
  days and all time, a chart of clicks a day, and which articles and networks
  are shared most.
- **A Sharing panel in the editor**: preview how the item looks on each
  network, with a title that fits where one is cut, and set the item's own
  options: no share buttons here, words to post with the link, and a
  Pinterest description and image.
- **UTM tags** and **Bitly short links** for shared addresses.
- **Import from Novashare** when you move a WordPress site that used it.

## Networks

X, Facebook, LinkedIn, Bluesky, Threads, Mastodon, WhatsApp, Telegram,
Reddit, Pinterest, Flipboard, Tumblr, Buffer, LINE, Messenger (phones only),
VK, Hacker News, email, text message, print, copy link, and the device's own
share menu. Follow buttons can also link to your site's feed. Pocket is not
offered: it closed in July 2025.

## Settings

Under Settings › Plugin settings › Sharely (or Tools › Sharely):

| Section | Settings |
|---|---|
| Where the buttons show | Above, below, both or nowhere; Posts, Pages and other content types; the floating bar; a heading |
| Networks | The share buttons in order; a More button after so many; a different set on phones and the width that counts as a phone |
| How the buttons look | Shape, size, icons or icons and names, colors (network colors, your site's accent from Settings › Design, or outlined in your theme's colors), alignment |
| Share counts | Count share clicks (off by default); show counts and the total; hide counts below a number; ask networks for their counts, which ones, and how often; a Facebook access token; your site's old address |
| Pinterest | Pin buttons on images, the smallest image to pin, the corner; let readers pick the image |
| Quotes and highlights | Highlight to share and its networks; share buttons on quotes |
| Follow buttons | Your profiles; where they show; a heading |
| Shared links | UTM tags; your X account; the Mastodon server readers are offered first; Bitly short links and the access token |

Buttons on the settings page: **Bring the article list up to date**,
**Refresh share counts now**, and **Apply the Novashare settings**.

To turn the buttons off for one item, open it in the editor, choose
**Sharing** in the tools, and save "No share buttons on this item". Teme's
own **No inserted content** field also turns them off, together with
anything else plugins add to that item.

## If your theme has its own share buttons

Turn them off in your theme's settings when you use Sharely, or readers see
two sets. In the Fable look of the Egberipiribo theme that is Settings ›
Design › "Share: show buttons on articles".

## Moving from Novashare

Import your WordPress site with Teme's importer while Sharely is enabled, and
agree to Sharely reading the `novashare` and `widget_novashare_follow_widget`
options. Then press **Apply the Novashare settings** (on Sharely's settings
page, or Site › Sharely › Novashare import). Sharely saves what it can and
lists what to set by hand: the order of your share buttons, your follow
profiles, and your Bitly and Facebook tokens (which a plugin may never save
for you). Novashare kept each post's own data and counts in a table of its
own, which Teme's import doesn't read yet, so they don't come across.

## Privacy

**What Sharely stores**, in its own tables:

- each published item's title, address, type and language, and every address
  it has had;
- the options you set for an item in the editor;
- only if you turn counting on: for each item, network and day, how many
  times its share button was clicked: one number, nothing else;
- the counts networks report, if you turn that on;
- a short link per item, if you connect Bitly;
- a copy of its own settings, for its public addresses to read.

**What Sharely never does:**

- set a cookie, or keep a reader's address, browser, device or the time of a
  click. To count a click only once per article from one address in ten
  minutes (so repeated requests can't inflate a count), it keeps, in your
  site's cache for those ten minutes and nowhere else, a one-way code made
  from the address, the article and a random number that changes every day.
  The code can't be turned back into an address, and it is gone after ten
  minutes;
- load anything from another site on your pages: icons, styles and scripts
  all come from your site;
- send anything anywhere until you turn it on. When you do: asking networks
  for counts sends each article's address to the networks you check;
  Bitly gets each article's address once; a reader who shares goes to the
  network they chose.

The reader's Mastodon server is remembered in their own browser, never sent
to your site except as part of the one share they ask for. Sharely only
passes it on after checking it is a public server name and showing the reader
where they are going.

Removing Sharely's data (Plugins › Sharely › Remove data, after disabling it)
deletes all of the above.

## License

Copyright © 2026 Krafty Sprouts Media, LLC. Sharely is free software, released
under the GNU General Public License, version 2 or (at your option) any later
version: see [LICENSE](LICENSE). It was written fresh. It takes nothing from
Novashare or any other plugin: no code, markup, styles, icons or wording.

## Accessibility

Every button is named for screen readers ("Share on Bluesky (opens in a new
tab)"), shows where keyboard focus is, and works by keyboard. Buttons that
need a script stay hidden until it runs. Motion stops for readers who ask for
reduced motion, and the buttons don't print.
