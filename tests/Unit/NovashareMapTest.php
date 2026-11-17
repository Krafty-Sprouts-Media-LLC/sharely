<?php

/**
 * Tests for novashare map.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Import\NovashareMap;
use KraftySprouts\Sharely\Tests\Support\NovashareFixtures;

/**
 * The fixture option, with any part replaced.
 *
 * @since 1.0.0
 */
function novashareOption(array $over = []): array
{
    return NovashareFixtures::option($over);
}

it('places the inline buttons as Novashare did', function (?string $enabled, string $position, string $expected): void {
    $option = novashareOption(['inline' => ['position' => $position]]);
    if ($enabled === null) {
        unset($option['inline']['enabled']);
    }

    expect(NovashareMap::settings($option)['inline_position'])->toBe($expected);
})->with([
    'default above' => ['1', '', 'above'],
    'below' => ['1', 'below', 'below'],
    'both' => ['1', 'both', 'both'],
    'shortcode only' => ['1', 'neither', 'none'],
    'turned off' => [null, 'both', 'none'],
]);

it('splits post types into Sharely\'s two and the rest', function (): void {
    $settings = NovashareMap::settings(novashareOption());

    expect($settings['types'])->toBe(['post', 'page'])
        ->and($settings['other_types'])->toBe('recipe, product');
});

it('maps the floating bar side, and off to none', function (): void {
    expect(NovashareMap::settings(novashareOption())['floating'])->toBe('right');
    expect(NovashareMap::settings(novashareOption(['floating' => ['alignment' => '']]))['floating'])->toBe('left');

    $off = novashareOption();
    unset($off['floating']['enabled']);
    expect(NovashareMap::settings($off)['floating'])->toBe('none');
});

it('renames networks, drops the ones Sharely lacks, and gives the repeater shape', function (): void {
    $networks = NovashareMap::settings(novashareOption())['networks'];

    expect($networks)->toBe([
        ['network' => 'x'],
        ['network' => 'facebook'],
        ['network' => 'linkedin'],
        ['network' => 'vk'],
        ['network' => 'pinterest'],
    ]);
});

it('leaves the networks out when none Sharely knows remain', function (): void {
    $option = novashareOption();
    $option['inline']['social_networks'] = ['pocket', 'xing'];
    $option['floating']['social_networks'] = ['mix'];
    $settings = NovashareMap::settings($option);

    expect($settings)->not->toHaveKey('networks');
});

it('maps the look', function (): void {
    $settings = NovashareMap::settings(novashareOption());

    expect($settings['size'])->toBe('large')
        ->and($settings['shape'])->toBe('pill')
        ->and($settings['labels'])->toBe('labels')
        ->and($settings['align'])->toBe('center')
        ->and($settings['colors'])->toBe('brand')
        ->and($settings['heading'])->toBe('Share this recipe');

    $defaults = NovashareMap::settings(novashareOption(['inline' => [
        'size' => '', 'shape' => '', 'alignment' => '', 'labels' => null,
    ]]));
    expect($defaults['size'])->toBe('medium')
        ->and($defaults['shape'])->toBe('square')
        ->and($defaults['labels'])->toBe('icons')
        ->and($defaults['align'])->toBe('start');

    expect(NovashareMap::settings(novashareOption(['inline' => ['alignment' => 'right']]))['align'])->toBe('end');
    expect(NovashareMap::settings(novashareOption(['inline' => ['layout' => '3-col']]))['align'])->toBe('fill');
    expect(NovashareMap::settings(novashareOption(['inline' => ['size' => 'small']]))['size'])->toBe('small');
});

it('maps counts', function (): void {
    $settings = NovashareMap::settings(novashareOption());

    expect($settings['show_counts'])->toBeTrue()
        ->and($settings['show_total'])->toBeTrue()
        ->and($settings['min_count'])->toBe(25)
        ->and($settings['counts_every'])->toBe('day')
        ->and($settings['fetch_counts'])->toBeTrue()
        ->and($settings['count_networks'])->toBe(['facebook', 'pinterest'])
        ->and($settings['old_domain'])->toBe('https://old-garden.example');

    expect(NovashareMap::settings(novashareOption(['refresh_rate' => '']))['counts_every'])->toBe('hour');
    expect(NovashareMap::settings(novashareOption(['refresh_rate' => 'low']))['counts_every'])->toBe('day');
});

it('turns counts off and asks no network when Novashare had them off and no Facebook app', function (): void {
    $option = novashareOption();
    unset($option['inline']['total_share_count'], $option['inline']['network_share_counts'],
        $option['facebook_app_id'], $option['facebook_app_secret'], $option['recover_previous_domain']);

    $settings = NovashareMap::settings($option);

    expect($settings['show_counts'])->toBeFalse()
        ->and($settings['show_total'])->toBeFalse()
        ->and($settings)->not->toHaveKey('fetch_counts')
        ->and($settings)->not->toHaveKey('old_domain');
});

