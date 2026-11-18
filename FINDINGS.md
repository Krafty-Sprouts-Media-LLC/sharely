# Findings

Where Teme's public handbook was missing, wrong or unclear for building
Sharely, and every missing socket ("wall"): what I expected, and what Sharely
does instead. Nothing here was worked around by changing Teme. Read against
Teme `main` at 4fbb9d48 (17 November 2026), contracts 1.0.0.

## Walls (missing sockets)

### W-01 A plugin can't add a card to the editor

**Expected:** a way for a plugin to register an editor card, such as a
click-to-share quote (a quote with its own share button), as core registers
its own (`docs/reference/cards.md`: every card is "Registered by core").
**Found:** no socket; a document may hold only core's cards.
**Instead:** "Share buttons on quotes": Sharely's highlight script adds a
"Share this quote" button under every quote (`blockquote`) in an article, so
any quote an editor writes with core's own quote can be shared, and
highlight-to-share covers any other passage. An editor can't choose the
words to post separately from the quote's text, nor a style per quote.
**Ask:** plugin-registered cards with attributes and a server-drawn HTML.

### W-02 A plugin can't add fields to an item

**Expected:** a way to declare per-item fields (share text, Pinterest image
and description, "no share buttons here"), shown in the editor's sidebar
like core's social fields.
**Found:** none. Core's own social title, description and image cover part
of it (Sharely uses them); core's "No inserted content" (`insertions_off`)
turns off every plugin's insertions, ads included, not just sharing.
**Instead:** the editor panel's "Save sharing options" action has four
fields (no buttons, words to post, Pinterest description, Pinterest image
address) and its job stores them in `sharely_items`. Because an action's
fields have only a declared `default`, the form can't show what is saved
for this item: the panel's status line says when buttons are off.
**Ask:** plugin item fields (declared in the manifest, stored by core,
read through `ContentReader`/`ArticleFacts`), or action fields whose
starting values the panel's `view()` can give.

### W-03 No address for a media item

**Expected:** a way to turn a media ID (the `social_image` and
`featured_image` fields hold IDs) into its public address, to give Pinterest
the image to pin and to tell the preview which image a card shows.
**Found:** `MediaRef` has no URL, and no door answers one.
**Instead:** Pinterest's share link carries an image only when the editor
pasted one into the Sharing panel; otherwise "Let readers pick the image"
(on by default) lets the reader choose from the article's own images, read
in the browser. The preview only says whether the item has an image.
**Ask:** `MediaRef::$url` or a `MediaReader` door answering a media item's
public addresses and sizes.

### W-04 No per-image attributes

**Expected:** a way for an editor to mark one image "don't pin", or give it
its own pin description, as Novashare did per image.
**Found:** core's image card has `alt`, `decorative`, `caption`, `credit`,
`width`; nothing a plugin can add.
**Instead:** pin buttons skip decorative images (empty alt), images
narrower than the chosen width, and any image carrying `data-sharely-nopin`,
`data-pin-nopin` or a `nopin` class (theme or embedded HTML can use these);
the pin description is the image's alt text, else the item's Pinterest
description, else its title.
**Ask:** plugin attributes on core cards, or a `nopin`-style switch on the
image card.

### W-05 A fixed insertion can't be limited to a screen size

**Expected:** to show the floating bar as a side rail on wide screens and a
bottom bar on phones, as Novashare does.
**Found:** core draws a fixed insertion as its own `<aside>` with a label and
a close button; a side rail shows only where there is room, but a bottom
bar shows on every screen, and a plugin can't hide core's aside on wide
screens.
**Instead:** the floating bar is a left rail, a right rail, or a bottom bar
on every screen. Phones see the in-article buttons (and the phone set) when
a rail is chosen. The `floating_phone` setting was dropped before release
rather than ship a setting that can't work (DECISIONS D-07).
**Ask:** a `PagePosition::fixed()` option for "only below/above this width",
or let a rail fall back to the bottom edge on narrow screens.

### W-06 A public route has no doors

**Expected:** the click redirect and the Mastodon page to read Sharely's
settings (UTM tags, X account, whether to count).
**Found:** "A public route's handler is built with none of your plugin's
doors" (Public routes).
**Instead:** Sharely copies its settings group into `sharely_state` whenever
it is saved (`SettingsChanged`) and every hour (the sync job), and the routes
read that copy. Before the first save or sync after enabling, the routes use
the declared defaults.
**Ask:** give route handlers `SettingsReader` (no secrets).

