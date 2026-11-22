# Releasing Sharely

How a version becomes a zip a site can upload, a GitHub release, and a listing
in Teme's directory. Everything here is one command or one pull request.

## Versions

Sharely follows semantic versioning. Three places must say the same version,
and the tools refuse a release where they don't:

- `version` in `teme-plugin.json`;
- the newest `## x.y.z (date)` heading in `CHANGELOG.md`;
- the git tag, `vx.y.z`.

`composer.json` has no `version`: Composer takes it from the tag. The zip's
own `composer.json` does carry one, which the packager adds, because Teme's
upload requires it.

`@since` in the code is the version a thing arrived in and never changes; code
added after a release carries the next version. See AGENTS.md.

There is no `v1.0.0` tag. 1.0.0 is the version the code was first pushed as,
before packaging existed, so the first release is 1.1.0.

## Before you tag

1. Add the version's section to `CHANGELOG.md` (it becomes the release notes)
   and set `version` in `teme-plugin.json`.
2. Run the tests:
   ```powershell
   composer test
   ```
   and the kit tests, inside the newest Teme release, on PostgreSQL (see
   AGENTS.md):
   ```powershell
   cd path\to\teme
   $env:DB_DATABASE='sharely_test'
   php artisan test path\to\sharely\tests\Kit
   ```
3. Build the zip and check it as Teme would:
   ```powershell
   composer package
   ```
   This writes `dist/sharely-<version>.zip` and `dist/sharely-<version>.zip.sha256`.
   Add `-- --tag=v<version>` to also check the tag against the manifest and the
   changelog.
4. Run the directory's own checks on what the tag will be (clone
   `https://github.com/temecms/directory` once and run `composer install` in it):
   ```powershell
   php tools/directory-check.php path\to\directory
   ```
5. Commit and push. The **CI** workflow runs the unit tests and builds the zip
   on every push to `main`; wait for it to pass.

## Tag

```powershell
git tag v1.1.0
git push origin v1.1.0
```

The **Release** workflow then runs the unit tests, checks that the tag, the
manifest and the changelog agree, builds and checks the zip, and publishes a
GitHub release with the zip, its checksum and the changelog's notes. A tag with
a hyphen, such as `v1.2.0-beta.1`, is marked a pre-release.

**Never move or delete a release's tag.** Teme's directory records each
release's checksum once and holds a release whose archive changes. If a
release is wrong, fix it in a new version.

## The zip

A site installs it from **Plugins › Add plugin › Upload ZIP** (see the README).
Teme reads only the zip's `composer.json` when it is uploaded, and unpacks it in
its installer service, so the zip has to follow Teme's rules. The packager
builds one that does, and `ArchiveCheck` applies the same rules before a zip is
kept:

- one top-level folder (`sharely/`) holding `composer.json` and
  `teme-plugin.json`;
- a `composer.json` with the package's name, `"type": "teme-plugin"` and a
  `"version"` equal to the manifest's;
- no path that leaves the folder, no symbolic link, no repeated name, no
  `vendor` folder, no Composer files loaded on every request, and no
  requirement on `teme/teme` (a site's own Teme provides it);
- under Teme's limits: 32 MB to upload by default, 20,000 files, 256 MB
  unpacked. The zip is about 0.4 MB.

It holds only what a site runs: `src`, `database`, `resources`, `lang`, `art`,
`teme-plugin.json`, `README.md`, `CHANGELOG.md`, `LICENSE`, and a trimmed
`composer.json`. Tests, tools, workflows and working notes stay out. Every
entry has a fixed order, time and mode, so the same source always makes the same
bytes, and the checksum can be trusted.

## Teme's directory

Teme's directory is free. A listed plugin appears on temecms.com and in every
Teme site's **Plugins › Add plugin › Directory**, and installs from there. The
directory holds only a small file that points at this repository; its rules and
checks are in [temecms/directory](https://github.com/temecms/directory).

### Before the first listing

- The repository is **public**.
- A release is tagged (see above): the directory reads releases from tags.
- `tools/directory-check.php` passes. It runs the directory's own inspection:
  `composer.json` (name, type, license), the manifest against Teme's schema,
  the Teme versions it claims, the capabilities it asks for, the icon and
  screenshots (PNG, JPEG or WebP under 2 MB each, from `art/`), and the README.

### List it

Open a pull request on `temecms/directory` that adds one file,
`listings/krafty-sprouts/sharely.json`, with the contents of
`tools/directory-listing.json`:

```json
{
    "repository": "https://github.com/Krafty-Sprouts-Media-LLC/sharely",
    "kind": "plugin",
    "categories": ["seo"],
    "picked": false
}
```

The Submit page on temecms.com opens the same pull request. The pull request
template asks for the package (`krafty-sprouts/sharely`), the repository, a
checklist (one file added, a public repository with a release tag, the
required files and a named license, fitting categories with `picked` false,
that you maintain the package, and that you have read the rules), and a
sentence on what it does.

The directory's checks comment on the pull request: the manifest, the README,
the license, the pictures, an install on a fresh Teme of each version
claimed (newest in each minor series) with the plugin enabled there, and the
plugin's own tests, which run from `composer test`. Fix what they report by
pushing to the pull request or tagging a new release. A maintainer reviews the
first listing; once the pull request is merged, the package is listed.

`picked` is for the Teme team's picks. Only a maintainer sets it.

### After it is listed

Tag a release and the directory picks it up on its own and runs the same
checks. A release that asks for **more** than the listed one (a new capability
or a new host to reach) waits for a maintainer before sites are offered it, as
does a release that fails a check. Keep `requires.teme`, the capabilities and
`hosts` in `teme-plugin.json` honest and no larger than needed.

To take Sharely out of the directory, open a pull request that deletes its
listing file.

## What the directory needs from every release

- `composer.json`: `name` equal to the listing's, `"type": "teme-plugin"`, a
  `license`, a `description`, `authors`; `require` `teme/contracts` (never
  `teme/teme`).
- `teme-plugin.json`: its `label` and `description` are the name and summary
  on the directory's page; `requires.teme` is `^1.3.1` because Teme installs
  plugins that require `teme/contracts` from 1.3.1, and the manifest's `icon`
  and `screenshots` are from 1.3.0; `capabilities` lists everything asked for.
- `README.md` at the root (its Overview), and a `CHANGELOG.md` with one heading
  per version and its date (its Changelog).
- `art/`: the icon and up to eight screenshots named in the manifest.
