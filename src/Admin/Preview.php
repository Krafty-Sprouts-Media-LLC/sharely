<?php

/**
 * Works out how an item will look when shared on each chosen network: the
 * title and description its card shows, cut where that network usually
 * cuts them, and what is missing.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Admin;

use KraftySprouts\Sharely\Networks\Networks;
use KraftySprouts\Sharely\Social;

/**
 * Works out how an item will look when shared on each chosen network: the
 * title and description its card shows, cut where that network usually cuts
 * them, and what is missing. The lengths are the networks' usual ones, not
 * promises: networks change them without notice.
 *
 * @since 1.0.0
 */
final class Preview
{
    /**
     * Roughly how many characters of title and description each network's
     * link card shows.
     *
     * @since 1.0.0
     */
    public const array LIMITS = [
        'x' => [70, 0],
        'facebook' => [88, 120],
        'linkedin' => [70, 0],
        'bluesky' => [100, 140],
        'threads' => [70, 0],
        'mastodon' => [80, 140],
        'whatsapp' => [65, 80],
        'telegram' => [80, 160],
        'reddit' => [300, 0],
        'pinterest' => [100, 500],
        'tumblr' => [80, 160],
        'flipboard' => [80, 160],
    ];

    /**
     * One line per network with a card: what it shows, and fixes worth
     * making, as [network, title shown, description shown, notes, fitted title].
     *
     * @param  array<string, mixed>  $fields  the draft's fields by handle
     * @param  list<string>  $networks
     * @return list<array{network: string, title: string, description: string, notes: list<string>, fitTitle: string|null}>
     *
     * @since 1.0.0
     */
    public static function lines(array $fields, string $title, array $networks): array
    {
        $shareTitle = Social::title($fields, $title);
        $description = Social::description($fields);
        $hasImage = ! empty($fields['social_image']) || ! empty($fields['featured_image']);
        $lines = [];

        foreach ($networks as $id) {
            if (! isset(self::LIMITS[$id]) || Networks::find($id) === null) {
                continue;
            }

            [$titleMax, $descMax] = self::LIMITS[$id];
            $notes = [];

            if (mb_strlen($shareTitle) > $titleMax) {
                $notes[] = __('The title is cut after about :n characters.', ['n' => $titleMax]);
            }

            if ($descMax > 0 && $description === '') {
                $notes[] = __('No description: the card shows none, or text the network picks.');
            }

            if (! $hasImage) {
                $notes[] = __('No social or featured image: the card may have no picture.');
            }

            $lines[] = [
                'network' => $id,
                'title' => self::cut($shareTitle, $titleMax),
                'description' => $descMax > 0 ? self::cut($description, $descMax) : '',
                'notes' => $notes,
                'fitTitle' => mb_strlen($shareTitle) > $titleMax ? self::fit($shareTitle, $titleMax) : null,
            ];
        }

        return $lines;
    }

    /**
     * The text as a card shows it, with an ellipsis where it is cut.
     *
     * @since 1.0.0
     */
    public static function cut(string $text, int $max): string
    {
        return mb_strlen($text) <= $max ? $text : rtrim(mb_substr($text, 0, $max - 1)).'…';
    }

    /**
     * The text shortened at a word to fit, with no ellipsis: a title that
     * reads whole.
     *
     * @since 1.0.0
     */
    public static function fit(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space !== false && $space > $max / 2 ? mb_substr($cut, 0, $space) : $cut, " ,;:–—-");
    }
}
