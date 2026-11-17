<?php

/**
 * Answers what a run would: the people and items it brought, by WordPress
 * ID, and whether it is a catch-up.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Tests\Support;

use Teme\Contracts\Content\ItemRef;
use Teme\Contracts\Import\ImportScope;

/**
 * Answers what a run would: the people and items it brought, by
 * WordPress ID, and whether it is a catch-up.
 *
 * @since 1.0.0
 */
final class FakeImportScope implements ImportScope
{
    /**
     * @param  array<int, string>  $people  Teme person IDs by WordPress user ID
     * @param  array<int, ItemRef>  $items  items by WordPress post ID
     *
     * @since 1.0.0
     */
    public function __construct(
        private readonly array $people = [],
        private readonly array $items = [],
        private readonly bool $catchUp = false,
    ) {}

    /** @since 1.0.0 */
    public function itemFor(int $sourcePostId): ?ItemRef
    {
        return $this->items[$sourcePostId] ?? null;
    }

    /** @since 1.0.0 */
    public function personFor(int $sourceUserId): ?string
    {
        return $this->people[$sourceUserId] ?? null;
    }

    /** @since 1.0.0 */
    public function isCatchUp(): bool
    {
        return $this->catchUp;
    }
}
