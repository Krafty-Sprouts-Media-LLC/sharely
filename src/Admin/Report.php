<?php

/**
 * The share report in Sharely's own admin area: share clicks over the last
 * 30 days and all time, the counts networks report, clicks a day, and
 * which articles and networks are shared most.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Admin;

use DateTimeImmutable;
use KraftySprouts\Sharely\Networks\Networks;
use KraftySprouts\Sharely\Store\Db;
use Teme\Contracts\Admin\AreaContext;
use Teme\Contracts\Admin\Chart;
use Teme\Contracts\Admin\Figure;
use Teme\Contracts\Admin\PluginArea;
use Teme\Contracts\Admin\Row;
use Teme\Contracts\Admin\RowPage;
use Teme\Contracts\Admin\Saved;
use Teme\Contracts\Admin\TableQuery;
use Teme\Contracts\Content\WriteRefused;
use Teme\Contracts\Lists\Cell;

/**
 * The share report in Sharely's own admin area: share clicks over the last
 * 30 days and all time, the counts networks report, clicks a day, and which
 * articles and networks are shared most. Read only: its records can be
 * opened, never changed.
 *
 * @since 1.0.0
 */
final class Report implements PluginArea
{
    /** @since 1.0.0 */
    public function figures(string $page, string $block, AreaContext $context): array
    {
        $since = (new DateTimeImmutable('-29 days'))->format('Y-m-d');
        $before = (new DateTimeImmutable('-59 days'))->format('Y-m-d');
        $recent = (int) Db::table('sharely_clicks')->where('day', '>=', $since)->sum('clicks');
        $earlier = (int) Db::table('sharely_clicks')->where('day', '>=', $before)->where('day', '<', $since)->sum('clicks');
        $top = Db::table('sharely_clicks')->where('day', '>=', $since)
            ->selectRaw('network, sum(clicks) as n')->groupBy('network')->orderByDesc('n')->first();

        return [
            'clicks-30' => Figure::number($recent, $earlier > 0 ? round(($recent - $earlier) / $earlier, 4) : null),
            'clicks-all' => Figure::number((int) Db::table('sharely_clicks')->sum('clicks')),
            'network-counts' => Figure::number((int) Db::table('sharely_counts')->sum('count')),
            'top-network' => Figure::text($top !== null ? (Networks::find((string) $top->network)?->name ?? (string) $top->network) : __('None yet')),
        ];
    }

    /** @since 1.0.0 */
    public function chart(string $page, string $block, AreaContext $context): Chart
    {
        $byDay = [];

        foreach (Db::select('select day, sum(clicks) as n from sharely_clicks where day >= ? group by day', [(new DateTimeImmutable('-29 days'))->format('Y-m-d')]) as $row) {
            $byDay[substr((string) $row->day, 0, 10)] = (int) $row->n;
        }

        $labels = [];
        $values = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = (new DateTimeImmutable("-{$i} days"))->format('Y-m-d');
            $labels[] = $day;
            $values[] = $byDay[$day] ?? 0;
        }

