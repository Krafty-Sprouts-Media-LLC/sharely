# Decisions

Each decision made while building Sharely alone, and why.

## D-01 File headers and `@since` on everything

The brief said "no file headers" and `@since` only on what Sharely publishes,
as Teme's handbook recommends. The owner then asked, during the build, for
"proper file headers/comments and @since tags", as their other Teme plugins
(Writura, Securetor) have. The later instruction wins: every PHP file starts
with a header docblock (its purpose in one sentence, `@package
KraftySprouts\Sharely`, `@since 1.0.0`), and every class, method and constant
carries `@since 1.0.0`. `tools/add-file-headers.php` and `tools/add-since.php`
(from Writura) keep them complete. Teme neither reads nor checks `@since` in a
plugin's code (handbook, Hooks), so nothing else changes.

## D-02 Settings defaults live only in the manifest

`Sharing\Declared` reads `teme-plugin.json` once, and `Sharing\Config` takes
every default, option list and number range from it. Teme answers declared
defaults through `SettingsReader` anyway; reading them from the same file
keeps the unit tests and the site in step, with one place to change them.

## D-03 Share links are plain links; clicks counted by a redirect, not a script

Every share button that can be a link is one. When "Count share clicks" is
on (the default), its address is Sharely's own `/sharely/go/{network}/{item}`,
which adds one to that item's count for that network and day and sends the
reader on with a 302. This counts without JavaScript, cookies or anything
about the reader. Mail and text-message links go straight to `mailto:` and
`sms:` (a redirect to those is unreliable) and are not counted. The links
carry `rel="nofollow"` and the route answers `X-Robots-Tag: noindex`, so
crawlers neither follow nor list them.

## D-04 Per-network colours in the stylesheet, not in style attributes

Network colours are set per class in Sharely's `<style>` block
(`.sharely--brand .sharely__btn--x{…}`), never in `style=""` attributes, so a
page policy that refuses inline style attributes still draws them. The text
colour on each is chosen by WCAG contrast (`Networks\Contrast`): WhatsApp's
green gets black text, LinkedIn's blue white.

## D-05 Counts shown: the network's own when Sharely asks it, else Sharely's clicks

A button's number is the count the network reports, for the networks Sharely
is set to ask; for every other network it is Sharely's own click count. The
total is the sum of what the buttons show. Nothing shows below the minimum.
Novashare showed network counts only; with most networks no longer
publishing counts, Sharely's own clicks are the honest number left.

## D-06 Pocket left out

Pocket closed on 8 July 2025, so a Pocket button would lead nowhere. The
brief lists it; Sharely leaves it out of the networks and the importer drops
it with a note. Mix, Xing and Yummly (Novashare networks the brief didn't
ask for) are left out too.

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

The brief suggested hashes allowed through core's approved scripts. Public
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

teme-dev's `.env` pins Teme 0.8.0-alpha.1 for older plugins there, and
Sharely requires `^1.0` as the brief asks. Kit runs set `TEME_VERSION=1.0.0`
for that process only; `.env` is unchanged (FINDINGS H-02).

## D-14 Parts written by helper agents, checked here

The icons, the four scripts, the count and Bitly clients, and the Novashare
importer were written by helper agents working to written briefs, in
separate files, each test first; every one was read, run and wired in here.
One finding of an automated security review (the Facebook token in the
request address) was fixed, test first, before anything used it.