### W-07 Counts on cached pages go stale

**Expected:** after the counts job stores new numbers, to tell core the
pages showing them changed.
**Found:** `InsertionsChanged::notify()` must run inside an Operation; a
job isn't one, and a plugin can't start one.
**Instead:** counts shown on a page update when its cache is next purged:
the article is published again, Sharely's settings are saved, or the cache
expires. The share report always shows the stored numbers.
**Ask:** let a job call `notify()`, or give jobs a way to run their writes
as an Operation.

### W-08 A theme can't know Sharely is drawing share buttons

**Expected:** that a theme which draws its own share buttons (the Fable skin
of the egberipiribo theme, `views/skins/fable/partials/article-share.blade.php`,
switched by its `show_share` setting) could step aside while a sharing
plugin is active.
**Found:** a theme sees only its own settings and the view data in
`docs/reference/theme.md`; nothing says which plugin features run, and a
page insertion can only go before or after a region, never fill one.
**Instead:** nothing; the site owner must turn the theme's own buttons off
(Fable: Settings › Design › "Share: show buttons on articles") when they
enable Sharely. The README says so. Sharely changes no theme.
**Ask (either):** (a) view data naming the plugin features in use, such as
`$site->features` including `sharing`, so a theme writes
`@unless(in_array('sharing', $site->features))`; or (b) a core-declared
region, such as `article-share`, that a plugin can *fill* (not just sit
beside), which a theme draws instead of its own buttons whenever a plugin
filled it.

### W-09 A page insertion can't go inside a region

**Expected:** follow buttons "as a region item": an item an editor places
inside a theme region beside their own content.
**Found:** `PagePosition::region()` draws before or after a region only.
**Instead:** follow buttons go before or after the region the administrator
names, or after each article.
**Ask:** region items from plugins, which editors place and order.

### W-10 The import can't save a plugin's settings, or read its tables

Found while writing the Novashare importer; details in DECISIONS D-08.

- **No person behind the run.** `PluginSettingsWriter::write()` needs the
  person the work runs for; `ImportScope` doesn't say who that is
  (`JobContext` has `personId()`; `ImportScope` has no equivalent).
  **Instead:** `options()` maps everything and keeps it in `sharely_state`;
  "Apply the Novashare settings" (a settings-page action, and on the share
  report's Novashare import page) saves it as the person who presses it.
  **Ask:** `ImportScope::personId()`.
- **Nothing to report with.** `options()` returns `void`; only `record()`
  has an outcome. **Instead:** the share report's Novashare import page
  shows what is left to set by hand. **Ask:** an outcome or report
  sentences from `options()`.
- **Repeaters and secrets can't be written.** The writer takes only text,
  textarea, number, boolean, select, multiselect, email and color, so the
  network lists, follow profiles and tokens can't be imported; the report
  names them. The handbook's import page says to "store imported keys and
  tokens as secret settings", which no door allows. **Ask:** writable
  repeaters of writable fields; fix import.md.
- **A plugin's own WordPress tables aren't read.** Novashare keeps every
  post's data (social overrides, Pinterest image and description, hidden
  buttons, share counts, recovery addresses, short links) in its own
  `{prefix}novashare_meta` table, which "is not read in this version". None
  of it comes across; only its settings and the follow widget do.
  **Ask:** declared plugin tables in `imports`.
- **One sentence for every refusal.** `WriteRefused` carries no key or
  reason, so telling a person refusal from a bad value means retrying key by
  key. `WriteRefused` also lives in `Content` though the settings writer
  throws it.

### W-11 A finished job's message is replaced

**Expected:** the last `progress()` message to stay readable in Tools ›
Jobs, so "Apply the Novashare settings" can say what is left to do.
**Found:** a finished run's message reads "Finished.".
**Instead:** what's left is kept in `sharely_state` and shown as a figure on
the share report's "Novashare import" page.

### W-12 The editor panel can't show a preview

**Expected:** a panel that shows how the item will look on each network.
**Found:** a panel shows a status line and suggestions, and each suggestion
must carry a change.
**Instead:** "Preview sharing" lists one suggestion per network, labelled
with the title that network's card shows ("X: Feeding roses in…"), its
detail saying what is cut or missing; using it sets the social title to one
that fits that network. It is a useful preview, but not a picture of the
card.
**Ask:** read-only rows in `PanelView`, or a card-preview block.

### W-13 `ArticleFacts` has no title

