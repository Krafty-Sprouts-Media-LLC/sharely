# Report: Sharely 1.1.0

A build report: what works, what doesn't yet and why, how it was tested, and
how to try it. The 1.0.0 sections below were written against Teme `main` at
4fbb9d48 (contracts 1.0.0); the last section covers 1.1.0, which needs Teme
1.3.1 or later and was last tested on Teme 1.3.2. Where the 1.0.0 sections say
"Teme 1.0", read the 1.1.0 section: it replaces them.

## What works

Tested inside a real Teme (PostgreSQL) through Teme's testing kit, and in a
browser on a page drawn by Sharely's own code:

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
- A **theme stepping aside** for Sharely (W-08): turn the theme's own share
  buttons off by hand; the README says how for the Fable look.
- Follow buttons **inside** a region (W-09).
- Novashare's **per-post data and counts**, its button order, follow
  profiles and tokens can't be imported (W-10); they're listed to set by
  hand.
- The **editor preview** is a list of suggestions, not a picture (W-12).

## Not done or not checked

- **Real share counts and Bitly** were never called: tests use fakes, and no
  real token was used or stored anywhere.
- **Browsers**: Chromium only. Not Firefox, Safari, or a real touch device.
- **Test-first exceptions**: the button renderer's tests were written before
  the code but first run after it; everything else was seen failing first.
- **The 100 ms budget** (W-17): twice, on a loaded machine, the page's
  stylesheet and scripts were skipped for going over. Measured and fixed
  (stylesheets linked, not read from disk while a page draws): cold time of
  that piece from about 100 ms to 3 ms.
- **Translations**: `lang/en.json` lists the sentences; Teme doesn't load a
  plugin's `lang` folder yet, so they show in English.

## Changes after review

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
   and `lang/en.json`. `aria-labelledby` is the HTML attribute and is
   unchanged.

## Totals

| Suite | Tests | Assertions | Where |
|---|---|---|---|
| Unit (networks, links, UTM, settings, buttons, styles, extras, follow, icons, count sources, recovery, Bitly, Novashare map and importer, the click guard, Mastodon host rules; from 1.1.0 also the packager and its checks) | 275 at 1.0.0, 300 at 1.1.0 | 968 at 1.0.0, 1,238 at 1.1.0 | the package, no database, no network |
| Kit (enable, publish and draw, counting off by default and rate-limited, redirect, Mastodon leaving page and refusals, assets, unpublish, settings, all jobs, counts, Bitly, panel, preview, report, import) | 27 | 112 | inside Teme 1.0.0 at 1.0.0, Teme 1.3.2 at 1.1.0, PostgreSQL |
| **Total at 1.1.0** | **327, all passing** | **1,350** | |

The browser checks (axe and keyboard, Chromium) were run before the
changes in "Changes after review"; the leaving page and the script's
validation were checked by the kit and `node --check`, not by a new browser
run. 53 PHP source files, 21 test files, four scripts, five stylesheets.

## How to try it

Sharely needs a Teme development site that can load plugins from a folder
(see "Set up a development site" in Teme's developer handbook) and Teme 1.3.1
or later. If the site's `.env` pins an older `TEME_VERSION`, Sharely
(`^1.3.1`) is refused. Or skip the development site: upload the release's zip
from Plugins › Add plugin (see README, Install).

1. Add Sharely's folder to `TEME_PLUGIN_PATHS` in the site's `.env`, as an
   absolute path.
2. Enable it as an administrator:
   ```powershell
   php artisan teme:plugins:enable krafty-sprouts/sharely --agree --as=you@example.com
   ```
3. Serve the site in the same window, sign in, open Tools › Sharely, choose
   networks and looks, and save. Press "Bring the article list up to date",
   then open a published post. Site › Sharely shows the report; the editor
   has a Sharing tool.

Run the tests:

```powershell
composer install
vendor\bin\pest
```

The kit tests run inside the Teme checkout, on PostgreSQL, in a database of
their own:

```powershell
cd path\to\teme
$env:DB_DATABASE='sharely_test'
php artisan test path\to\sharely\tests\Kit
```

`teme/contracts` comes from Packagist (`^1.3`), so `composer install` needs no
Teme checkout beside this folder.

## 1.1.0: the zip, the release workflow and the directory

What was built, and how far it was checked. Details in RELEASING.md,
DECISIONS D-16 to D-20 and FINDINGS H-07 to H-09.

**Built**

- A packager (`composer package`) that makes `sharely-1.1.0.zip` (about 0.4
  MB) and its SHA-256, the same bytes every time, after checking the zip against
  Teme's upload and unpack rules.
- Two GitHub workflows, with every action pinned to a commit: CI (unit tests,
  scripts parse, zip builds) and Release (a version tag becomes a GitHub release
  with the zip, its checksum and the changelog's notes, after the tag, the
  manifest and the changelog are checked to agree).
- An icon and three screenshots, named in the manifest; install steps in the
  README; `RELEASING.md`; `tools/directory-check.php`.
- The manifest now needs Teme 1.3.1 or later, and `teme/contracts` comes from
  Packagist (`^1.3`).

**Checked**

| Check | Result |
|---|---|
| Unit tests (including 25 for the packager), from a fresh clone with only Packagist | 300 pass, 1,238 assertions |
| Kit tests inside Teme 1.3.2 on PostgreSQL | 27 pass, 112 assertions |
| Enabling on Teme 1.3.2 (manifest, `^1.3.1`, icon, screenshots, code scan) | passes |
| The directory's own release inspection, on a GitHub-style archive of the commit | all 11 checks pass |
| The directory's listing validator | valid |
| Teme 1.3.2's own `ArchiveReader` (the installer's unpacker) on the zip | unpacks 72 files; a second unpack accepted |
| The workflow files | parse; no unpinned action |
| CI on GitHub, after the push to `main` (November 22, 2026) | passed |
| Release on GitHub, after the tag `v1.1.0` | passed; the release holds `sharely-1.1.0.zip` and `sharely-1.1.0.zip.sha256` |

**Released and submitted**

- `v1.1.0` is tagged and released on GitHub.
- The listing's pull request is open: `temecms/directory` pull request 7. Its
  checks and review had not been seen when this was written.

**Not checked**

- The **Upload ZIP** tab in a running admin, and the installer service
  carrying it out: the rules and Teme's own unpacker were run on the zip, but
  not the whole path through a site.
- **The released zip's bytes against a local build.** Both workflows passed,
  but the downloaded zip's checksum hasn't been compared with a local build's.
- **The directory's install on a fresh Teme** and its review: they run on the
  open pull request, and their result hasn't been read.
- The kit tests aren't in CI (they need a Teme checkout and PostgreSQL).
- Teme 1.0.x to 1.3.0 are no longer supported by the manifest.
- The screenshots are Sharely's own renderer on a stand-in page, not Teme's
  admin screens.

**Tooling gotchas met on the way** (this machine, not Sharely): Teme's
`optimize-autoloader` made `composer install` and `dump-autoload` never
finish here, so the scratch Teme was set up without it; and Herd's installer
replacing its files for several minutes made PHP unusable until it finished.

**Outside this folder:** a scratch checkout of Teme 1.3.2 in
`teme-plugins/teme-latest` (own `.env`, database `sharely_latest`, optimized
autoload switched off there), used only to run the kit tests; `teme-dev` was
not touched. A copy of the directory repository and the screenshot demo
live in the session's scratch space only.
