# AGENTS.md: Sharely for TemeCMS

Sharely draws share buttons in articles (above, below or both) and a floating
share bar, follow buttons for the site's own profiles, highlight-to-share and
quote buttons, Pinterest pin buttons on images, and a share report in its own
admin area. It counts share clicks on the site itself, with no cookies and no
addresses kept, and, only when the administrator turns it on, asks Facebook,
Pinterest, Reddit and Hacker News for their counts in a scheduled job. It
replaces the WordPress plugin Novashare on Teme, written fresh: no code,
markup, CSS, icons or wording come from Novashare. `FEATURES.md` maps every
Novashare feature to what Sharely does; `FINDINGS.md` lists where Teme's
handbook or sockets fell short; `DECISIONS.md` says why things are as they are.

## Manifest

`teme-plugin.json` is the source of truth. Capabilities: `content.read`,
`content.insert`, `pages.insert`, `jobs`, `http`, `routes.public`,
`import.wordpress`. Hooks: `ContentPublished`, `ContentUnpublished`,
`ContentTrashed`, `ContentDeleted`, `SettingsChanged`. Tables, all `sharely_`:
`items`, `addresses`, `clicks`, `counts`, `state`.

## Run the tests

```powershell
composer install
vendor\bin\pest                      # unit tests, no database, no network
```

The kit tests run inside the teme-dev checkout, on PostgreSQL, in a database
of their own:

```powershell
cd ..\teme-dev
$env:DB_DATABASE='sharely_test'; php artisan test ..\sharely\tests\Kit
```

Write the failing test first, watch it fail for the right reason, then write
the code. Tests never reach the network: unit tests use the fakes in
`tests/Support/`, kit tests use the kit's `outbound()`.

## Rules this package keeps

- Code only against `teme/contracts`. The one class that touches Laravel's
  query builder is `src/Store/Db.php`, on Sharely's own tables only.
- Never read a setting while Teme starts the plugin (constructor,
  `register()`, `jobs()`): read them when the work happens.
- Every word a person reads goes through `__()`, with the American English
  sentence as its key. `lang/en.json` lists them (`php tools/lang.php`).
- Secrets only as `secret` settings read with `SecretReader`; never in a file,
  a test, a fixture, a log or an error message. Tests use made-up strings.
- No reader tracking: no cookies, no addresses, no user agents stored. The
  click counter (off by default) keeps item, network, day and a number. The
  only thing derived from an address is a one-way key in the site's cache for
  ten minutes (`Routes\Guard`), so repeated requests can't inflate a count.
- Mastodon: only a public host name is accepted (`Routes\PublicHost`), a
  signed "You’re leaving for…" page comes before any redirect, and a click
  is counted only on Continue.
- No JavaScript where none is needed: every share button is a plain link.
  Scripts (in `resources/js/`) are only for highlight-to-share, the Mastodon
  server picker, copy/print/share-sheet and pin buttons, each loaded only on
  pages that use it, served from Sharely's own `/sharely/assets/` route.
- Accessibility: every control has a name ("Share on Bluesky (opens in a new
  tab)"), visible focus, works by keyboard, respects reduced motion.
- `declare(strict_types=1);` in every PHP file; a file header docblock (one
  sentence on what the file is for, `@package KraftySprouts\Sharely`,
  `@since <version>`) before it; a docblock on every class, method and
  constant with `@since <version>`.
- **`@since` is the version a thing arrived in, and never changes.** The code
  that was pushed as 1.0.0 is `@since 1.0.0`; what is added after a release
  carries the version it will ship in (the one in `teme-plugin.json`, the top
  of `CHANGELOG.md`), such as `1.1.0`. Changing an existing method keeps its
  tag; adding a method or a file takes the current version. Run
  `php tools/add-file-headers.php <current version> "KraftySprouts\Sharely" src database tests`
  and `php tools/add-since.php <current version> src database` with the
  version being built: they only fill in what is missing and never rewrite a
  tag that is there.
- CSS classes and JavaScript globals start with `sharely`.

## Layout

```
src/Sharely.php       the plugin class: doors, hooks, insertions, jobs, area, panel
src/Networks/         the networks, their icons, color contrast
src/Links/            share addresses, UTM tags, short links
src/Sharing/          the item being shared and the settings it is drawn with
src/Render/           the HTML and CSS of buttons, bars and follow buttons
src/Counts/           click counts and the networks' own counts
src/Store/            Sharely's own tables
src/Routes/           public routes: click redirect, Mastodon picker, assets
src/Import/           Novashare's settings from a WordPress import
src/Admin/            the share report and the editor panel
resources/js/         the few scripts, plain ES2020, no dependencies
database/migrations/  Sharely's tables
```

Start with Teme's handbook: `docs/guides/developers/README.md` in teme-dev,
then "Your first plugin", "Hooks", "Content inside articles" and
"Content on every page".
