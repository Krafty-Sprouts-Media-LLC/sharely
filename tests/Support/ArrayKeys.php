<?php

/**
 * An in-memory stand-in for the short-lived cache the guard uses: keeps
 * what was added and the lifetimes asked for, and can pretend to be down.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Tests\Support;

use KraftySprouts\Sharely\Routes\Keys;
use RuntimeException;

/**
 * An in-memory stand-in for the short-lived cache the guard uses: keeps
 * what was added and the lifetimes asked for, and can pretend to be down.
 *
 * @since 1.0.0
 */
final class ArrayKeys implements Keys
{
    /** @var array<string, true> */
    public array $seen = [];

    /** @var list<int> */
    public array $ttls = [];

    /** @since 1.0.0 */
    public function __construct(private readonly string $salt = 'test-salt', private readonly bool $broken = false) {}

    /** @since 1.0.0 */
    public function add(string $key, int $seconds): bool
    {
        $this->down();

        if (isset($this->seen[$key])) {
            return false;
        }

        $this->seen[$key] = true;
        $this->ttls[] = $seconds;

        return true;
    }

    /** @since 1.0.0 */
    public function salt(): string
    {
        $this->down();

        return $this->salt;
    }

    private function down(): void
    {
        if ($this->broken) {
            throw new RuntimeException('The cache is down.');
        }
    }
}