it('maps links: UTM, the X account and the shortener', function (): void {
    $settings = NovashareMap::settings(novashareOption());

    expect($settings['utm'])->toBeTrue()
        ->and($settings['utm_source'])->toBe('{network}')
        ->and($settings['utm_medium'])->toBe('social')
        ->and($settings['utm_campaign'])->toBe('spring-{network}')
        ->and($settings['x_account'])->toBe('@gardenbeds')
        ->and($settings['shortener'])->toBe('bitly');

    $off = novashareOption();
    unset($off['enable_bitly'], $off['google_utm']);
    expect(NovashareMap::settings($off)['shortener'])->toBe('none')
        ->and(NovashareMap::settings($off)['utm'])->toBeFalse();
});

it('maps highlight and Pinterest', function (): void {
    $settings = NovashareMap::settings(novashareOption());

    expect($settings['highlight'])->toBeTrue()
        ->and($settings['highlight_networks'])->toBe(['x', 'bluesky', 'email', 'reddit'])
        ->and($settings['pin_images'])->toBeTrue()
        ->and($settings['pin_corner'])->toBe('bottom-end')
        ->and($settings['pin_pick'])->toBeTrue();

    expect(NovashareMap::settings(novashareOption(['pinterest' => ['pin_button_position' => '']]))['pin_corner'])->toBe('top-start');
    expect(NovashareMap::settings(novashareOption(['pinterest' => ['pin_button_position' => 'center']])))->not->toHaveKey('pin_corner');
});

it('writes only keys Sharely declares, never a secret', function (): void {
    $declared = array_column(json_decode((string) file_get_contents(__DIR__.'/../../teme-plugin.json'), true)['contributes']['settings'], null, 'key');
    $settings = NovashareMap::settings(novashareOption());

    foreach ($settings as $key => $value) {
        expect($declared)->toHaveKey($key);
        expect($declared[$key]['type'])->not->toBe('secret');
        if (in_array($declared[$key]['type'], ['select'], true)) {
            expect(array_keys($declared[$key]['options']))->toContain($value);
        }
        if ($declared[$key]['type'] === 'multiselect') {
            expect(array_diff($value, array_keys($declared[$key]['options'])))->toBe([]);
        }
        if (isset($declared[$key]['maxLength'])) {
            expect(mb_strlen($value))->toBeLessThanOrEqual($declared[$key]['maxLength']);
        }
    }
    expect(json_encode($settings))->not->toContain('made-up');
});

it('copes with an empty or missing option', function (): void {
    expect(NovashareMap::settings([]))->toBeArray()
        ->and(NovashareMap::settings([])['inline_position'])->toBe('none')
        ->and(NovashareMap::leftOut([]))->toBeArray();
});

it('names the secrets it found, never their values', function (): void {
    expect(NovashareMap::secrets(novashareOption()))->toBe(['bitly_token', 'facebook_token']);

    $none = novashareOption();
    unset($none['bitly_access_token'], $none['facebook_app_secret']);
    expect(NovashareMap::secrets($none))->toBe([]);
});

it('brings the first follow widget\'s profiles over in order', function (): void {
    $widget = [
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

    expect(NovashareMap::follow($widget))->toBe([
        ['network' => 'facebook', 'address' => 'https://www.facebook.com/gardenbeds'],
        ['network' => 'x', 'address' => 'https://x.com/gardenbeds'],
        ['network' => 'email', 'address' => 'hello@gardenbeds.example'],
        ['network' => 'rss', 'address' => ''],
    ]);
    expect(NovashareMap::follow([]))->toBe([]);
    expect(NovashareMap::follow(['_multiwidget' => 1]))->toBe([]);
});

it('says in plain sentences what it could not bring over', function (): void {
    $option = novashareOption([
        'inline' => ['button_color' => '#336699', 'style' => 'inverse'],
        'custom_css' => '.ns-buttons { margin: 0 }',
        'click_to_tweet' => ['theme' => 'simple', 'accent_color' => '#000000'],
        'pinterest' => ['pin_button_position' => 'center'],
    ]);
    $widget = [2 => ['selected_networks' => ['instagram' => 'https://www.instagram.com/x']]];

    $sentences = NovashareMap::leftOut($option, $widget);
    $all = implode("\n", $sentences);

    expect($sentences)->each->toBeString()
        ->and($all)->toContain('Pocket')
        ->and($all)->toContain('Mix')
        ->and($all)->toContain('colors')
        ->and($all)->toContain('custom CSS')
        ->and($all)->toContain('Click to Post')
        ->and($all)->toContain('center')
        ->and($all)->toContain('Instagram')
        ->and($all)->not->toContain('#336699')
        ->and($all)->not->toContain('made-up');
});
