<?php

/**
 * Writes a count the short way a share button has room for: 999, 1.5K,
 * 12K, 1.2M.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Render;

/**
 * Writes a count the short way a share button has room for: 999, 1.5K,
 * 12K, 1.2M.
 *
 * @since 1.0.0
 */
final class Number
{
    /**
     * The count in at most four characters.
     *
     * @since 1.0.0
     */
    public static function short(int $count): string
    {
        foreach ([1_000_000_000 => 'B', 1_000_000 => 'M', 1_000 => 'K'] as $size => $suffix) {
            if ($count >= $size) {
                $value = $count / $size;
                $text = $value >= 10 ? (string) (int) floor($value) : rtrim(rtrim(number_format(floor($value * 10) / 10, 1, '.', ''), '0'), '.');

                return __(':number'.$suffix, ['number' => $text]);
            }
        }

        return (string) max(0, $count);
    }
}
