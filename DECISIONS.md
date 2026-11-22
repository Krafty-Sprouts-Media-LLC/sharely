# Decisions

Each decision made while building Sharely alone, and why.

## D-01 File headers and `@since` on everything

The original plan said no file headers and `@since` only on what Sharely
publishes, as Teme's handbook recommends. During the build the project asked
for proper file headers, comments and `@since` tags, as its other Teme
plugins have. The later decision wins: every PHP file starts
with a header docblock (its purpose in one sentence, `@package
KraftySprouts\Sharely`, `@since 1.0.0`), and every class, method and constant
carries `@since 1.0.0`. `tools/add-file-headers.php` and `tools/add-since.php`
keep them complete. Teme neither reads nor checks `@since` in a
plugin's code (handbook, Hooks), so nothing else changes.

## D-02 Settings defaults live only in the manifest

`Sharing\Declared` reads `teme-plugin.json` once, and `Sharing\Config` takes
every default, option list and number range from it. Teme answers declared
defaults through `SettingsReader` anyway; reading them from the same file
keeps the unit tests and the site in step, with one place to change them.

## D-03 Share links are plain links; clicks counted by a redirect, not a script

Every share button that can be a link is one. When "Count share clicks" is
on, its address is Sharely's own `/sharely/go/{network}/{item}`, which adds
one to that item's count for that network and day and sends the reader on
with a 302. This counts without JavaScript or cookies. Mail and text-message
links go straight to `mailto:` and `sms:` (a redirect to those is
unreliable) and are not counted. The links carry `rel="nofollow"` and the
route answers `X-Robots-Tag: noindex`, so crawlers neither follow nor list
them.

**Changed after review:**

- **Off by default.** Counting is a setting the administrator turns on; with
  it off the buttons link straight to the networks, and the redirect still
  works (an old link keeps working) but counts nothing. The share works
  either way.
- **Rate limited.** One counted click per article per address in ten
  minutes (`Routes\Guard`), so repeated requests can't inflate a count. The
  address is never stored in a table. A key is made as an HMAC-SHA256 of
  the article and the address under a random salt that turns over daily, kept
  in the site's own cache (`Cache::add`, ten minutes), so it can't be turned
  back into an address and is gone in ten minutes. This is the one place
  Sharely holds anything derived from an address, and the README says so
  instead of "never keeps an address".
- **Fails closed.** When the cache is down nothing is counted; the share
  still works.
- **One cache class.** `Routes\SiteKeys` is the only class that reaches the
  cache, behind a two-method interface (`Routes\Keys`) so the guard is tested
  without one, as `Store\Db` is for the database.
