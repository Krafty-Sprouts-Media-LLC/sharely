<?php

/**
 * Reddit's count for an address: the scores of the posts that link to it
 * added together.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Counts\Sources;

use KraftySprouts\Sharely\Counts\CountSource;
use Teme\Contracts\Http\OutboundHttp;

/**
 * Reddit's count for an address: the scores of the posts that link to it
 * added together. Reddit asks for a descriptive User-Agent; this one names
 * the plugin and no person.
 *
 * @since 1.0.0
 */
final readonly class RedditSource implements CountSource
{
    /** The info address. @since 1.0.0 */
    public const string ENDPOINT = 'https://www.reddit.com/api/info.json';

    /** The User-Agent sent. @since 1.0.0 */
    public const string USER_AGENT = 'Sharely/1.0 (TemeCMS plugin)';

    /** @since 1.0.0 */
    public function __construct(private OutboundHttp $http) {}

    /** @since 1.0.0 */
    public function network(): string
    {
        return 'reddit';
    }

    /** @since 1.0.0 */
    public function host(): string
    {
        return 'www.reddit.com';
    }

    /** @since 1.0.0 */
    public function count(string $url): ?int
    {
        $data = Fetch::object($this->http, self::ENDPOINT.'?'.Fetch::query(['url' => $url]), ['User-Agent' => self::USER_AGENT]);
        $children = $data['data']['children'] ?? null;

        if (! is_array($children) || ! array_is_list($children)) {
            return null;
        }

        $sum = 0;

        foreach ($children as $child) {
            $sum += is_array($child) ? (Fetch::int($child['data']['score'] ?? null) ?? 0) : 0;
        }

        return max(0, $sum);
    }
}
