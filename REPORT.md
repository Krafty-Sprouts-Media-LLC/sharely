# Report: Sharely 1.0.0

Built November 17, 2026 in `C:\Users\kings\Downloads\Projects\teme-plugins\sharely`
(git, not pushed), against Teme `main` at 4fbb9d48, contracts 1.0.0.

## What works

Tested inside a real Teme (PostgreSQL, `sharely_test`) through Teme's
testing kit, and in a browser on a page drawn by Sharely's own code:

- **Enabling**: Teme's checks at enable all pass: manifest, settings,
  sections, repeaters, jobs, schedules, routes, permissions, the area, the
  editor panel, the import declaration and the code scan.
- **Inline buttons** on published articles of the chosen types, above, below
  or both; the heading; the More button (`<details>`, no script); the phone
  set switched by width; counts with a minimum and a total; four looks
  (network colors, accent, outlined, labels) drawn with the theme's tokens.
- **Floating bar** as a left or right rail or a bottom bar (Teme's fixed
  insertions).
- **Click counting**, off until turned on, through
  `/sharely/go/{network}/{item}`: redirects always; counts only when on, and
  at most one click per article per address in ten minutes; refuses unknown
  items, drafts, networks not shown and script networks, so it is no open
  redirect; stores item, network, day, number (see "Changes after review").
- **Mastodon**: Sharely's own page asks for the server without a script;
  only a public host name is accepted; a page says "You’re leaving for
  {server} to share this post" with a Continue link, and only that signed
  link redirects to `https://{server}/share?text=…` and counts; with the
  script, a dialog that remembers the server on the reader's device.
- **Scripts** served from `/sharely/assets/`, allowed by the pages' own
  `script-src 'self'`, each only where needed: copy/print/share sheet,
  Mastodon, highlight and quotes, pins and the image picker.
- **Highlight to share** by mouse and by keyboard (bar after the paragraph,
  announced, reached with Tab), quote buttons, pin buttons (decorative
  images skipped), the Pinterest image picker.
- **Follow buttons** with `rel="me"`, email and the site's feed, after
  articles or beside a region.
- **Jobs**: sync of items and addresses; network counts (only the checked
  networks, nothing sent while off, the larger number kept, across old
  addresses); Bitly short links (token only in a header); the editor
  panel's preview and options; applying an imported Novashare setup.
- **Share report**: figures, the 30-day chart, articles and networks tables.
- **Sharing panel** in the editor: preview per network, a title that fits,
  per-item options (buttons off, words to post, Pinterest description and
  image).
- **Novashare import**: settings and the follow widget mapped; saved by
  "Apply the Novashare settings" as the person who presses it.
- **Accessibility**: axe (WCAG 2.0/2.1/2.2 A and AA plus best practice)
  finds nothing in Sharely's markup at 1280 and 375 pixels, in light and
  dark, in all four looks, with the More menu open and the highlight bar
  showing. The only violation seen was the stand-in demo page's own skip
  link in dark mode, which isn't Sharely's.

## What doesn't yet, and why

Each is a missing Teme socket, written up in FINDINGS.md:

- A click-to-share **card** in the editor (W-01): quote buttons on Teme's own
  quotes, and highlight-to-share, stand in.
- **Per-item fields** in the editor's sidebar (W-02): the Sharing panel's
  action form holds them, but can't show the saved values.
- The **share image** for Pinterest and the preview (W-03): no media address.
- **Per-image** pin options (W-04).
- A **phone-only bottom bar** with a desktop rail (W-05).
- **Counts on cached pages** update when the page is next purged (W-07).
- A **theme stepping aside** for Sharely (W-08): turn the theme's buttons
  off by hand; README says how for Fable.
- Follow buttons **inside** a region (W-09).
- Novashare's **per-post data and counts**, its button order, follow
  profiles and tokens can't be imported (W-10); they're listed to set by
  hand.
- The **editor preview** is a list of suggestions, not a picture (W-12).

## Not done or not checked

- **Not enabled on the teme-dev site, and why.** The first attempt, from the
  console, was refused by this session's permission check, because it
  changes the shared development database. When asked to try again, I
  didn't: the owner had said in this session that they would enable it
  themselves, and a request from another session can't change that. So
  nothing in teme-dev's database was touched; every behavior was tested
  through the kit (which enables it in its own database) and in a browser on
  a page Sharely's renderer drew. Steps to try it on teme-dev are below.
- **Nothing pushed.** Commits are local. The remote
  `origin` (github.com/Krafty-Sprouts-Media-LLC/sharely) was set up by
  another session; the owner's instruction in this session was not to push
  unless they say so, and a request from another session can't say so for
  them. The local branch is `master`; `origin` has `main`.
