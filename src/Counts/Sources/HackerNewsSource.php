<?php

/**
 * Hacker News's count for an address: the points of the stories that link
 * to it, from Algolia's search, counting only stories whose address is the
 * same one leaving aside its scheme and a trailing slash.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Counts\Sources;

use KraftySprouts\Sharely\Counts\CountSource;
use Teme\Contracts\Http\OutboundHttp;

/**
 * Hacker News's count for an address: the points of the stories that link to
 * it, from Algolia's search, counting only stories whose address is the same
 * one leaving aside its scheme and a trailing slash.
 *
 * @since 1.0.0
 */
final readonly class HackerNewsSource implements CountSource
{
    /** The search address. @since 1.0.0 */
    public const string ENDPOINT = 'https://hn.algolia.com/api/v1/search';

    /** @since 1.0.0 */
    public function __construct(private OutboundHttp $http) {}

    /** @since 1.0.0 */
    public function network(): string
    {
        return 'hackernews';
    }

    /** @since 1.0.0 */
    public function host(): string
    {
        return 'hn.algolia.com';
    }

    /** @since 1.0.0 */
    public function count(string $url): ?int
    {
        $data = Fetch::object($this->http, self::ENDPOINT.'?'.Fetch::query([
            'restrictSearchableAttributes' => 'url',
            'hitsPerPage' => '50',
            'query' => $url,
        ]));
        $hits = $data['hits'] ?? null;

        if (! is_array($hits) || ! array_is_list($hits)) {
            return null;
        }

        $wanted = self::comparable($url);
        $sum = 0;

        foreach ($hits as $hit) {
            if (is_array($hit) && is_string($hit['url'] ?? null) && self::comparable($hit['url']) === $wanted) {
                $sum += Fetch::int($hit['points'] ?? null) ?? 0;
            }
        }

        return max(0, $sum);
    }

    /**
     * The address without its scheme and without one slash at the end of its
     * path (before any query or fragment).
     *
     * @since 1.0.0
     */
    private static function comparable(string $url): string
    {
        $bare = (string) preg_replace('#^https?://#i', '', trim($url));
        $cut = strcspn($bare, '?#');
        $path = substr($bare, 0, $cut);

        if (str_ends_with($path, '/')) {
            $path = substr($path, 0, -1);
        }

        return $path.substr($bare, $cut);
    }
}
