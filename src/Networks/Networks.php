<?php

/**
 * Every network and utility Sharely knows, in the order the settings offer
 * them.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Networks;

/**
 * Every network and utility Sharely knows, in the order the settings offer
 * them. Brand colours are the networks' published ones.
 *
 * @since 1.0.0
 */
final class Networks
{
    /** @var array<string, Network>|null */
    private static ?array $all = null;

    /**
     * Every network by id.
     *
     * @return array<string, Network>
     *
     * @since 1.0.0
     */
    public static function all(): array
    {
        if (self::$all !== null) {
            return self::$all;
        }

        $list = [
            new Network('x', 'X', '#000000', highlight: true),
            new Network('facebook', 'Facebook', '#0866ff'),
            new Network('linkedin', 'LinkedIn', '#0a66c2', highlight: true),
            new Network('bluesky', 'Bluesky', '#1185fe', highlight: true),
            new Network('threads', 'Threads', '#000000', highlight: true),
            new Network('mastodon', 'Mastodon', '#6364ff', kind: Network::PAGE, highlight: true),
            new Network('whatsapp', 'WhatsApp', '#25d366', highlight: true),
            new Network('telegram', 'Telegram', '#26a5e4', highlight: true),
            new Network('reddit', 'Reddit', '#ff4500', highlight: true),
            new Network('pinterest', 'Pinterest', '#e60023'),
            new Network('flipboard', 'Flipboard', '#e12828'),
            new Network('tumblr', 'Tumblr', '#36465d'),
            new Network('buffer', 'Buffer', '#231f20', follow: false, highlight: true),
            new Network('line', 'LINE', '#06c755'),
            new Network('messenger', 'Messenger', '#0084ff', follow: false),
            new Network('vk', 'VK', '#0077ff'),
            new Network('hackernews', 'Hacker News', '#ff6600', follow: false, highlight: true),
            new Network('email', 'Email', '#4b5563', highlight: true, sameTab: true),
            new Network('sms', 'SMS', '#4b5563', follow: false, sameTab: true),
            new Network('print', 'Print', '#4b5563', kind: Network::SCRIPT, follow: false),
            new Network('copy', 'Copy link', '#4b5563', kind: Network::SCRIPT, follow: false, highlight: true),
            new Network('sheet', 'Share', '#4b5563', kind: Network::SCRIPT, follow: false),
            new Network('rss', 'RSS', '#f26522', share: false),
        ];

        $byId = [];

        foreach ($list as $network) {
            $byId[$network->id] = $network;
        }

        return self::$all = $byId;
    }

    /**
     * One network, or null for an id Sharely doesn't know.
     *
     * @since 1.0.0
     */
    public static function find(string $id): ?Network
    {
        return self::all()[$id] ?? null;
    }

    /**
     * The networks that can share an item.
     *
     * @return array<string, Network>
     *
     * @since 1.0.0
     */
    public static function sharing(): array
    {
        return array_filter(self::all(), static fn (Network $n): bool => $n->share);
    }

    /**
     * The networks a site can have a profile on, for follow buttons.
     *
     * @return array<string, Network>
     *
     * @since 1.0.0
     */
    public static function following(): array
    {
        return array_filter(self::all(), static fn (Network $n): bool => $n->follow);
    }

    /**
     * The networks highlight-to-share offers.
     *
     * @return array<string, Network>
     *
     * @since 1.0.0
     */
    public static function highlighting(): array
    {
        return array_filter(self::all(), static fn (Network $n): bool => $n->highlight);
    }

    /**
     * Only the known, sharing ids of a list, once each, in its order.
     *
     * @param  list<mixed>  $ids
     * @return list<string>
     *
     * @since 1.0.0
     */
    public static function clean(array $ids, bool $follow = false): array
    {
        $pool = $follow ? self::following() : self::sharing();
        $out = [];

        foreach ($ids as $id) {
            if (is_string($id) && isset($pool[$id]) && ! in_array($id, $out, true)) {
                $out[] = $id;
            }
        }

        return $out;
    }
}
