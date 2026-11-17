<?php

/**
 * Tests for icons.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

/**
 * Tests for the inline SVG icons drawn for every network and utility.
 *
 * @package KraftySprouts\Sharely
 *
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Networks\Icons;
use KraftySprouts\Sharely\Networks\Networks;

/**
 * Every icon id Sharely draws: the networks plus the interface glyphs.
 *
 * @return list<string>
 *
 * @since 1.0.0
 */
function iconIds(): array
{
    return [...array_keys(Networks::all()), 'more', 'share', 'close', 'pin', 'check'];
}

it('draws a safe, well-formed svg for every id', function (string $id): void {
    $svg = Icons::svg($id);

    $doc = new DOMDocument();
    expect(@$doc->loadXML($svg))->toBeTrue();

    $root = $doc->documentElement;
    expect($root->nodeName)->toBe('svg')
        ->and($root->getAttribute('viewBox'))->toBe('0 0 24 24')
        ->and($root->getAttribute('aria-hidden'))->toBe('true')
        ->and($root->getAttribute('focusable'))->toBe('false')
        ->and($root->getAttribute('class'))->toBe('sharely-icon')
        ->and($root->getElementsByTagName('path')->length)->toBeGreaterThan(0)
        ->and($svg)->not->toContain('<script')
        ->and($svg)->not->toContain('href')
        ->and($svg)->not->toContain('style')
        ->and($svg)->not->toContain('<use')
        ->and($svg)->not->toContain(' id=')
        ->and(strlen($svg))->toBeLessThan(1536);
})->with(iconIds());

it('draws a different mark for every id', function (): void {
    $marks = array_map(Icons::svg(...), iconIds());

    expect(array_unique($marks))->toHaveCount(count(iconIds()));
});

it('answers the share glyph for an id it does not know', function (): void {
    expect(Icons::svg('myspace'))->toBe(Icons::svg('share'))
        ->and(Icons::svg(''))->toBe(Icons::svg('share'));
});
