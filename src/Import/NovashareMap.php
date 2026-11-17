<?php

/**
 * Turns the decoded `novashare` option and the follow widget's option into
 * values for Sharely's declared settings, each valid for its type, options
 * and limits, and says in sentences what Sharely can't bring over.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Import;

/**
 * Turns the decoded `novashare` option and the follow widget's option into
 * values for Sharely's declared settings, each valid for its type, options
 * and limits, and says in sentences what Sharely can't bring over. Pure:
 * it reads nothing and writes nothing, and never returns a secret's value.
 *
 * @since 1.0.0
 */
final class NovashareMap
{
    /**
     * Novashare's network keys that Sharely spells otherwise.
     *
     * @since 1.0.0
     */
    private const array RENAMED = ['twitter' => 'x', 'vkontakte' => 'vk'];

    /**
     * The networks Sharely's share buttons offer.
     *
     * @since 1.0.0
     */
    private const array SHARE = [
        'x', 'facebook', 'linkedin', 'bluesky', 'threads', 'mastodon', 'whatsapp', 'telegram',
        'reddit', 'pinterest', 'flipboard', 'tumblr', 'buffer', 'line', 'messenger', 'vk',
        'hackernews', 'email', 'sms', 'print', 'copy', 'sheet',
    ];

    /**
     * The networks Sharely's highlight bar offers.
     *
     * @since 1.0.0
     */
    private const array HIGHLIGHT = [
        'x', 'bluesky', 'threads', 'mastodon', 'linkedin', 'whatsapp', 'telegram', 'reddit',
        'buffer', 'hackernews', 'email', 'copy',
    ];

    /**
     * The networks Sharely's follow buttons offer.
     *
     * @since 1.0.0
     */
    private const array FOLLOW = [
        'x', 'facebook', 'linkedin', 'bluesky', 'threads', 'mastodon', 'whatsapp', 'telegram',
        'reddit', 'pinterest', 'flipboard', 'tumblr', 'line', 'vk', 'email', 'rss',
    ];

    /**
     * Networks whose counts Sharely can ask for, besides Facebook.
     *
     * @since 1.0.0
     */
    private const array COUNTED = ['pinterest', 'reddit'];

    /**
     * Novashare's corners for pin buttons, as Sharely's; centre has none.
     *
     * @since 1.0.0
     */
    private const array CORNERS = [
        '' => 'top-start', 'topright' => 'top-end', 'bottomleft' => 'bottom-start', 'bottomright' => 'bottom-end',
    ];

    /**
     * Display names for Novashare networks Sharely lacks.
     *
     * @since 1.0.0
     */
    private const array NAMES = [
        'pocket' => 'Pocket', 'mix' => 'Mix', 'xing' => 'Xing', 'yummly' => 'Yummly',
        'subscribe' => 'Subscribe', 'share' => 'Share (the share window)', 'instagram' => 'Instagram',
        'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'github' => 'GitHub', 'phone' => 'Phone',
        'lastfm' => 'Last.fm', 'stackoverflow' => 'Stack Overflow', 'wordpress' => 'WordPress',
        'applemusic' => 'Apple Music', 'soundcloud' => 'SoundCloud', 'codepen' => 'CodePen',
        'deviantart' => 'DeviantArt', 'jsfiddle' => 'JSFiddle',
    ];

