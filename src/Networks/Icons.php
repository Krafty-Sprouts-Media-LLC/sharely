<?php

/**
 * Answers one small inline SVG per network or interface glyph.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

/**
 * The inline SVG icons Sharely draws on its buttons, one per network or
 * utility, each drawn fresh on a 24-unit grid as a simple geometric mark.
 *
 * @package KraftySprouts\Sharely
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Networks;

/**
 * Answers one small inline SVG per network or interface glyph. The marks are
 * simplified shapes drawn for Sharely, not copies of any brand's artwork;
 * they take their colour from the text around them (currentColor).
 *
 * @since 1.0.0
 */
final class Icons
{
    /**
     * The opening tag every icon shares: decorative, hidden from screen
     * readers, never a tab stop.
     *
     * @since 1.0.0
     */
    private const string OPEN = '<svg class="sharely-icon" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true" focusable="false">';

    /**
     * The id drawn for any id Sharely has no mark for.
     *
     * @since 1.0.0
     */
    private const string FALLBACK = 'share';

    /** @var array<string, string>|null */
    private static ?array $marks = null;

    /**
     * The icon for a network or glyph id; the share glyph for an unknown id.
     *
     * @since 1.0.0
     */
    public static function svg(string $id): string
    {
        $marks = self::marks();

        return self::OPEN.($marks[$id] ?? $marks[self::FALLBACK]).'</svg>';
    }