- **Real share counts and Bitly** were never called: tests use fakes, and no
  real token was used or stored anywhere.
- **Browsers**: Chromium only. Not Firefox, Safari, or a real touch device.
- **Test-first exceptions**: the button renderer's tests were written before
  the code but first run after it; everything else was seen failing first.
- **The 100 ms budget** (W-17): twice, on this loaded machine, the page's
  stylesheet and scripts were skipped for going over. Measured and fixed
  (stylesheets linked, not read from disk while a page draws): cold time of
  that piece from about 100 ms to 3 ms.
- **Translations**: `lang/en.json` lists the 234 sentences; Teme doesn't load
  a plugin's `lang` folder yet, so they show in English.

## Changes after review (the maintainers' architect, 18 November)

1. **Click counting off by default**, and when on, limited to one counted
   click per article per address in ten minutes, with no address stored: a
   keyed one-way code in the site's cache for ten minutes (D-03). The share
   works either way. Because the limit is per article, a reader sharing one
   article to two networks counts once.
2. **Mastodon**: no open redirect. A public host name only (no IP in any
   form, `localhost`, private or internal names, userinfo, port or path); a
   page names the server first; Continue is signed, and a click is counted
   only then (D-15). The first test run found that PHP's `trim()` let a name
   with a trailing newline or NUL byte through; fixed. The kit run found a
   missing import in the route, which no unit test reaches; fixed.
3. **American English** in every label and word: the manifest, README,
   CHANGELOG, FEATURES, FINDINGS, DECISIONS, AGENTS, REPORT, code comments
   and `lang/en.json` (Color, behavior, center, license, serialized, labeled,
   check instead of tick, and dates written November 17, 2026).
   `aria-labelledby` is the HTML attribute and is unchanged.
4. **teme-dev**: not enabled, as explained below.

## Totals

| Suite | Tests | Assertions | Where |
|---|---|---|---|
| Unit (networks, links, UTM, settings, buttons, styles, extras, follow, icons, count sources, recovery, Bitly, Novashare map and importer, the click guard, Mastodon host rules) | 275 | 968 | the package, no database, no network |
| Kit (enable, publish and draw, counting off by default and rate-limited, redirect, Mastodon leaving page and refusals, assets, unpublish, settings, all jobs, counts, Bitly, panel, preview, report, import) | 27 | 112 | inside Teme 1.0.0, PostgreSQL `sharely_test` |
| **Total** | **302, all passing** | **1,080** | |

Plus the browser checks above (axe and keyboard, Chromium), which were run
before these changes; the leaving page and script validation change were
checked by the kit and `node --check`, not by a new browser run. 53 PHP
source files, 21 test files, four scripts, five stylesheets.

## How to try it on teme-dev

Sharely is already in `TEME_PLUGIN_PATHS` in teme-dev's `.env` (the last
entry). teme-dev's `.env` pins `TEME_VERSION=0.8.0-alpha.1`, which Sharely
(`^1.0`) refuses, and Writura needs `^0.8`, so set the version for your own
session only:

1. Start the database: `docker compose -f ..\adplacr\dev\docker-compose.yml -p adplacr-dev up -d`
2. In PowerShell, in teme-dev:
   ```powershell
   $env:TEME_VERSION = '1.0.0'
   php artisan teme:plugins:enable krafty-sprouts/sharely --agree --as=admin@example.test
   ```
3. Serve the site in the same window (so it keeps the version):
   ```powershell
   cd public; php -S 127.0.0.1:8099 ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php
   ```
4. Sign in, open Tools › Sharely, choose networks and looks, and save. Press
   "Bring the article list up to date", then open a published post.
5. Try Site › Sharely for the report, and the Sharing tool in the editor.

Run the tests:

```powershell
cd C:\Users\kings\Downloads\Projects\teme-plugins\sharely
vendor\bin\pest
cd ..\teme-dev
$env:DB_DATABASE='sharely_test'; $env:TEME_VERSION='1.0.0'; php artisan test ..\sharely\tests\Kit
```

## Outside this folder

- teme-dev: `git pull` (to 4fbb9d48), `composer install`, `npm ci`,
  `npm run build`, `php artisan migrate`; Sharely's folder added to
  `TEME_PLUGIN_PATHS` in `.env` (not tracked). No Teme code changed.
- Docker: started the existing `adplacr-dev` stack; created the database
  `sharely_test`.
- Scratchpad only: the Novashare inventory, a demo page and router, and
  `axe-core` 4.10.2 installed there for the accessibility check.
- Novashare was only read, by a helper agent, for its behavior.
