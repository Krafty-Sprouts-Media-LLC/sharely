<?php

declare(strict_types=1);

namespace KraftySprouts\Sharely\Links;

/**
 * The UTM tags Sharely adds to a shared address, each a pattern in which
 * {network} becomes the network's id, so a site's analytics can tell which
 * network a visit came from.
 *
 * @since 1.0.0
 */
final readonly class Utm
{
    /** The placeholder a pattern may hold. @since 1.0.0 */
    public const string NETWORK = '{network}';

    /** @since 1.0.0 */
    public function __construct(
        public bool $enabled,
        public string $source,
        public string $medium,
        public string $campaign,
    ) {}

    /**
     * No tags at all.
     *
     * @since 1.0.0
     */
    public static function off(): self
    {
        return new self(false, '', '', '');
    }

    /**
     * The address with the tags added for that network, its own query and
     * fragment kept; an empty pattern adds no tag of its kind.
     *
     * @since 1.0.0
     */
    public function apply(string $url, string $network): string
    {
        if (! $this->enabled) {
            return $url;
        }

        $tags = array_filter([
            'utm_source' => $this->fill($this->source, $network),
            'utm_medium' => $this->fill($this->medium, $network),
            'utm_campaign' => $this->fill($this->campaign, $network),
        ], static fn (string $value): bool => $value !== '');

        if ($tags === []) {
            return $url;
        }

        $fragment = '';
        $hash = strpos($url, '#');

        if ($hash !== false) {
            $fragment = substr($url, $hash);
            $url = substr($url, 0, $hash);
        }

        $query = http_build_query($tags, '', '&', PHP_QUERY_RFC3986);

        return $url.(str_contains($url, '?') ? '&' : '?').$query.$fragment;
    }

    /** @since 1.0.0 */
    private function fill(string $pattern, string $network): string
    {
        return trim(str_replace(self::NETWORK, $network, $pattern));
    }
}