        return new Chart($labels, ['clicks' => $values]);
    }

    /** @since 1.0.0 */
    public function rows(string $record, TableQuery $query, AreaContext $context): RowPage
    {
        return $record === 'networks' ? $this->networkRows($query) : $this->itemRows($query);
    }

    /** @since 1.0.0 */
    public function values(string $record, string $id, AreaContext $context): ?array
    {
        if ($record === 'networks') {
            $network = Networks::find($id);

            if ($network === null) {
                return null;
            }

            $top = Db::select(
                'select i.title, sum(c.clicks) as n from sharely_clicks c join sharely_items i on i.id = c.item_id where c.network = ? group by i.title order by n desc limit 10',
                [$id],
            );

            return [
                'network' => $network->name,
                'summary' => implode("\n", array_map(static fn (object $r): string => $r->n.' · '.$r->title, $top)),
            ];
        }

        $item = Db::table('sharely_items')->where('id', $id)->first();

        if ($item === null) {
            return null;
        }

        $lines = [];

        foreach (Db::select('select network, sum(clicks) as n from sharely_clicks where item_id = ? group by network order by n desc', [$id]) as $r) {
            $lines[] = __(':network: :count share clicks', ['network' => Networks::find((string) $r->network)?->name ?? $r->network, 'count' => $r->n]);
        }

        foreach (Db::table('sharely_counts')->where('item_id', $id)->get() as $r) {
            $lines[] = __(':network reports :count shares', ['network' => Networks::find((string) $r->network)?->name ?? $r->network, 'count' => $r->count]);
        }

        return [
            'title' => (string) $item->title,
            'url' => (string) $item->url,
            'summary' => $lines === [] ? __('No shares counted yet.') : implode("\n", $lines),
        ];
    }

    /** @since 1.0.0 */
    public function save(string $record, ?string $id, array $values, AreaContext $context): Saved
    {
        throw new WriteRefused(__('The share report can’t be changed: it counts what readers did.'));
    }

    /** @since 1.0.0 */
    public function act(string $record, string $action, array $ids, AreaContext $context): Saved
    {
        throw new WriteRefused(__('The share report can’t be changed: it counts what readers did.'));
    }

    /** @since 1.0.0 */
    private function itemRows(TableQuery $query): RowPage
    {
        $sort = match ($query->sort) {
            'counts' => 'counts',
            'title' => 'title',
            default => 'clicks',
        };
        $direction = $query->direction === 'asc' ? 'asc' : 'desc';
        $base = Db::table('sharely_items as i')
            ->leftJoinSub(Db::table('sharely_clicks')->selectRaw('item_id, sum(clicks) as clicks')->groupBy('item_id'), 'c', 'c.item_id', '=', 'i.id')
            ->leftJoinSub(Db::table('sharely_counts')->selectRaw('item_id, sum(count) as counts')->groupBy('item_id'), 'n', 'n.item_id', '=', 'i.id')
            ->where(static fn ($q) => $q->where('c.clicks', '>', 0)->orWhere('n.counts', '>', 0));

        if (trim($query->search) !== '') {
            $base->where('i.title', 'ilike', '%'.addcslashes(trim($query->search), '%_\\').'%');
        }

        $total = (clone $base)->count();
        $rows = $base->selectRaw('i.id, i.title, coalesce(c.clicks, 0) as clicks, coalesce(n.counts, 0) as counts')
            ->orderBy($sort, $direction)->orderBy('i.id')
            ->forPage(max(1, $query->page), max(1, min(100, $query->perPage)))->get();
        $out = [];

        foreach ($rows as $row) {
            $top = Db::table('sharely_clicks')->where('item_id', $row->id)->selectRaw('network, sum(clicks) as n')->groupBy('network')->orderByDesc('n')->first();
            $out[] = new Row((string) $row->id, [
                'title' => Cell::text((string) $row->title),
                'clicks' => Cell::number((int) $row->clicks),
                'counts' => Cell::number((int) $row->counts),
                'top' => Cell::badge($top !== null ? (Networks::find((string) $top->network)?->name ?? (string) $top->network) : '—', 'info'),
            ], ['duplicate', 'enable', 'disable', 'delete']);
        }

        return new RowPage($out, $total);
    }

    /** @since 1.0.0 */
    private function networkRows(TableQuery $query): RowPage
    {
        $since = (new DateTimeImmutable('-29 days'))->format('Y-m-d');
        $stats = [];

        foreach (Db::select('select network, sum(clicks) as all_time, sum(case when day >= ? then clicks else 0 end) as recent from sharely_clicks group by network', [$since]) as $r) {
            $stats[(string) $r->network] = ['clicks' => (int) $r->all_time, 'clicks-30' => (int) $r->recent, 'counts' => 0];
        }

        foreach (Db::select('select network, sum(count) as n from sharely_counts group by network') as $r) {
            $stats[(string) $r->network] = ($stats[(string) $r->network] ?? ['clicks' => 0, 'clicks-30' => 0]) + ['counts' => 0];
            $stats[(string) $r->network]['counts'] = (int) $r->n;
        }

        $sort = in_array($query->sort, ['clicks', 'clicks-30', 'counts'], true) ? $query->sort : 'clicks-30';
        uasort($stats, static fn (array $a, array $b): int => $query->direction === 'asc' ? $a[$sort] <=> $b[$sort] : $b[$sort] <=> $a[$sort]);
        $rows = [];

        foreach ($stats as $id => $s) {
            $rows[] = new Row($id, [
                'network' => Cell::text(Networks::find($id)?->name ?? $id),
                'clicks-30' => Cell::number($s['clicks-30']),
                'clicks' => Cell::number($s['clicks']),
                'counts' => Cell::number($s['counts']),
            ], ['duplicate', 'enable', 'disable', 'delete']);
        }

        $perPage = max(1, min(100, $query->perPage));

        return new RowPage(array_slice($rows, (max(1, $query->page) - 1) * $perPage, $perPage), count($rows));
    }
}
