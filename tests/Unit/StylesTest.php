<?php

/**
 * Tests for styles.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Networks\Contrast;
use KraftySprouts\Sharely\Render\Styles;
use KraftySprouts\Sharely\Sharing\Config;

it('picks white or black text, whichever reads better on a color', function (): void {
    expect(Contrast::on('#000000'))->toBe('#ffffff')
        ->and(Contrast::on('#25d366'))->toBe('#000000')
        ->and(Contrast::on('#0a66c2'))->toBe('#ffffff')
        ->and(Contrast::ratio('#ffffff', '#0a66c2'))->toBeGreaterThan(4.5);
});

it('gives every network shown its color, with readable text', function (): void {
    $css = Styles::css(Config::fromSettings(['networks' => [['network' => 'whatsapp'], ['network' => 'linkedin']]]));

    expect($css)->toContain('.sharely--brand .sharely__btn--whatsapp{--sharely-bg:#25d366;--sharely-fg:#000000}')
        ->toContain('.sharely--brand .sharely__btn--linkedin{--sharely-bg:#0a66c2;--sharely-fg:#ffffff}');
});

it('draws with the theme’s tokens, so it fits any theme', function (): void {
    $css = Styles::css(Config::fromSettings([]));

    expect($css)->toContain('var(--teme-accent')
        ->toContain('var(--teme-on-accent')
        ->toContain('var(--teme-contrast')
        ->toContain('var(--teme-space-2');
});

it('switches between the main and phone sets at the chosen width', function (): void {
    $css = Styles::css(Config::fromSettings(['phone_set' => true, 'phone_networks' => [['network' => 'sms']], 'phone_width' => 700]));

    expect($css)->toContain('@media (max-width:699.98px){.sharely__set--main{display:none}}')
        ->toContain('@media (min-width:700px){.sharely__set--phone{display:none}}');
});

it('shows focus, respects reduced motion and hides itself in print', function (): void {
    $css = Styles::css(Config::fromSettings([]));

    expect($css)->toContain(':focus-visible')
        ->toContain('@media (prefers-reduced-motion:reduce)')
        ->toContain('@media print{.sharely');
});

it('never closes its style element early', function (): void {
    expect(Styles::css(Config::fromSettings([])))->not->toContain('</style');
});
