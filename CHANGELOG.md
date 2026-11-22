# Changelog

All notable changes to Sharely. Versions follow semantic versioning.

## 1.1.0 (2026-11-22)

Ready for Teme's directory, and for a site to upload as a zip.

### Added

- A zip a site can upload from Plugins › Add plugin, built with
  `php tools/package.php` (or `composer package`). It holds one top-level
  folder with only what a site runs, a `composer.json` that carries the
  version Teme's upload needs, and is checked against the rules Teme applies
  when it uploads and unpacks a package. The same source always makes the
  same bytes, so its checksum can be trusted.
- A release workflow: push a version tag and GitHub builds the zip, checks
  that the tag, `teme-plugin.json` and this changelog agree, and publishes a
  release with the zip, its checksum and these notes.
- A test workflow that runs the unit tests and builds the zip on every push.
  Both workflows pin every action to a commit.
- An icon and three screenshots (`art/`), named in the manifest for Teme's
  directory and the Plugins screen.
- Install steps in the README, and `RELEASING.md`: how a version becomes a
  zip, a GitHub release and a listing in Teme's directory.
- `tools/directory-check.php`, which runs the directory's own inspection on
  what a tag will be, before the tag is pushed.

### Changed

- Needs Teme 1.3.1 or later (was 1.0): from 1.3.1 Teme installs plugins that
  require `teme/contracts`, which is what the directory and the upload
  install.
- `teme/contracts` is taken from Packagist (`^1.3`), so the package installs
  and its unit tests run anywhere, with no Teme checkout beside it.
- The header tool no longer rewrites a file's `@since`, so each file keeps the
  version it arrived in.

## 1.0.0 (2026-11-17)

The first release.

- Share buttons in articles (above, below or both) on the content types you
  choose, as plain links, through Teme's article insertions.
- A floating share bar on the left, the right or the bottom, through Teme's
  fixed insertions.
- 22 networks and tools, in the order you choose, with icons drawn for
  Sharely, a More button, and a separate set for phones.
- Shapes, sizes, labels, network or accent or outlined colors and alignment,
  drawn with the theme's tokens.
- Share clicks counted on the site with no reader tracking, off until you
  turn it on, and limited to one counted click per article per address in
  ten minutes, kept as a one-way code in the site's cache for those ten
  minutes; counts from Facebook, Pinterest, Reddit and Hacker News fetched
  by a scheduled job; a minimum count; counts that follow an item's old
  addresses.
- Pinterest pin buttons on images and an image picker.
- Highlight to share by mouse, touch or keyboard, and share buttons on quotes.
- Mastodon sharing to the reader's own server, remembered on their device,
  with or without a script. Only a public host name is accepted (no address
  numbers, `localhost`, private or internal names, user names, ports or
  paths), and a page that names the server, with a Continue link, comes
  before any redirect; a click is counted only when the reader continues.
- Follow buttons for the site's profiles, email and feed.
- UTM tags and Bitly short links.
- A share report in Sharely's own area, and a Sharing panel in the editor
  that previews each network's card and holds the item's options.
- Import of Novashare's settings from a WordPress site.
