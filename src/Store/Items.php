<?php

/**
 * The published items Sharely shares, kept in sharely_items as hooks and
 * the sync job tell of them, with every address each has had in
 * sharely_addresses, so counts follow an item to a new address.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Store;

use DateTimeImmutable;
use KraftySprouts\Sharely\Sharing\SharedItem;
use Teme\Contracts\Content\ItemRef;

/**
 * The published items Sharely shares, kept in sharely_items as hooks and the
 * sync job tell of them, with every address each has had in
 * sharely_addresses, so counts follow an item to a new address.
 *
 * @since 1.0.0
 */
final class Items
{
    /**
     * Records an item as published under its live address, keeping its old
     * addresses.
     *
     * @param  array{description?: string, image?: string|null}  $social
     *
     * @since 1.0.0
     */
    public function published(ItemRef $item, array $social = []): void
    {
        if ($item->url === null || $item->url === '') {
            $this->unpublished($item->id);

            return;
        }

        $now = new DateTimeImmutable;
        $values = [
            'type' => $item->type,
            'language' => $item->language,
            'title' => $item->title,
            'url' => $item->url,
            'published' => true,
            'updated_at' => $now,
        ];

        if (array_key_exists('description', $social)) {
            $values['description'] = (string) $social['description'];
        }

        if (array_key_exists('image', $social)) {
            $values['image'] = $social['image'];
        }

        Db::table('sharely_items')->updateOrInsert(['id' => $item->id], $values);
        Db::table('sharely_addresses')->insertOrIgnore([
            'item_id' => $item->id,
            'url' => $item->url,
            'first_seen' => $now,
        ]);
    }

    /**
     * Marks an item as no longer public: no buttons, no redirects. Its counts
     * and addresses stay, for when it comes back.
     *
     * @since 1.0.0
     */
    public function unpublished(string $id): void
    {
        Db::table('sharely_items')->where('id', $id)->update(['published' => false, 'updated_at' => new DateTimeImmutable]);
    }

    /**
     * Forgets an item that was deleted, with its addresses and counts.
     *
     * @since 1.0.0
     */
    public function deleted(string $id): void
    {
        foreach (['sharely_items' => 'id', 'sharely_addresses' => 'item_id', 'sharely_clicks' => 'item_id', 'sharely_counts' => 'item_id'] as $table => $column) {
            Db::table($table)->where($column, $id)->delete();
        }
    }

    /**
     * Saves the options set for one item in the editor.
     *
     * @since 1.0.0
     */
    public function options(string $id, bool $off, ?string $shareText, ?string $pinDescription, ?string $pinImage): void
    {
        $blank = static fn (?string $v): ?string => $v === null || trim($v) === '' ? null : trim($v);

        Db::table('sharely_items')->updateOrInsert(['id' => $id], [
            'off' => $off,
            'share_text' => $blank($shareText),
            'pin_description' => $blank($pinDescription),
            'pin_image' => $blank($pinImage),
            'updated_at' => new DateTimeImmutable,
        ]);
    }

    /**
     * Records the short address made for an item's address.
     *
     * @since 1.0.0
     */
    public function shortened(string $id, string $longUrl, string $shortUrl): void
    {
        Db::table('sharely_items')->where('id', $id)->update(['short_url' => $shortUrl, 'short_for' => $longUrl]);
    }

    /**
     * One item's row, or null.
     *
     * @since 1.0.0
     */
    public function row(string $id): ?object
    {
        return Db::table('sharely_items')->where('id', $id)->first();
    }

    /**
     * One published item as Sharely shares it, or null when it isn't
     * published or has buttons turned off.
     *
     * @since 1.0.0
     */
    public function shared(string $id): ?SharedItem
    {
        $row = $this->row($id);

        if ($row === null || ! $row->published || $row->off || $row->url === '') {
            return null;
        }

        return self::toShared($row);
    }

    /**
     * A stored row as the item Sharely shares; a short address counts only
     * while it was made for the item's current address.
     *
     * @since 1.0.0
     */
    public static function toShared(object $row): SharedItem
    {
        return new SharedItem(
            id: (string) $row->id,
            url: (string) $row->url,
            title: (string) $row->title,
            description: (string) $row->description,
            image: $row->image !== null ? (string) $row->image : null,
            pinImage: $row->pin_image !== null ? (string) $row->pin_image : null,
            pinDescription: $row->pin_description !== null ? (string) $row->pin_description : null,
            shareText: $row->share_text !== null ? (string) $row->share_text : null,
            shortUrl: $row->short_url !== null && $row->short_for === $row->url ? (string) $row->short_url : null,
        );
    }

    /**
     * Every address the item has had, its current one first.
     *
     * @return list<string>
     *
     * @since 1.0.0
     */
    public function addresses(string $id): array
    {
        $current = (string) ($this->row($id)->url ?? '');
        $all = Db::table('sharely_addresses')->where('item_id', $id)->orderBy('id')->pluck('url')->map(static fn ($u): string => (string) $u)->all();

        return array_values(array_unique(array_filter([$current, ...$all])));
    }

    /**
     * Published items whose counts were checked longest ago, at most $limit.
     *
     * @return list<object>
     *
     * @since 1.0.0
     */
    public function dueForCounts(int $limit): array
    {
        return Db::table('sharely_items')->where('published', true)
            ->orderByRaw('counts_checked_at asc nulls first')->orderBy('id')
            ->limit($limit)->get()->all();
    }

    /**
     * Published items whose short address is missing or was made for an
     * older address, at most $limit.
     *
     * @return list<object>
     *
     * @since 1.0.0
     */
    public function dueForShortening(int $limit): array
    {
        return Db::table('sharely_items')->where('published', true)
            ->where(static fn ($q) => $q->whereNull('short_for')->orWhereColumn('short_for', '!=', 'url'))
            ->orderBy('id')->limit($limit)->get()->all();
    }
}
