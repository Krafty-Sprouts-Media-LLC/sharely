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
