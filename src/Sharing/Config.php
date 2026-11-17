<?php

/**
 * Sharely's settings as typed values, read from the saved group with each
 * missing or unknown value falling back to its declared default.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Sharing;

use KraftySprouts\Sharely\Links\Utm;
use KraftySprouts\Sharely\Networks\Networks;

/**
 * Sharely's settings as typed values, read from the saved group with each
 * missing or unknown value falling back to its declared default.
 *
 * @since 1.0.0
 */
final readonly class Config
{
    /**
     * @param  list<string>  $types
     * @param  list<string>  $networks
     * @param  list<string>  $phoneNetworks  empty when phones show the main set
     * @param  list<string>  $countNetworks
     * @param  list<string>  $highlightNetworks
     * @param  list<array{network: string, address: string}>  $follow
     *
     * @since 1.0.0
     */
    public function __construct(
        public string $inlinePosition,
        public array $types,
        public string $floating,
        public string $heading,
        public array $networks,
        public int $moreAfter,
        public array $phoneNetworks,
        public int $phoneWidth,
        public string $shape,
        public string $size,
        public string $labels,
        public string $colors,
        public string $align,
        public bool $countClicks,
        public bool $showCounts,
        public bool $showTotal,
        public int $minCount,
        public bool $fetchCounts,
        public array $countNetworks,
        public string $oldDomain,
        public bool $pinImages,
        public int $pinMinWidth,
        public string $pinCorner,
        public bool $pinPick,
        public bool $highlight,
        public array $highlightNetworks,
        public bool $quoteButtons,
        public array $follow,
        public string $followPlace,
        public string $followRegion,
        public string $followRegionWhere,
        public string $followHeading,
        public Utm $utm,
        public string $xAccount,
        public string $mastodonServer,
        public string $shortener,
    ) {}

    /**
     * Reads the saved settings group; anything missing takes its default.
     *
     * @param  array<string, mixed>  $saved
     *
     * @since 1.0.0
     */
    public static function fromSettings(array $saved): self
    {
        $r = new Values($saved);
        $phoneNetworks = $r->bool('phone_set') ? Networks::clean($r->items('phone_networks', 'network')) : [];

        return new self(
            inlinePosition: $r->choice('inline_position'),
            types: self::types($r->stringList('types'), $r->string('other_types')),
            floating: $r->choice('floating'),
            heading: $r->string('heading'),
            networks: Networks::clean($r->items('networks', 'network')),
            moreAfter: $r->int('more_after'),
            phoneNetworks: $phoneNetworks,
            phoneWidth: $r->int('phone_width'),
            shape: $r->choice('shape'),
            size: $r->choice('size'),
            labels: $r->choice('labels'),
            colors: $r->choice('colors'),
            align: $r->choice('align'),
            countClicks: $r->bool('count_clicks'),
            showCounts: $r->bool('show_counts'),
            showTotal: $r->bool('show_total'),
            minCount: $r->int('min_count'),
            fetchCounts: $r->bool('fetch_counts'),
            countNetworks: $r->choices('count_networks'),
            oldDomain: $r->string('old_domain'),
            pinImages: $r->bool('pin_images'),
            pinMinWidth: $r->int('pin_min_width'),
            pinCorner: $r->choice('pin_corner'),
            pinPick: $r->bool('pin_pick'),
            highlight: $r->bool('highlight'),
            highlightNetworks: array_values(array_intersect($r->choices('highlight_networks'), array_keys(Networks::highlighting()))),
            quoteButtons: $r->bool('quote_buttons'),
            follow: self::follow($r->rows('follow')),
            followPlace: $r->choice('follow_place'),
            followRegion: strtolower($r->string('follow_region')),
            followRegionWhere: $r->choice('follow_region_where'),
            followHeading: $r->string('follow_heading'),
            utm: new Utm($r->bool('utm'), $r->string('utm_source'), $r->string('utm_medium'), $r->string('utm_campaign')),
            xAccount: $r->string('x_account'),
            mastodonServer: $r->string('mastodon_server'),
            shortener: $r->choice('shortener'),
        );
    }

    /**
     * Whether items of this content type get share buttons.
     *
     * @since 1.0.0
     */
    public function sharesType(string $type): bool
    {
        return in_array($type, $this->types, true);
    }

    /**
     * Every network either set shows, once each.
     *
     * @return list<string>
     *
     * @since 1.0.0
     */
    public function allNetworks(): array
    {
        return array_values(array_unique([...$this->networks, ...$this->phoneNetworks]));
    }

    /**
     * @since 1.0.0
     *
     * @param  list<string>  $chosen
     * @return list<string>
     */
    private static function types(array $chosen, string $typed): array
    {
        $types = $chosen;

        foreach (explode(',', $typed) as $type) {
            $type = strtolower(trim($type));

            if (preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $type) === 1) {
                $types[] = $type;
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * @since 1.0.0
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{network: string, address: string}>
     */
    private static function follow(array $rows): array
    {
        $out = [];
        $known = Networks::following();

        foreach ($rows as $row) {
            $network = is_string($row['network'] ?? null) ? $row['network'] : '';
            $address = is_string($row['address'] ?? null) ? trim($row['address']) : '';

            if (! isset($known[$network]) || ($address === '' && $network !== 'rss')) {
                continue;
            }

            $out[] = ['network' => $network, 'address' => $address];
        }

        return $out;
    }
}
