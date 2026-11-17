<?php

/**
 * Tests for config.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Sharing\Config;

it('reads the declared defaults when nothing was saved', function (): void {
    $config = Config::fromSettings([]);

    expect($config->inlinePosition)->toBe('above')
        ->and($config->types)->toBe(['post'])
        ->and($config->networks)->toBe(['x', 'facebook', 'linkedin', 'bluesky', 'email', 'copy'])
        ->and($config->floating)->toBe('none')
        ->and($config->heading)->toBe('Share this')
        ->and($config->countClicks)->toBeTrue()
        ->and($config->showCounts)->toBeFalse()
        ->and($config->minCount)->toBe(10)
        ->and($config->phoneWidth)->toBe(768)
        ->and($config->utm->enabled)->toBeFalse()
        ->and($config->mastodonServer)->toBe('mastodon.social');
});

it('reads repeaters stored as JSON text or as lists, keeping their order and dropping unknown or repeated networks', function (): void {
    $config = Config::fromSettings([
        'networks' => json_encode([['network' => 'bluesky'], ['network' => 'nope'], ['network' => 'x'], ['network' => 'bluesky']]),
        'phone_networks' => [['network' => 'sheet'], ['network' => 'whatsapp']],
        'phone_set' => true,
    ]);

    expect($config->networks)->toBe(['bluesky', 'x'])
        ->and($config->phoneNetworks)->toBe(['sheet', 'whatsapp']);
});

it('uses the main set on phones unless a separate set is on and has networks', function (): void {
    expect(Config::fromSettings(['phone_set' => false, 'phone_networks' => [['network' => 'sms']]])->phoneNetworks)->toBe([])
        ->and(Config::fromSettings(['phone_set' => true, 'phone_networks' => []])->phoneNetworks)->toBe([]);
});

it('joins the chosen and the typed content types', function (): void {
    $config = Config::fromSettings(['types' => ['post', 'page'], 'other_types' => ' recipe, Review ,bad key!, recipe']);

    expect($config->types)->toBe(['post', 'page', 'recipe', 'review'])
        ->and($config->sharesType('recipe'))->toBeTrue()
        ->and($config->sharesType('event'))->toBeFalse();
});

it('reads follow profiles, keeping RSS without an address and dropping other empty rows', function (): void {
    $config = Config::fromSettings(['follow' => [
        ['network' => 'mastodon', 'address' => ' https://mastodon.social/@site '],
        ['network' => 'x', 'address' => ''],
        ['network' => 'rss', 'address' => ''],
        ['network' => 'sms', 'address' => 'x'],
    ]]);

    expect($config->follow)->toBe([
        ['network' => 'mastodon', 'address' => 'https://mastodon.social/@site'],
        ['network' => 'rss', 'address' => ''],
    ]);
});

it('keeps only highlight networks for the highlight bar', function (): void {
    expect(Config::fromSettings(['highlight_networks' => ['x', 'pinterest', 'copy']])->highlightNetworks)->toBe(['x', 'copy']);
});

it('falls back to a select’s default for a value it doesn’t offer', function (): void {
    $config = Config::fromSettings(['shape' => 'hexagon', 'size' => 'large', 'pin_corner' => 'middle']);

    expect($config->shape)->toBe('rounded')
        ->and($config->size)->toBe('large')
        ->and($config->pinCorner)->toBe('top-start');
});

it('clamps numbers to their declared range', function (): void {
    $config = Config::fromSettings(['phone_width' => 10, 'min_count' => -4, 'more_after' => '3']);

    expect($config->phoneWidth)->toBe(320)
        ->and($config->minCount)->toBe(0)
        ->and($config->moreAfter)->toBe(3);
});

it('builds the UTM tags from their settings', function (): void {
    $utm = Config::fromSettings(['utm' => true, 'utm_source' => '{network}', 'utm_medium' => 'social', 'utm_campaign' => 'spring'])->utm;

    expect($utm->apply('https://example.com/a/', 'x'))->toBe('https://example.com/a/?utm_source=x&utm_medium=social&utm_campaign=spring');
});