    /**
     * The inner markup of every icon, by id.
     *
     * @return array<string, string>
     *
     * @since 1.0.0
     */
    private static function marks(): array
    {
        if (self::$marks !== null) {
            return self::$marks;
        }

        $round = 'M5 2h14a3 3 0 0 1 3 3v14a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V5a3 3 0 0 1 3-3z';

        return self::$marks = [
            // Two crossing strokes, one broad and one fine, the fine one broken where they meet.
            'x' => self::fill('M3 3h5.4l12.6 18h-5.4z')
                .self::fill('M18.8 3h2.1l-6.5 7.4-1.2-1.7zM10.7 13.5l1.2 1.7L5.2 21H3.1z'),
            // A rounded square with a lower-case f cut out of it.
            'facebook' => self::fill($round.'M11 22V9a4 4 0 0 1 4-4h2.5v3H15a1 1 0 0 0-1 1v13zM8.5 11H11v3H8.5zM14 11h3.5v3H14z', true),
            // A rounded square with "in" cut out of it.
            'linkedin' => self::fill($round.self::circle(7.5, 7.2, 1.7).'M6 10h3v8H6zM10.5 10h2.8v1.3c.6-1 1.7-1.5 2.9-1.5 2 0 3.3 1.3 3.3 3.8V18h-3v-4c0-1-.5-1.6-1.4-1.6s-1.6.6-1.6 1.6v4h-3z', true),
            // A butterfly of two pairs of rounded wings.
            'bluesky' => self::fill('M12 10.5C10.3 7.2 6.8 4 4.2 4 2.8 4 2.5 5 2.5 6.5c0 2.2.8 5.2 3.3 5.6 2 .3 4.4-.3 6.2-1.6z'
                .'M12 10.5C13.7 7.2 17.2 4 19.8 4c1.4 0 1.7 1 1.7 2.5 0 2.2-.8 5.2-3.3 5.6-2 .3-4.4-.3-6.2-1.6z'
                .'M12 12.2c-2.6 0-6.4.6-6.4 3.3 0 2 2 4.5 3.8 4.5 1.5 0 2.3-2.6 2.6-4.6z'
                .'M12 12.2c2.6 0 6.4.6 6.4 3.3 0 2-2 4.5-3.8 4.5-1.5 0-2.3-2.6-2.6-4.6z'),
            // An @-like spiral.
            'threads' => self::line('M15 9v4.5c0 1.5 1 2.5 2.3 2.5 1.6 0 2.7-1.6 2.7-4a8 8 0 1 0-3.8 6.8M15 12a3 3 0 1 1-6 0 3 3 0 1 1 6 0'),
            // A speech bubble with an m cut out of it.
            'mastodon' => self::fill('M7 2.5h10A4 4 0 0 1 21 6.5v6a4 4 0 0 1-4 4h-5c-1.5 0-2.5.8-2.5 2.2 0 1.3 1.3 1.8 3 1.8h3.5v1.5H12c-3.5 0-5.6-1.5-5.6-4.2A4 4 0 0 1 3 13.9V6.5a4 4 0 0 1 4-4z'
                .'M7 14v-4a3 3 0 0 1 5-2.2A3 3 0 0 1 17 10v4h-2v-4a1 1 0 0 0-2 0v4h-2v-4a1 1 0 0 0-2 0v4z', true),
            // A phone handset inside a round speech bubble.
            'whatsapp' => self::line('M3.5 20.5l1.2-4.2A8.5 8.5 0 1 1 8 19.6z')
                .self::fill('M9 7.5c.5 0 .8.2 1 .7l.7 1.7c.1.4 0 .7-.2 1l-.6.7c.6 1.2 1.6 2.2 2.8 2.8l.7-.6c.3-.3.6-.3 1-.2l1.7.7c.5.2.7.5.7 1v.8c0 .9-.8 1.6-1.7 1.5C11 17 7 13 6.6 9.2c-.1-.9.6-1.7 1.5-1.7z'),
            // A paper plane.
            'telegram' => self::fill('M2.5 11.2l18-7c.8-.3 1.6.4 1.4 1.3l-3 14.2c-.2.9-1.2 1.2-1.9.7l-4.6-3.4-2.3 2.2c-.3.3-.8.1-.8-.3V15l7.5-7-9.4 5.7-4.7-1.5c-.9-.3-.9-1.5 0-1.8z'),
            // A round face with eyes, a smile and an antenna.
            'reddit' => self::fill('M3.5 14a8.5 6 0 1 0 17 0 8.5 6 0 1 0-17 0z'.self::circle(8.5, 13.3, 1.4).self::circle(15.5, 13.3, 1.4)
                .'M8.6 16.4c2 1.3 4.8 1.3 6.8 0l.5.8c-2.3 1.6-5.5 1.6-7.8 0z', true)
                .self::fill('M11.5 8.1l1.2-5.6 4.3.9-.2 1-3.3-.7-1 4.4z'.self::circle(18.2, 4.3, 1.7)),
            // A circle with a P cut out of it.
            'pinterest' => self::fill(self::circle(12, 12, 10).'M9 6.5h2.3V19H9zM11.3 6.5h2.2a4 4 0 0 1 0 8h-2.2v-2.2h2.2a1.8 1.8 0 0 0 0-3.6h-2.2z', true),
            // An F built from stepped squares.
            'flipboard' => self::fill('M3 3h18v6h-6v6H9v6H3z'),
            // A lower-case t.
            'tumblr' => self::fill('M10 3h3v4h4v3h-4v5.5c0 1.2.6 1.8 1.8 1.8H17V21h-3c-2.6 0-4-1.5-4-4.2V10H7.5V7.2C9 6.8 10 5 10 3z'),
            // Three stacked layers.
            'buffer' => self::fill('M12 3l9 4.2-9 4.2-9-4.2zM5.2 10.6l6.8 3.2 6.8-3.2 2.2 1.1-9 4.2-9-4.2zM5.2 14.9l6.8 3.2 6.8-3.2L21 16l-9 4.2L3 16z'),
            // A wide speech bubble with LINE spelled in bars.
            'line' => self::fill('M12 3c5 0 9 3.2 9 7.3 0 2.2-1.1 4-3 5.6-2 1.7-5 3.6-6.3 4.2-.5.2-.6-.1-.5-.6l.2-1.3c0-.3 0-.6-.4-.7C6.3 17.2 3 14.1 3 10.3 3 6.2 7 3 12 3z'
                .'M6 8.3h1.2v3H9v1H6zM9.8 8.3H11v4H9.8zM11.8 8.3H13l1.8 2.4V8.3H16v4h-1.2L13 9.9v2.4h-1.2zM16.8 8.3h2.7v1H18v.5h1.5v1H18v.5h1.5v1h-2.7z', true),
            // A round speech bubble with a zigzag through it.
            'messenger' => self::fill('M12 2.5c5.4 0 9.5 3.9 9.5 9s-4.1 9-9.5 9c-1 0-1.9-.1-2.8-.4L6 21.5v-3.2C4 16.6 2.5 14.2 2.5 11.5c0-5.1 4.1-9 9.5-9z'
                .'M6.5 14.5l3.8-5 2.6 2 4.6-2-3.8 5-2.6-2z', true),
            // The letters V and K drawn as strokes.
            'vk' => self::line('M2.5 7l3.3 10L9 7M12.5 7v10M20.5 7l-7 5 7 5', 2.4),
            // A square with a Y cut out of it.
            'hackernews' => self::fill('M3 3h18v18H3zM7.5 6.5h2.3l2.2 4.2 2.2-4.2h2.3l-3.3 6.3v4.7h-2.4v-4.7z', true),
            // An envelope.
            'email' => self::line('M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zM3.5 7.5L12 13l8.5-5.5'),
            // A speech bubble with three dots.
            'sms' => self::fill('M5 4h14a2.5 2.5 0 0 1 2.5 2.5v9A2.5 2.5 0 0 1 19 18h-8l-5 3.5V18H5a2.5 2.5 0 0 1-2.5-2.5v-9A2.5 2.5 0 0 1 5 4z'
                .self::circle(8, 11, 1.3).self::circle(12, 11, 1.3).self::circle(16, 11, 1.3), true),
            // A printer with a sheet in and a sheet out.
            'print' => self::line('M6 9V3h12v6M6 18H4.5A1.5 1.5 0 0 1 3 16.5V11a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5.5a1.5 1.5 0 0 1-1.5 1.5H18M6 14h12v7H6z'),
            // Two overlapping rectangles.
            'copy' => self::line('M10 9h9a1.5 1.5 0 0 1 1.5 1.5v9A1.5 1.5 0 0 1 19 21h-9a1.5 1.5 0 0 1-1.5-1.5v-9A1.5 1.5 0 0 1 10 9zM5 15h-.5A1.5 1.5 0 0 1 3 13.5v-9A1.5 1.5 0 0 1 4.5 3h9A1.5 1.5 0 0 1 15 4.5V5'),
            // An open box with an arrow rising out of it.
            'sheet' => self::line('M8 10H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-2M12 15V3M8 7l4-4 4 4'),
            // A dot and two arcs.
            'rss' => self::fill(self::circle(5.5, 18.5, 2.2)).self::line('M4 11a9 9 0 0 1 9 9M4 4a16 16 0 0 1 16 16', 2.6),
            // Three dots in a row.
            'more' => self::fill(self::circle(5, 12, 2).self::circle(12, 12, 2).self::circle(19, 12, 2)),
            // Three joined nodes.
            'share' => self::line('M6 12l12-7M6 12l12 7').self::fill(self::circle(18, 5, 2.8).self::circle(6, 12, 2.8).self::circle(18, 19, 2.8)),
            // An even cross.
            'close' => self::line('M6 6l12 12M18 6L6 18', 2.2),
            // A push pin.
            'pin' => self::fill('M8.5 3h7v1.5l-1.5 1V10l3 3v1.5H7V13l3-3V5.5l-1.5-1zM11.2 14.5h1.6l-.8 6.5z'),
            // A tick.
            'check' => self::line('M4.5 12.5l5 5 10-11', 2.5),
        ];
    }

    /**
     * A filled path, cutting holes where shapes overlap when asked.
     *
     * @since 1.0.0
     */
    private static function fill(string $d, bool $holes = false): string
    {
        return '<path'.($holes ? ' fill-rule="evenodd"' : '').' d="'.$d.'"/>';
    }

    /**
     * A stroked path with round ends and corners.
     *
     * @since 1.0.0
     */
    private static function line(string $d, float $width = 2.0): string
    {
        return '<path fill="none" stroke="currentColor" stroke-width="'.$width.'" stroke-linecap="round" stroke-linejoin="round" d="'.$d.'"/>';
    }

    /**
     * A circle as path data, drawn as two half arcs.
     *
     * @since 1.0.0
     */
    private static function circle(float $cx, float $cy, float $r): string
    {
        return 'M'.round($cx - $r, 2).' '.$cy.'a'.$r.' '.$r.' 0 1 0 '.round(2 * $r, 2).' 0a'.$r.' '.$r.' 0 1 0 '.round(-2 * $r, 2).' 0z';
    }
}
