<?php

/**
 * Picks an item's share title and description from Teme core's own fields,
 * in the order core's social card uses them: the social field, then the
 * search field, then the item's own.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely;

/**
 * Picks an item's share title and description from Teme core's own fields,
 * in the order core's social card uses them: the social field, then the
 * search field, then the item's own.
 *
 * @since 1.0.0
 */
final class Social
{
    /**
     * The share title: social title, else SEO title, else the title.
     *
     * @param  array<string, mixed>  $fields  by handle
     *
     * @since 1.0.0
     */
    public static function title(array $fields, string $title): string
    {
        return self::first($fields, ['social_title', 'seo_title']) ?? $title;
    }

    /**
     * The share description: social description, else meta description,
     * else the excerpt, else nothing.
     *
     * @param  array<string, mixed>  $fields  by handle
     *
     * @since 1.0.0
     */
    public static function description(array $fields): string
    {
        return self::first($fields, ['social_description', 'meta_description', 'excerpt']) ?? '';
    }

    /**
     * @since 1.0.0
     *
     * @param  array<string, mixed>  $fields
     * @param  list<string>  $handles
     */
    private static function first(array $fields, array $handles): ?string
    {
        foreach ($handles as $handle) {
            $value = $fields[$handle] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
