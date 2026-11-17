<?php

/**
 * Every address one item may have been shared under, so the networks can
 * be asked about each and the counts recovered after a move: the current
 * address, the older ones Sharely recorded, the http/https twin of each,
 * and each of those on the site's old domain.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Counts;

/**
 * Every address one item may have been shared under, so the networks can be
 * asked about each and the counts recovered after a move: the current
 * address, the older ones Sharely recorded, the http/https twin of each, and
 * each of those on the site's old domain.
 *
 * @since 1.0.0
 */
final class Recovery
{
    /** The most addresses asked about for one item. @since 1.0.0 */
    public const int LIMIT = 12;

    /**
     * The addresses to ask about, unique, the current one first, at most
     * LIMIT: the current and recorded addresses, then their twins, then all
     * of those with the old domain's scheme, host and port in place of their
     * own.
     *
     * @param  list<string>  $known
     * @return list<string>
     *
     * @since 1.0.0
     */
    public static function addresses(string $current, array $known, string $oldDomain): array
    {
        $own = self::unique([$current, ...$known]);
        $twins = array_values(array_filter(array_map(self::twin(...), $own)));
        $all = self::unique([...$own, ...$twins]);
        $origin = self::origin($oldDomain);

        if ($origin !== null) {
            $all = self::unique([...$all, ...array_values(array_filter(array_map(
                static fn (string $url): ?string => self::rehome($url, $origin),
                $all,
            )))]);
        }

        return array_slice($all, 0, self::LIMIT);
    }

    /**
     * The addresses trimmed, blanks dropped, each kept once in first order.
     *
     * @param  list<string>  $urls
     * @return list<string>
     *
     * @since 1.0.0
     */
    private static function unique(array $urls): array
    {
        $kept = [];

        foreach ($urls as $url) {
            $url = trim($url);

            if ($url !== '' && ! in_array($url, $kept, true)) {
                $kept[] = $url;
            }
        }

        return $kept;
    }

    /**
     * The same address over the other of http and https, or null when it is
     * neither.
     *
     * @since 1.0.0
     */
    private static function twin(string $url): ?string
    {
        if (preg_match('#^https://#i', $url) === 1) {
            return 'http://'.substr($url, 8);
        }

        if (preg_match('#^http://#i', $url) === 1) {
            return 'https://'.substr($url, 7);
        }

        return null;
    }

    /**
     * The scheme, host and port of an http(s) domain such as
     * `https://old.example.com`, or null when it is not one.
     *
     * @since 1.0.0
     */
    private static function origin(string $domain): ?string
    {
        $parts = parse_url(trim($domain));

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host']) || $parts['host'] === '') {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        return $scheme.'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    /**
     * The address with its scheme, host and port replaced by that origin, its
     * path, query and fragment kept; null when it is not an http(s) address.
     *
     * @since 1.0.0
     */
    private static function rehome(string $url, string $origin): ?string
    {
        if (preg_match('#^https?://[^/?\#]*(.*)$#is', $url, $match) !== 1) {
            return null;
        }

        $rest = $match[1];

        return $origin.($rest === '' || $rest[0] !== '/' ? '/'.$rest : $rest);
    }
}
