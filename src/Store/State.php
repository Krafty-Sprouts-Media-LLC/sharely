<?php

/**
 * A few named values Sharely keeps, as JSON.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Store;

use DateTimeImmutable;

/**
 * A few named values Sharely keeps in sharely_state as JSON, such as the
 * copy of its settings its public routes read: Teme builds a route handler
 * with none of the plugin's doors, so it can't read the settings itself.
 *
 * @since 1.0.0
 */
final class State
{
    /** The settings the public routes need, copied when they change. @since 1.0.0 */
    public const string SETTINGS = 'settings';

    /**
     * Stores a value.
     *
     * @since 1.0.0
     */
    public function put(string $key, mixed $value): void
    {
        Db::table('sharely_state')->updateOrInsert(['key' => $key], [
            'value' => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_at' => new DateTimeImmutable,
        ]);
    }

    /**
     * A stored value, or the fallback.
     *
     * @since 1.0.0
     */
    public function get(string $key, mixed $fallback = null): mixed
    {
        $value = Db::table('sharely_state')->where('key', $key)->value('value');

        if (! is_string($value)) {
            return $fallback;
        }

        $decoded = json_decode($value, true);

        return $decoded ?? $fallback;
    }
}
