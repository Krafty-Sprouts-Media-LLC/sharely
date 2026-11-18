<?php

/**
 * The short-lived keys the guard needs: add a key for a few minutes if it
 * isn't there, and a salt that changes every day.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Routes;

/**
 * The short-lived keys the guard needs: add a key for a few minutes if it
 * isn't there, and a salt that changes every day. Kept behind an interface
 * so the guard is tested without a cache, and so the one class that
 * reaches the site's cache is easy to find.
 *
 * @since 1.0.0
 */
interface Keys
{
    /**
     * Adds the key for that many seconds; false when it was already there.
     *
     * @since 1.0.0
     */
    public function add(string $key, int $seconds): bool;

    /**
     * A random value that stays the same for a day, then changes.
     *
     * @since 1.0.0
     */
    public function salt(): string;
}