    /**
     * Sharely's setting values by key, from the decoded `novashare` option.
     * A key is left out when Novashare had nothing Sharely can use for it,
     * so Sharely's default stands.
     *
     * @param  array<string, mixed>  $novashare
     * @return array<string, mixed>
     *
     * @since 1.0.0
     */
    public static function settings(array $novashare): array
    {
        $inline = self::group($novashare, 'inline');
        $floating = self::group($novashare, 'floating');
        $highlight = self::group($novashare, 'highlight');
        $pinterest = self::group($novashare, 'pinterest');
        $values = [];

        $values['inline_position'] = self::on($inline, 'enabled')
            ? match (self::text($inline, 'position')) {
                'below' => 'below',
                'both' => 'both',
                'neither' => 'none',
                default => 'above',
            }
            : 'none';

        $types = self::keys($inline['post_types'] ?? null);
        if ($types !== []) {
            $values['types'] = array_values(array_intersect($types, ['post', 'page']));
            $other = array_values(array_filter(
                array_diff($types, ['post', 'page']),
                static fn (string $type): bool => preg_match('/^[a-z0-9_-]{1,64}$/', $type) === 1,
            ));
            $values['other_types'] = self::fit(implode(', ', $other), 300, ', ');
        }

        $values['floating'] = self::on($floating, 'enabled') && self::text($floating, 'position') !== 'none'
            ? (self::text($floating, 'alignment') === 'right' ? 'right' : 'left')
            : 'none';

        $cta = self::text($inline, 'cta_text');
        if ($cta !== '') {
            $values['heading'] = mb_substr($cta, 0, 80);
        }

        $networks = self::active($novashare);
        if ($networks !== []) {
            $values['networks'] = array_map(
                static fn (string $network): array => ['network' => $network],
                array_slice($networks, 0, 24),
            );
        }

        $values['size'] = match (self::text($inline, 'size')) {
            'small' => 'small',
            'large' => 'large',
            default => 'medium',
        };
        $values['shape'] = match (self::text($inline, 'shape')) {
            'rounded' => 'rounded',
            'circular' => 'pill',
            default => 'square',
        };
        $values['labels'] = self::on($inline, 'labels') ? 'labels' : 'icons';
        $values['colors'] = 'brand';
        $values['align'] = preg_match('/^[1-6]-col$/', self::text($inline, 'layout')) === 1
            ? 'fill'
            : match (self::text($inline, 'alignment')) {
                'center' => 'center',
                'right' => 'end',
                default => 'start',
            };

        $total = self::on($inline, 'total_share_count') || self::on($floating, 'total_share_count');
        $each = self::on($inline, 'network_share_counts') || self::on($floating, 'network_share_counts');
        $values['show_counts'] = $total || $each;
        $values['show_total'] = $total;

        $minimum = $novashare['minimum_share_count'] ?? null;
        if (is_numeric($minimum)) {
            $values['min_count'] = max(0, min(100000, (int) $minimum));
        }

        $values['counts_every'] = self::text($novashare, 'refresh_rate') === '' || self::text($novashare, 'refresh_rate') === 'high'
            ? 'hour'
            : 'day';

        if (self::hasFacebookApp($novashare)) {
            $values['fetch_counts'] = true;
            $values['count_networks'] = ['facebook', ...array_values(array_intersect(self::COUNTED, $networks))];
        }

        $domain = self::text($novashare, 'previous_domain');
        if (self::on($novashare, 'recover_previous_domain') && $domain !== '') {
            $address = preg_match('#^https?://#i', $domain) === 1 ? $domain : 'https://'.$domain;
            if (mb_strlen($address) <= 200) {
                $values['old_domain'] = rtrim($address, '/');
            }
        }

        $values['pin_images'] = self::on($pinterest, 'image_pins');
        $corner = self::CORNERS[self::text($pinterest, 'pin_button_position')] ?? null;
        if ($corner !== null) {
            $values['pin_corner'] = $corner;
        }
        $values['pin_pick'] = self::text($pinterest, 'share_button_behavior') === 'grid';

        $values['highlight'] = self::on($highlight, 'enabled');
        $lit = array_values(array_intersect(self::networks($highlight['social_networks'] ?? null), self::HIGHLIGHT));
        if ($lit !== []) {
            $values['highlight_networks'] = $lit;
        }

        $values['utm'] = self::on($novashare, 'google_utm');
        foreach (['utm_source' => 'google_utm_source', 'utm_medium' => 'google_utm_medium', 'utm_campaign' => 'google_utm_name'] as $key => $from) {
            $tag = str_replace('{{network}}', '{network}', self::text($novashare, $from));
            if ($tag !== '') {
                $values[$key] = mb_substr($tag, 0, 60);
            }
        }

        $account = ltrim(self::text($novashare, 'twitter_username'), '@');
        if (preg_match('/^\w{1,15}$/', $account) === 1) {
            $values['x_account'] = '@'.$account;
        }

        $values['shortener'] = self::on($novashare, 'enable_bitly') ? 'bitly' : 'none';

        return $values;
    }

    /**
     * The `follow` repeater's value from the first follow widget instance's
     * profiles, in order, leaving out networks Sharely lacks. RSS keeps an
     * empty address, so Sharely links the site's own feed.
     *
     * @param  array<int|string, mixed>  $widgetOption
     * @return list<array{network: string, address: string}>
     *
     * @since 1.0.0
     */
    public static function follow(array $widgetOption): array
    {
        $profiles = [];

        foreach (self::followProfiles($widgetOption) as $key => $address) {
            $network = self::RENAMED[$key] ?? $key;
            if (! in_array($network, self::FOLLOW, true) || count($profiles) >= 24) {
                continue;
            }
            $profiles[] = [
                'network' => $network,
                'address' => $network === 'rss' ? '' : mb_substr(trim((string) $address), 0, 300),
            ];
        }

        return $profiles;
    }

