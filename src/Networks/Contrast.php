<?php

/**
 * Works out how well two colours read against each other, as WCAG measures
 * it, so a button's text is always the colour that reads best on it.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Networks;

/**
 * Works out how well two colours read against each other, as WCAG measures
 * it, so a button's text is always the colour that reads best on it.
 *
 * @since 1.0.0
 */
final class Contrast
{
    /**
     * White or black, whichever reads better on the colour.
     *
     * @since 1.0.0
     */
    public static function on(string $hex): string
    {
        return self::ratio('#ffffff', $hex) >= self::ratio('#000000', $hex) ? '#ffffff' : '#000000';
    }

    /**
     * The contrast ratio of two #rrggbb colours, from 1 to 21.
     *
     * @since 1.0.0
     */
    public static function ratio(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** @since 1.0.0 */
    private static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $sum = 0.0;

        foreach ([0 => 0.2126, 2 => 0.7152, 4 => 0.0722] as $at => $weight) {
            $c = hexdec(substr($hex, $at, 2)) / 255;
            $sum += $weight * ($c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4);
        }

        return $sum;
    }
}