**Expected:** the article's title (and social title) in `ArticleFacts`, to
put in share links while the page draws.
**Found:** ID, type, terms, language, authors, size and path only.
**Instead:** Sharely keeps its own copy of each published item's title and
address (`ContentPublished`, and an hourly sync through `ContentReader`).
An item it hasn't seen yet is shared by its address alone until the next
sync; networks read the title from the page's own social tags.
**Ask:** title and social fields in `ArticleFacts`.

### W-14 Social fields after a scheduled publish

**Expected:** to read an item's social description whenever it is
published.
**Found:** `ContentReader::draft()` needs the person the work acts for; a
listener can use `ContentPublished::$actorId`, but a scheduled publish runs
with no signed-in person, and `published()` lists `ItemRef`s without fields.
**Instead:** the description is read when a person publishes; a scheduled
item gets its title and address, and its description when next published by
a person. The preview job reads everything, as the person who asks.

### W-15 One content type per setting

**Expected:** a setting to choose several content types.
**Found:** the `type` setting holds one.
**Instead:** a multiselect of Posts and Pages, plus a text setting for the
keys of other types.

### W-16 Repeater fields can't be URLs

**Expected:** follow profiles as `url` fields.
**Found:** a repeater's fields may be text, textarea, number, boolean,
select, multiselect, email, color or code.
**Instead:** a text field, checked when drawn: only `http(s)` addresses and
email addresses become links.

### W-17 The 100 ms budget on a cold, busy machine

Twice, in kit runs on a heavily loaded machine, Sharely's page insertions
took 154 ms and 101 ms and Teme skipped them for that page ("over the 100 ms
budget"): the buttons drew (article insertions) but not the stylesheet and
scripts. Measured, almost all of it was the first read of three stylesheet
files from disk, which Sharely then wrote into the page. Fixed: the scripts'
stylesheets are now linked from `/sharely/assets/` (cached by browsers) and
drawing a page reads no file; the cold time of that piece fell from about
100 ms to 3 ms, and each page is 6 KB lighter. What remains cold is PHP's
first load of Sharely's classes (about 15 ms here). **Ask:** say in the
handbook that the budget includes the first request's class loading, so
authors keep file reads out of insertions.

## The handbook

### H-01 Area blocks: the keys aren't named

"A plugin's own area" lists the blocks and what each "declares", but not
that each block is an object with `type` and an `id` (2 to 40 lower-case
letters, digits and hyphens, starting with a letter), nor that a figures
block holds 1 to 4 figures; the schema leaves pages and blocks as plain
objects. I learned both from the refusals at enable ("A block's id must
be…", "A figures block holds 1 to 4 figures."). The refusals are clear;
the page should say it.

### H-02 The development site's `TEME_VERSION` decides what can be tested

`teme-dev`'s `.env` sets `TEME_VERSION=0.8.0-alpha.1` (an earlier plugin
needed it), and the testing kit uses it, so a `^1.0` plugin is refused
("requires Teme ^1.0, but this is Teme 0.8.0-alpha.1"). Kit runs pass
`TEME_VERSION=1.0.0` on the command line. "Set up a development site" could
say that one checkout can't host plugins needing different majors, and how
to pick the version per test run.

### H-03 An editor panel action's payload: the values' key

"A panel in the editor" says the payload holds "the panel, the action, the
item's ID, the revision the person saw, and the form's values" and shows
`payload()['itemId']`, but not the key the values are under. Sharely reads
`values`, `fields` or `form`, falling back to the top level (`Jobs\Payload`).

### H-04 Insertions are re-serialised

Core re-writes inserted HTML: `defer` becomes `defer=""`, `hidden` becomes
`hidden=""`. Harmless, but a plugin testing its markup by string meets it.
Worth a line in "What core cleans".

### H-05 Which scripts a page allows

"Content on every page" says a per-visitor choice needs "a script your plugin
serves from its own route or an approved inline script", but nowhere says
the public pages' policy has `script-src 'self'` (confirmed in a kit test),
which is what makes a route-served script work with no approval. One
sentence would save a plugin author from requesting approvals it doesn't
need.

### H-06 Smaller points

- `import.md` doesn't say whether `options()` runs inside a transaction, or
  what it receives on a catch-up run.
- "Plugin settings and secrets" lists the types a plugin may write but not,
  explicitly, that `repeater` is refused.
- `ContentPublished` carries `actorId`, but the handbook doesn't say whether
  a listener may read the draft with it on a scheduled publish (W-14).