    /**
     * The names of Sharely's secret settings that Novashare held a value
     * for. Names only: the values stay on the old site, for a person to
     * enter on the settings page.
     *
     * @param  array<string, mixed>  $novashare
     * @return list<string>
     *
     * @since 1.0.0
     */
    public static function secrets(array $novashare): array
    {
        $found = [];

        if (self::text($novashare, 'bitly_access_token') !== '') {
            $found[] = 'bitly_token';
        }
        if (self::hasFacebookApp($novashare) || str_contains(self::text($novashare, 'facebook_app_access_token'), '|')) {
            $found[] = 'facebook_token';
        }

        return $found;
    }

    /**
     * Plain sentences naming what Novashare had that Sharely couldn't bring
     * over, for the person who runs the import.
     *
     * @param  array<string, mixed>  $novashare
     * @param  array<int|string, mixed>  $widgetOption
     * @return list<string>
     *
     * @since 1.0.0
     */
    public static function leftOut(array $novashare, array $widgetOption = []): array
    {
        $inline = self::group($novashare, 'inline');
        $floating = self::group($novashare, 'floating');
        $highlight = self::group($novashare, 'highlight');
        $pinterest = self::group($novashare, 'pinterest');
        $sentences = [];

        $lacking = [];
        foreach ([$inline, $floating, self::group($novashare, 'share')] as $group) {
            foreach (self::keys($group['social_networks'] ?? null) as $key) {
                if (! in_array(self::RENAMED[$key] ?? $key, self::SHARE, true)) {
                    $lacking[$key] = self::name($key);
                }
            }
        }
        if ($lacking !== []) {
            $sentences[] = __('Sharely has no share button for these Novashare networks, so they were left out: :networks.', ['networks' => implode(', ', $lacking)]);
        }

        $unlit = [];
        foreach (self::keys($highlight['social_networks'] ?? null) as $key) {
            if (! in_array(self::RENAMED[$key] ?? $key, self::HIGHLIGHT, true)) {
                $unlit[$key] = self::name($key);
            }
        }
        if ($unlit !== []) {
            $sentences[] = __('Sharely’s highlight bar doesn’t offer these networks, so they were left out: :networks.', ['networks' => implode(', ', $unlit)]);
        }

        $unfollowed = [];
        foreach (array_keys(self::followProfiles($widgetOption)) as $key) {
            if (! in_array(self::RENAMED[$key] ?? $key, self::FOLLOW, true)) {
                $unfollowed[$key] = self::name((string) $key);
            }
        }
        if ($unfollowed !== []) {
            $sentences[] = __('Sharely has no follow button for these profiles, so they were left out: :networks.', ['networks' => implode(', ', $unfollowed)]);
        }

        $colors = ['button_color', 'button_hover_color', 'icon_color', 'icon_hover_color', 'total_share_count_color', 'cta_text_color', 'background_color'];
        $custom = false;
        foreach ([$inline, $floating, $highlight] as $group) {
            foreach ($colors as $key) {
                $custom = $custom || self::text($group, $key) !== '';
            }
        }
        $custom = $custom || self::text($pinterest, 'pin_button_color') !== '' || self::text($pinterest, 'pin_icon_color') !== '';
        if ($custom) {
            $sentences[] = __('Novashare’s own button colors weren’t brought over. Sharely draws each network’s colors, your site’s accent, or outlined buttons in your theme’s colors; choose one under How the buttons look.');
        }

        if (self::text($inline, 'style') !== '' || self::text($floating, 'style') !== '' || self::on($inline, 'inverse_hover') || self::on($floating, 'inverse_hover')) {
            $sentences[] = __('Novashare’s button styles (inverse, bordered and minimal, and inverse on hover) weren’t brought over.');
        }

        if (self::text($novashare, 'custom_css') !== '') {
            $sentences[] = __('Novashare’s custom CSS wasn’t brought over, because it was written for Novashare’s markup.');
        }

        $tweet = self::group($novashare, 'click_to_tweet');
        if (array_filter($tweet, static fn (mixed $value): bool => $value !== '' && $value !== null) !== []) {
            $sentences[] = __('Click to Post box styles weren’t brought over. Sharely’s quote buttons take your theme’s look, and Novashare’s Click to Post boxes in articles show as plain text.');
        }

        if (self::text($pinterest, 'pin_button_position') === 'center') {
            $sentences[] = __('Novashare put pin buttons in the center of images; Sharely puts them in a corner, so its default corner stands.');
        }

        if (self::on($floating, 'enabled') && self::text($floating, 'position') === 'none') {
            $sentences[] = __('Novashare’s floating bar showed only where a shortcode placed it, which Sharely doesn’t do, so the floating bar is off.');
        }

        if (self::text($novashare, 'mastodon_username') !== '') {
            $sentences[] = __('Novashare’s Mastodon username wasn’t brought over: Sharely doesn’t add one to what readers post.');
        }

        if (self::on($novashare, 'recover_previous_permalinks') || self::on($novashare, 'combine_http_https')) {
            $sentences[] = __('Novashare’s share count recovery for old permalinks and for http and https wasn’t brought over; only an old domain is.');
        }

        if (self::text($novashare, 'subscribe_link') !== '') {
            $sentences[] = __('Novashare’s Subscribe link wasn’t brought over: Sharely has no Subscribe button.');
        }

        if (self::on($inline, 'feeds') || self::on($floating, 'archives') || self::on($floating, 'posts_page') || self::on($floating, 'home_page')) {
            $sentences[] = __('Sharely shows buttons on articles only, so Novashare’s buttons in feeds, archives and the posts page weren’t brought over.');
        }

        $sentences[] = __('Each article’s own Novashare details (social image and title, Pinterest image and description, hidden buttons) and its stored share counts and short links live in Novashare’s own table, which Teme’s import doesn’t read, so none came over.');

        return $sentences;
    }

