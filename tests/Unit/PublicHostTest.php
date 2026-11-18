<?php

/**
 * Tests for public host.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Routes\PublicHost;

it('accepts a public host name, lower-cased, with or without https:// and a lone trailing slash', function (string $typed, string $host): void {
    expect(PublicHost::from($typed))->toBe($host);
})->with([
    ['mastodon.social', 'mastodon.social'],
    ['  Fosstodon.ORG ', 'fosstodon.org'],
    ['https://hachyderm.io', 'hachyderm.io'],
    ['HTTPS://Mas.to/', 'mas.to'],
    ['http://social.example.co.uk', 'social.example.co.uk'],
    ['xn--bcher-kva.example.org', 'xn--bcher-kva.example.org'],
    ['a-b.c-d.social', 'a-b.c-d.social'],
]);

it('refuses IP addresses in every form', function (string $typed): void {
    expect(PublicHost::from($typed))->toBeNull();
})->with([
    '127.0.0.1', '10.0.0.5', '192.168.1.10', '169.254.169.254', '8.8.8.8',
    '[::1]', '::1', '[2001:db8::1]', '2130706433', '0x7f.0.0.1', '0177.0.0.1', '1.1', '127.1',
    'https://127.0.0.1', 'https://[::1]/',
]);

it('refuses localhost and names that are private, internal or reserved', function (string $typed): void {
    expect(PublicHost::from($typed))->toBeNull();
})->with([
    'localhost', 'LOCALHOST', 'app.localhost', 'printer.local', 'db.internal', 'nas.lan', 'router.home',
    'wiki.corp', 'box.intranet', 'host.localdomain', 'thing.private', 'foo.test', 'foo.example', 'foo.invalid',
    'abc.onion', '1.0.0.127.in-addr.arpa', 'intranet', 'nas',
    'foo.nip.io', 'foo.sslip.io', 'foo.localtest.me', 'foo.lvh.me', 'a.b.xip.io',
]);

it('refuses userinfo, ports, paths, queries and fragments', function (string $typed): void {
    expect(PublicHost::from($typed))->toBeNull();
})->with([
    'user@mastodon.social', 'https://user:pass@mastodon.social', '@me@mastodon.social',
    'mastodon.social:443', 'https://mastodon.social:8443', 'mastodon.social:80',
    'mastodon.social/share', 'https://mastodon.social/@me', 'mastodon.social/?x=1', 'mastodon.social#top',
    'mastodon.social?x=1', 'https://mastodon.social//', 'mastodon.social\\evil.example',
]);

it('refuses what is not a host name at all', function (string $typed): void {
    expect(PublicHost::from($typed))->toBeNull();
})->with([
    '', '   ', '.', 'a.', '.social', 'a..social', '-a.social', 'a-.social', 'a_b.social', 'a b.social',
    "a.social\n", "a.social\r\nHost: evil.example", 'javascript:alert(1)', 'ftp://mastodon.social',
    str_repeat('a', 64).'.social', 'bücher.example.org', 'mastodon.123',
    'https://', '//mastodon.social', 'mastodon.social@', "mastodon.social\0",
]);

it('refuses a name longer than a host name can be', function (): void {
    expect(PublicHost::from(str_repeat('abcdefghi.', 26).'social'))->toBeNull();
});
