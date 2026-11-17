<?php

/**
 * Share clicks counted on the site, and the counts networks report.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Store;

use DateTimeImmutable;

/**
 * Share clicks counted on the site, one number per item, network and day in
 * sharely_clicks, and the counts networks report in sharely_counts. Nothing
 * about the reader is kept: no address, no cookie, no time of day.
 *
 * @since 1.0.0
 */
final class Counts
{
    /**
     * Adds one click for that item and network today.
     *
     * @since 1.0.0
     */
    public function click(string $itemId, string $network, ?DateTimeImmutable $day = null): void
    {
        Db::statement(
            'insert into sharely_clicks (item_id, network, day, clicks) values (?, ?, ?, 1)
             on conflict (item_id, network, day) do update set clicks = sharely_clicks.clicks + 1',
            [$itemId, $network, ($day ?? new DateTimeImmutable)->format('Y-m-d')],
        );
    }

    /**
     * Records what a network reported for an item. A count never goes down
     * by itself: a smaller answer, as a network gives when it forgets old
     * shares, keeps the larger number.
     *
     * @since 1.0.0
     */
    public function reported(string $itemId, string $network, int $count): void
    {
        Db::statement(
            'insert into sharely_counts (item_id, network, count, checked_at) values (?, ?, ?, ?)
             on conflict (item_id, network) do update set count = greatest(sharely_counts.count, excluded.count), checked_at = excluded.checked_at',
            [$itemId, $network, max(0, $count), new DateTimeImmutable],
        );
    }

    /**
     * Marks when an item's counts were last asked for, answered or not.
     *
     * @since 1.0.0
     */
    public function checked(string $itemId): void
    {
        Db::table('sharely_items')->where('id', $itemId)->update(['counts_checked_at' => new DateTimeImmutable]);
    }

    /**
     * The counts a button shows for one item: the network's own count for
     * the networks asked, Sharely's clicks for the rest.
     *
     * @param  list<string>  $asked  the networks whose own counts Sharely asks for
     * @return array<string, int>
     *
     * @since 1.0.0
     */
    public function forItem(string $itemId, array $asked): array
    {
        $shown = [];

        foreach (Db::select('select network, sum(clicks) as n from sharely_clicks where item_id = ? group by network', [$itemId]) as $row) {
            $shown[(string) $row->network] = (int) $row->n;
        }

        foreach (Db::table('sharely_counts')->where('item_id', $itemId)->get() as $row) {
            if (in_array($row->network, $asked, true)) {
                $shown[(string) $row->network] = (int) $row->count;
            }
        }

        return $shown;
    }
}