    /**
     * The networks the inline and floating sets showed, inline's first, as
     * Sharely's keys, without ones Sharely lacks or repeats.
     *
     * @param  array<string, mixed>  $novashare
     * @return list<string>
     *
     * @since 1.0.0
     */
    private static function active(array $novashare): array
    {
        $inline = self::networks(self::group($novashare, 'inline')['social_networks'] ?? null);
        $floating = self::networks(self::group($novashare, 'floating')['social_networks'] ?? null);

        return array_values(array_unique(array_intersect([...$inline, ...$floating], self::SHARE)));
    }

    /**
     * A list of Novashare network keys as Sharely's, unknown ones kept.
     *
     * @return list<string>
     *
     * @since 1.0.0
     */
    private static function networks(mixed $list): array
    {
        return array_map(static fn (string $key): string => self::RENAMED[$key] ?? $key, self::keys($list));
    }

    /**
     * The first follow widget instance's profiles, network key => value.
     *
     * @param  array<int|string, mixed>  $widgetOption
     * @return array<string, mixed>
     *
     * @since 1.0.0
     */
    private static function followProfiles(array $widgetOption): array
    {
        foreach ($widgetOption as $number => $instance) {
            if (is_int($number) && is_array($instance) && is_array($instance['selected_networks'] ?? null)) {
                return array_filter(
                    $instance['selected_networks'],
                    static fn (mixed $value, int|string $key): bool => is_string($key) && is_scalar($value),
                    ARRAY_FILTER_USE_BOTH,
                );
            }
        }

        return [];
    }

    /**
     * The string items of a list, as lower-case keys.
     *
     * @return list<string>
     *
     * @since 1.0.0
     */
    private static function keys(mixed $list): array
    {
        if (! is_array($list)) {
            return [];
        }

        return array_values(array_map(
            static fn (string $key): string => strtolower(trim($key)),
            array_filter($list, static fn (mixed $key): bool => is_string($key) && trim($key) !== ''),
        ));
    }

    /**
     * One of the option's groups, or none.
     *
     * @param  array<string, mixed>  $novashare
     * @return array<string, mixed>
     *
     * @since 1.0.0
     */
    private static function group(array $novashare, string $key): array
    {
        return is_array($novashare[$key] ?? null) ? $novashare[$key] : [];
    }

    /**
     * Whether a Novashare toggle is on: stored "1", absent when off.
     *
     * @param  array<string, mixed>  $group
     *
     * @since 1.0.0
     */
    private static function on(array $group, string $key): bool
    {
        $value = $group[$key] ?? null;

        return $value === '1' || $value === 1 || $value === true;
    }

    /**
     * A scalar value as trimmed text, '' when absent.
     *
     * @param  array<string, mixed>  $group
     *
     * @since 1.0.0
     */
    private static function text(array $group, string $key): string
    {
        $value = $group[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Whether Novashare held a Facebook app's ID and secret.
     *
     * @param  array<string, mixed>  $novashare
     *
     * @since 1.0.0
     */
    private static function hasFacebookApp(array $novashare): bool
    {
        return self::text($novashare, 'facebook_app_id') !== '' && self::text($novashare, 'facebook_app_secret') !== '';
    }

    /**
     * A joined list cut back to whole items within a length.
     *
     * @since 1.0.0
     */
    private static function fit(string $joined, int $max, string $glue): string
    {
        while (mb_strlen($joined) > $max) {
            $cut = strrpos($joined, $glue);
            $joined = $cut === false ? '' : substr($joined, 0, $cut);
        }

        return $joined;
    }

    /**
     * A Novashare network's name, for a sentence.
     *
     * @since 1.0.0
     */
    private static function name(string $key): string
    {
        return self::NAMES[$key] ?? ucfirst($key);
    }
}
