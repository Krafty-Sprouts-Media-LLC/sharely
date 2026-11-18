<?php

/**
 * Checks the server name a reader may be sent to to share on Mastodon: a
 * public host name and nothing else.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Routes;

/**
 * Checks the server name a reader may be sent to to share on Mastodon: a
 * public host name and nothing else. No IP address in any form, no
 * localhost, no name under a private, internal or reserved ending, no
 * well-known service that points any name at an address, and no userinfo,
 * port, path, query or fragment. A leading https:// or http:// and one
 * trailing slash are taken off, since people paste addresses. The name is
 * not looked up: it says what the name is, not where it points.
 *
 * @since 1.0.0
 */
final class PublicHost
{
    /**
     * Endings no public server has: local networks, private use, reserved
     * names, and the reverse-lookup tree.
     *
     * @since 1.0.0
     */
    public const array REFUSED_ENDINGS = [
        'localhost', 'local', 'localdomain', 'internal', 'intranet', 'lan', 'home', 'corp', 'private',
        'test', 'example', 'invalid', 'onion', 'arpa', 'home.arpa',
    ];

    /**
     * Services that answer any name under them with an address of the
     * caller's choosing, often 127.0.0.1 or a private one.
     *
     * @since 1.0.0
     */
    public const array REFUSED_SERVICES = ['nip.io', 'sslip.io', 'xip.io', 'localtest.me', 'lvh.me', 'traefik.me', 'localhost.run'];

    /**
     * The host name a person typed, lower-cased, or null when it isn't a
     * public one.
     *
     * @since 1.0.0
     */
    public static function from(string $typed): ?string
    {
        // Only spaces and tabs are trimmed: trim()'s default would also take off a newline or
        // a NUL byte, which would let a name with one pass as if it were clean.
        $host = strtolower(trim($typed, " \t"));
        $host = (string) preg_replace('#^https?://#', '', $host);

        if (str_ends_with($host, '/') && ! str_ends_with($host, '//')) {
            $host = substr($host, 0, -1);
        }

        if ($host === '' || strlen($host) > 253) {
            return null;
        }

        // Letters, digits, hyphens and dots only: this also leaves out userinfo, ports, paths,
        // queries, fragments, brackets (IPv6), spaces, control characters and non-ASCII.
        if (preg_match('/^[a-z0-9.-]+$/D', $host) !== 1) {
            return null;
        }

        $labels = explode('.', $host);

        if (count($labels) < 2) {
            return null;
        }

        foreach ($labels as $label) {
            if (preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $label) !== 1) {
                return null;
            }
        }

        // The last label must be letters (or punycode): that rules out every number form of an IP address.
        $tld = end($labels);

        if (preg_match('/^(?:[a-z]{2,63}|xn--[a-z0-9-]{1,59})$/D', $tld) !== 1) {
            return null;
        }

        foreach (self::REFUSED_ENDINGS as $ending) {
            if ($host === $ending || str_ends_with($host, '.'.$ending)) {
                return null;
            }
        }

        foreach (self::REFUSED_SERVICES as $service) {
            if ($host === $service || str_ends_with($host, '.'.$service)) {
                return null;
            }
        }

        return $host;
    }
}
