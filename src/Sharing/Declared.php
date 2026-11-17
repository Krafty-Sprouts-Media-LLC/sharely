<?php

/**
 * The settings Sharely declares in its manifest, read once, so defaults,
 * options and limits live in one place: teme-plugin.json.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Sharing;

use RuntimeException;

/**
 * The settings Sharely declares in its manifest, read once, so defaults,
 * options and limits live in one place: teme-plugin.json.
 *
 * @since 1.0.0
 */
final class Declared
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $settings = null;

    /**
     * Every declared setting by key.
     *
     * @return array<string, array<string, mixed>>
     *
     * @since 1.0.0
     */
    public static function settings(): array
    {
        if (self::$settings !== null) {
            return self::$settings;
        }

        $json = file_get_contents(dirname(__DIR__, 2).'/teme-plugin.json');
        $manifest = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($manifest)) {
            throw new RuntimeException("Sharely's manifest couldn't be read.");
        }

        $byKey = [];

        foreach ($manifest['contributes']['settings'] ?? [] as $setting) {
            $byKey[$setting['key']] = $setting;
        }

        return self::$settings = $byKey;
    }

    /**
     * One setting's declared default, or null.
     *
     * @since 1.0.0
     */
    public static function default(string $key): mixed
    {
        return self::settings()[$key]['default'] ?? null;
    }

    /**
     * One select's or multiselect's option values.
     *
     * @return list<string>
     *
     * @since 1.0.0
     */
    public static function options(string $key): array
    {
        return array_map('strval', array_keys(self::settings()[$key]['options'] ?? []));
    }

    /**
     * One number's declared least and greatest values.
     *
     * @return array{0: int, 1: int}
     *
     * @since 1.0.0
     */
    public static function range(string $key): array
    {
        $setting = self::settings()[$key] ?? [];

        return [(int) ($setting['min'] ?? PHP_INT_MIN), (int) ($setting['max'] ?? PHP_INT_MAX)];
    }
}
