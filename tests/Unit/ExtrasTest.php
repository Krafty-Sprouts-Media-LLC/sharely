<?php

/**
 * Tests for extras.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Render\Extras;
use KraftySprouts\Sharely\Render\Follow;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Sharing\SharedItem;

function extrasItem(): SharedItem
{
    return new SharedItem('01jz0000000000000000000000', 'https://example.com/roses/', 'Roses', 'About roses.');
}

it('loads no script for buttons that are all plain links', function (): void {
    $config = Config::fromSettings(['networks' => [['network' => 'x'], ['network' => 'facebook']]]);

    expect(Extras::scripts($config))->toBe([]);
});

it('loads each script only when a feature on the page needs it', function (): void {
    $config = Config::fromSettings([
        'networks' => [['network' => 'copy'], ['network' => 'mastodon'], ['network' => 'pinterest']],
        'highlight' => true,
        'pin_images' => true,
    ]);

    expect(Extras::scripts($config))->toBe(['actions', 'mastodon', 'highlight', 'pins']);
});

it('gives the highlight script a template for each network with the placeholder for the quote', function (): void {
    $config = Config::fromSettings(['highlight' => true, 'highlight_networks' => ['x', 'bluesky', 'copy']]);
    $html = Extras::markers($config, extrasItem());

    preg_match('/data-sharely-templates="([^"]*)"/', $html, $m);
    $templates = json_decode(html_entity_decode($m[1]), true);

    expect($templates['x'])->toBe('https://x.com/intent/post?text=%25%25SHARELY%25%25&url=https%3A%2F%2Fexample.com%2Froses%2F')
        ->and($templates['copy'])->toBe('')
        ->and($html)->toContain('data-sharely-networks="x,bluesky,copy"')
        ->and($html)->toContain('data-sharely-quotes="0"');
});

it('gives the pin script a Pinterest template with places for the image and the words', function (): void {
    $config = Config::fromSettings(['pin_images' => true, 'pin_min_width' => 300, 'pin_corner' => 'bottom-end']);
    $html = Extras::markers($config, extrasItem());

    expect($html)->toContain('data-sharely-pins')
        ->toContain('media=%25%25SHARELY_MEDIA%25%25')
        ->toContain('description=%25%25SHARELY_TEXT%25%25')
        ->toContain('data-sharely-min="300"')
        ->toContain('data-sharely-corner="bottom-end"')
        ->toContain('data-sharely-buttons="1"');
});

it('draws no markers when no script needs them', function (): void {
    expect(Extras::markers(Config::fromSettings([]), extrasItem()))->toBe('');
});

it('puts the words, the icons and the script tags at the end of the page', function (): void {
    $config = Config::fromSettings(['networks' => [['network' => 'copy']], 'highlight' => true]);
    $html = Extras::foot($config);

    expect($html)->toContain('<style>')
        ->toContain('data-sharely-words=')
        ->toContain('<template data-sharely-icons>')
        ->toContain('<svg data-network="x"')
        ->toContain('<script src="/sharely/assets/actions.js" defer></script>')
        ->toContain('<script src="/sharely/assets/highlight.js" defer></script>')
        ->not->toContain('mastodon.js');
});

it('draws follow buttons that say where they go, with rel="me" for profile checks', function (): void {
    $config = Config::fromSettings(['follow' => [
        ['network' => 'mastodon', 'address' => 'https://mastodon.social/@site'],
        ['network' => 'email', 'address' => 'hello@example.com'],
        ['network' => 'rss', 'address' => ''],
        ['network' => 'x', 'address' => 'javascript:alert(1)'],
    ], 'follow_heading' => 'Follow us']);
    $html = Follow::html($config, 'https://example.com/feed/');

    expect($html)->toContain('href="https://mastodon.social/@site"')
        ->toContain('rel="me noopener"')
        ->toContain('Follow us on Mastodon (opens in a new tab)')
        ->toContain('href="mailto:hello@example.com"')
        ->toContain('href="https://example.com/feed/"')
        ->not->toContain('javascript:');
});

it('draws nothing for follow buttons with no profiles', function (): void {
    expect(Follow::html(Config::fromSettings([]), ''))->toBe('');
});
