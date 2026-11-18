<?php

/**
 * The guard's keys, kept in the site's own cache: a key added for a few
 * minutes, and a random salt that lives a day.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Routes;

use Illuminate\Support\Facades\Cache;

/**
 * The guard's keys, kept in the site's own cache: a key added for a few
 * minutes, and a random salt that lives a day. The one class that reaches
 * the cache, as Store\Db is the one that reaches the database. Nothing here
 * is a table of Teme's, and nothing outlives its lifetime.
 *
 * @since 1.0.0
 */
final class SiteKeys implements Keys
{
    /** @since 1.0.0 */
    public function add(string $key, int $seconds): bool
    {
        return Cache::add($key, 1, $seconds);
    }

    /** @since 1.0.0 */
    public function salt(): string
    {
        return (string) Cache::remember('sharely:salt', 86400, static fn (): string => bin2hex(random_bytes(32)));
    }
}
