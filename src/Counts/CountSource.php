<?php

/**
 * One network that can say how often an address was shared there.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Counts;

/**
 * One network that can say how often an address was shared there.
 *
 * @since 1.0.0
 */
interface CountSource
{
    /**
     * Sharely's id for the network, such as `facebook`.
     *
     * @since 1.0.0
     */
    public function network(): string;

    /**
     * The host the count is asked of, as the http capability must allow it.
     *
     * @since 1.0.0
     */
    public function host(): string;

    /**
     * The network's count for that address, never below 0, or null when the
     * network answered nothing usable. It never throws for a failed request
     * or an answer it cannot read.
     *
     * @since 1.0.0
     */
    public function count(string $url): ?int;
}
