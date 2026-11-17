<?php

/**
 * Builds the count sources an administrator chose, in the order chosen,
 * leaving out ids no source answers for, repeats, and Facebook when there
 * is no token for it.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Counts;

use KraftySprouts\Sharely\Counts\Sources\FacebookSource;
use KraftySprouts\Sharely\Counts\Sources\HackerNewsSource;
use KraftySprouts\Sharely\Counts\Sources\PinterestSource;
use KraftySprouts\Sharely\Counts\Sources\RedditSource;
use SensitiveParameter;
use Teme\Contracts\Http\OutboundHttp;

/**
 * Builds the count sources an administrator chose, in the order chosen,
 * leaving out ids no source answers for, repeats, and Facebook when there is
 * no token for it.
 *
 * @since 1.0.0
 */
final class Sources
{
    /** The networks that have a count source. @since 1.0.0 */
    public const array NETWORKS = ['facebook', 'pinterest', 'reddit', 'hackernews'];

    /**
     * The sources for those networks.
     *
     * @param  list<string>  $networks
     * @return list<CountSource>
     *
     * @since 1.0.0
     */
    public static function for(array $networks, OutboundHttp $http, #[SensitiveParameter] ?string $facebookToken): array
    {
        $token = trim((string) $facebookToken);
        $sources = [];

        foreach ($networks as $network) {
            if (isset($sources[$network])) {
                continue;
            }

            $source = match ($network) {
                'facebook' => $token === '' ? null : new FacebookSource($http, $token),
                'pinterest' => new PinterestSource($http),
                'reddit' => new RedditSource($http),
                'hackernews' => new HackerNewsSource($http),
                default => null,
            };

            if ($source !== null) {
                $sources[$network] = $source;
            }
        }

        return array_values($sources);
    }
}