- **Known limit.** The window is per article and address, as asked, not per
  network: a reader who shares one article to two networks within ten
  minutes counts once. A reader behind a shared address (an office, a mobile
  carrier's NAT) counts once per article per ten minutes for all of them;
  and anyone with many addresses can still add one click per address. This
  slows inflation, it doesn't make a count tamper-proof, and the report
  says "share clicks", not "shares".

## D-04 Per-network colors in the stylesheet, not in style attributes

Network colors are set per class in Sharely's `<style>` block
(`.sharely--brand .sharely__btn--x{…}`), never in `style=""` attributes, so a
page policy that refuses inline style attributes still draws them. The text
color on each is chosen by WCAG contrast (`Networks\Contrast`): WhatsApp's
green gets black text, LinkedIn's blue white.

## D-05 Counts shown: the network's own when Sharely asks it, else Sharely's clicks

A button's number is the count the network reports, for the networks Sharely
is set to ask; for every other network it is Sharely's own click count. The
total is the sum of what the buttons show. Nothing shows below the minimum.
Novashare showed network counts only; with most networks no longer
publishing counts, Sharely's own clicks are the honest number left.

## D-06 Pocket left out

Pocket closed on July 8, 2025, so a Pocket button would lead nowhere. The
original list of networks included it; Sharely leaves it out of the
networks and the importer drops it with a note. Mix, Xing and Yummly
(Novashare networks that weren't on that list) are left out too.

## D-07 No phone-only floating bar

A setting "Floating bar along the bottom on phones" was written and removed
before release: core draws a fixed insertion as its own aside, on every
screen, and a plugin can't hide it on wide ones (FINDINGS W-05). A setting
that can't do what it says is worse than none.

## D-08 Novashare's settings are applied by a person, after the import

Teme's import gives the importer no person to save settings for (FINDINGS
W-10). The importer maps everything and keeps it in `sharely_state`; the
action "Apply the Novashare settings" (settings page and the report's
Novashare import page) runs a job as the person who presses it, which saves
what a plugin may write. The network lists, follow profiles and tokens,
which no plugin may write, are listed on that page to set by hand. This
keeps the rule that a plugin acts only for the person who started the work.

## D-09 Short links replace UTM tags

One short link per item, of its plain address. When a short link exists,
every network gets it and no UTM tags are added: a short link already counts
its own clicks, and one per network would multiply the calls to the service.

## D-10 Scripts from Sharely's own route, not approved inline scripts

The original plan suggested hashes allowed through core's approved scripts. Public
pages' policy has `script-src 'self'` (checked in a kit test), so Sharely
serves its four scripts from `/sharely/assets/` and adds a `<script src>`
only on pages that use each. No person has to approve anything, the
scripts can be cached, and an update to a script needs no new approval.

## D-11 Counts are asked of four networks, all off by default

Facebook (needs the site's own app token), Pinterest and Reddit (addresses
they don't promise to keep, named as such in the setting), and Hacker News
(Algolia's public API). Buffer, Tumblr and VK no longer answer; X and
Bluesky publish none for a link. Asking is off until the administrator turns
it on, as the rules require for any call out of the site.

## D-12 Sharely keeps its own list of items

`ArticleFacts` has no title (FINDINGS W-13), so Sharely records each
published item's title, type, language and address in `sharely_items` (from
`ContentPublished` and an hourly sync), with every address it has had in
`sharely_addresses`. This also gives the click redirect a fixed list of
items it may send people to, so it can't be used as an open redirect.

## D-13 Tested against Teme 1.0.0 with the version set per run

A Teme development checkout may pin an older `TEME_VERSION` in its `.env`
for other plugins, and Sharely requires `^1.0`. Kit runs set
`TEME_VERSION=1.0.0` for that process only and leave `.env` unchanged
(FINDINGS H-02).

## D-14 Parts developed separately, reviewed before use

The icons, the four scripts, the count and Bitly clients, and the Novashare
importer were developed separately from the core, to written briefs, in
their own files, each test first; every one was read, run and wired in
before it was used. One finding of an automated security review (the
Facebook token in the request address) was fixed, test first, before
anything used it.

## D-15 Mastodon: a named, signed leaving page, never an open redirect

The first version redirected at once to `https://{typed server}/share`,
which made `/sharely/mastodon/{item}?server=…` an open redirect to any
host. After review:

- **Only a public host name** (`Routes\PublicHost`, mirrored in
  `mastodon.js`): letters, digits, hyphens and dots; at least two labels;
  a last label of letters (or punycode), which rules out every number form
  of an IP address; no IP literal in any form, no `localhost`, no name
  under a private, internal or reserved ending (`local`, `internal`, `lan`,
  `home`, `corp`, `test`, `example`, `invalid`, `onion`, `arpa`, and
  more), none of the services that point any name at an address
  (`nip.io`, `sslip.io`, `localtest.me`, and more); no userinfo, port,
  path, query or fragment. A leading `https://` or `http://` and one
  trailing slash are taken off, since people paste addresses. Only spaces
  and tabs are trimmed: PHP's `trim()` also strips newlines and NUL bytes,
  which let a name carrying one pass (found by the first test run).
  The handle form `@me@server` the first script accepted is refused too: it
  is userinfo.
- **A page first.** A valid server gets "You’re leaving for {server} to
  share this post" with the post's words, a Continue link and a Cancel link.
  Nothing is counted and no redirect is sent.
- **Continue is signed.** Its link carries a token, an HMAC of the article
  and the server under the day's salt (`Routes\Guard`), so a crafted
  `?server=…&go=…` link can't skip the page; a wrong or missing token shows
  the page again. Only then is the click counted (when counting is on and
  the guard allows it) and the redirect sent.
- **Not looked up.** The name's DNS isn't resolved: this says what the name
  is, not where it points, and a name that points at a private address is
  still the reader's own choice in their own browser, on a page that named
  it. The known wildcard services are refused by name.
- **The script's dialog** sends the reader from their own browser to the
  server they typed, with the same checks; that is not a redirect from the
  site.
