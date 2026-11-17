<?php

/**
 * Real-looking `novashare` and follow widget options: toggles "1" or
 * absent, select defaults "", network lists as ordered keys.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Tests\Support;

/**
 * Real-looking `novashare` and follow widget options: toggles "1" or
 * absent, select defaults "", network lists as ordered keys. Every
 * credential is made up.
 *
 * @since 1.0.0
 */
final class NovashareFixtures
{
    /**
     * The `novashare` option, with any part replaced.
     *
     * @param  array<string, mixed>  $over
     * @return array<string, mixed>
     *
     * @since 1.0.0
     */
    public static function option(array $over = []): array
    {
        return array_replace_recursive([
            'inline' => [
                'enabled' => '1',
                'social_networks' => ['twitter', 'facebook', 'pocket', 'linkedin', 'vkontakte', 'share'],
                'post_types' => ['post', 'page', 'recipe', 'product'],
                'position' => 'both',
                'breakpoint' => '1200px',
                'style' => '',
                'layout' => '',
                'alignment' => 'center',
                'size' => 'large',
                'shape' => 'circular',
                'labels' => '1',
                'total_share_count' => '1',
                'total_share_count_position' => '',
                'network_share_counts' => '1',
                'cta_text' => 'Share this recipe',
            ],
            'floating' => [
                'enabled' => '1',
                'social_networks' => ['twitter', 'facebook', 'pinterest', 'mix'],
                'post_types' => ['post'],
                'position' => '',
                'alignment' => 'right',
            ],
            'highlight' => [
                'enabled' => '1',
                'social_networks' => ['twitter', 'bluesky', 'email', 'reddit'],
            ],
            'pinterest' => [
                'share_button_behavior' => 'grid',
                'image_pins' => '1',
                'pin_button_position' => 'bottomright',
            ],
            'twitter_username' => 'gardenbeds',
            'minimum_share_count' => '25',
            'refresh_rate' => 'medium',
            'google_utm' => '1',
            'google_utm_source' => '{{network}}',
            'google_utm_medium' => 'social',
            'google_utm_name' => 'spring-{{network}}',
            'enable_bitly' => '1',
            'bitly_access_token' => 'made-up-bitly-token',
            'facebook_app_id' => '1234567890',
            'facebook_app_secret' => 'made-up-secret',
            'recover_previous_domain' => '1',
            'previous_domain' => 'old-garden.example',
        ], $over);
    }

    /**
     * The `widget_novashare_follow_widget` option: instances by number,
     * and WordPress's `_multiwidget` marker.
     *
     * @return array<int|string, mixed>
     *
     * @since 1.0.0
     */
    public static function followWidget(): array
    {
        return [
            2 => [
                'title' => 'Follow us',
                'shape' => 'rounded',
                'selected_networks' => [
                    'facebook' => 'https://www.facebook.com/gardenbeds',
                    'instagram' => 'https://www.instagram.com/gardenbeds',
                    'twitter' => 'https://x.com/gardenbeds',
                    'email' => 'hello@gardenbeds.example',
                    'rss' => 'https://gardenbeds.example/feed/',
                ],
            ],
            3 => ['selected_networks' => ['youtube' => 'https://youtube.com/@x']],
            '_multiwidget' => 1,
        ];
    }
}
