<?php

/**
 * Tests for buttons.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Render\Buttons;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Sharing\SharedItem;

function shared(array $over = []): SharedItem
{
    return new SharedItem(...array_merge([
        'id' => '01jz0000000000000000000000',
        'url' => 'https://example.com/roses/',
        'title' => 'Feeding <roses>',
        'description' => 'When to feed them.',
        'image' => 'https://example.com/rose.jpg',
    ], $over));
}

function dom(string $html): DOMXPath
{
    $doc = new DOMDocument;
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>');
    libxml_clear_errors();

    return new DOMXPath($doc);
}

function buttons(array $settings = [], array $counts = []): string
{
    return (new Buttons(Config::fromSettings($settings)))->inline(shared(), $counts);
}

it('draws one named link per network, in the chosen order, each opening in a new tab', function (): void {
    $x = dom(buttons(['networks' => [['network' => 'bluesky'], ['network' => 'x']], 'count_clicks' => false]));
    $links = $x->query('//a[contains(@class,"sharely__btn")]');

    expect($links->length)->toBe(2)
        ->and($links->item(0)->getAttribute('href'))->toStartWith('https://bsky.app/intent/compose?text=Feeding%20%3Croses%3E')
        ->and($links->item(0)->getAttribute('target'))->toBe('_blank')
        ->and($links->item(0)->getAttribute('rel'))->toBe('noopener nofollow')
        ->and(trim($links->item(0)->textContent))->toContain('Share on Bluesky (opens in a new tab)')
        ->and($links->item(1)->getAttribute('class'))->toContain('sharely__btn--x');
});

it('sends clicks through Sharely’s counting address when counting is on', function (): void {
    $x = dom(buttons(['networks' => [['network' => 'facebook']]]));

    expect($x->query('//a[contains(@class,"sharely__btn--facebook")]')->item(0)->getAttribute('href'))
        ->toBe('/sharely/go/facebook/01jz0000000000000000000000');
});

it('keeps mail and text messages in the same tab, and doesn’t count them', function (): void {
    $x = dom(buttons(['networks' => [['network' => 'email']]]));
    $mail = $x->query('//a[contains(@class,"sharely__btn--email")]')->item(0);

    expect($mail->getAttribute('href'))->toStartWith('mailto:?subject=')
        ->and($mail->hasAttribute('target'))->toBeFalse()
        ->and($mail->textContent)->not->toContain('new tab');
});

it('draws script buttons hidden, so they never show without their script', function (): void {
    $x = dom(buttons(['networks' => [['network' => 'copy'], ['network' => 'sheet'], ['network' => 'print']]]));
    $copy = $x->query('//button[@data-sharely-action="copy"]')->item(0);

    expect($copy->hasAttribute('hidden'))->toBeTrue()
        ->and($copy->getAttribute('type'))->toBe('button')
        ->and($copy->getAttribute('data-sharely-url'))->toBe('https://example.com/roses/')
        ->and($copy->getAttribute('data-sharely-done'))->toBe('Link copied')
        ->and($x->query('//button[@data-sharely-action="sheet"][@hidden]')->length)->toBe(1)
        ->and($x->query('//button[@data-sharely-action="print"][@hidden]')->length)->toBe(1)
        ->and($x->query('//*[@data-sharely-status][@role="status"]')->length)->toBe(1);
});

it('links Mastodon to Sharely’s server picker, with the words to post', function (): void {
    $x = dom(buttons(['networks' => [['network' => 'mastodon']], 'mastodon_server' => 'fosstodon.org']));
    $link = $x->query('//a[@data-sharely-mastodon]')->item(0);

    expect($link->getAttribute('href'))->toBe('/sharely/mastodon/01jz0000000000000000000000')
        ->and($link->getAttribute('data-sharely-text'))->toBe('Feeding <roses> https://example.com/roses/')
        ->and($link->getAttribute('data-sharely-server'))->toBe('fosstodon.org');
});

it('escapes everything it prints', function (): void {
    $html = buttons(['heading' => '<b>Share</b>']);

    expect($html)->not->toContain('<roses>')
        ->and($html)->not->toContain('<b>')
        ->and($html)->toContain('&lt;b&gt;Share&lt;/b&gt;');
});

it('puts the buttons past the chosen number behind a More button that needs no script', function (): void {
    $x = dom(buttons([
        'networks' => [['network' => 'x'], ['network' => 'facebook'], ['network' => 'reddit'], ['network' => 'tumblr']],
        'more_after' => 2,
    ]));

    expect($x->query('//ul[contains(@class,"sharely__list--main")]/li[contains(@class,"sharely__item")][not(contains(@class,"more"))]')->length)->toBe(2)
        ->and($x->query('//details[contains(@class,"sharely__more")]/summary')->length)->toBe(1)
        ->and(trim($x->query('//details/summary')->item(0)->textContent))->toContain('More ways to share')
        ->and($x->query('//details//a[contains(@class,"sharely__btn")]')->length)->toBe(2);
});

it('draws a separate phone set when one is chosen', function (): void {
    $x = dom(buttons(['phone_set' => true, 'phone_networks' => [['network' => 'whatsapp']]]));

    expect($x->query('//*[contains(@class,"sharely__set--main")]')->length)->toBe(1)
        ->and($x->query('//*[contains(@class,"sharely__set--phone")]//a[contains(@class,"sharely__btn--whatsapp")]')->length)->toBe(1);
});

it('shows counts only from the minimum, with a total', function (): void {
    $settings = ['networks' => [['network' => 'x'], ['network' => 'facebook']], 'show_counts' => true, 'min_count' => 10];

    expect(buttons($settings, ['x' => 3, 'facebook' => 4]))->not->toContain('sharely__count')
        ->and(buttons($settings, ['x' => 3, 'facebook' => 1500]))
        ->toContain('<span class="sharely__count" aria-hidden="true">1.5K</span>')
        ->toContain('sharely__total');
});

it('marks the set with its look, for the stylesheet', function (): void {
    $x = dom(buttons(['shape' => 'pill', 'size' => 'large', 'labels' => 'labels', 'colors' => 'accent', 'align' => 'center']));
    $class = $x->query('//*[@data-sharely]')->item(0)->getAttribute('class');

    expect($class)->toContain('sharely--inline')
        ->toContain('sharely--pill')
        ->toContain('sharely--large')
        ->toContain('sharely--labels')
        ->toContain('sharely--accent')
        ->toContain('sharely--align-center');
});

it('draws the heading and names the group by it', function (): void {
    $x = dom(buttons(['heading' => 'Pass it on']));
    $group = $x->query('//*[@role="group"]')->item(0);
    $heading = $x->query('//*[@id="'.$group->getAttribute('aria-labelledby').'"]')->item(0);

    expect($heading->textContent)->toBe('Pass it on');
});

it('marks the Pinterest button for the image picker when picking is on', function (): void {
    $x = dom(buttons(['networks' => [['network' => 'pinterest']], 'pin_pick' => true]));

    expect($x->query('//a[@data-sharely-pick]')->length)->toBe(1);
});

it('draws a floating bar with icons only and no heading', function (): void {
    $html = (new Buttons(Config::fromSettings(['labels' => 'labels'])))->floating(shared(), []);
    $x = dom($html);

    expect($x->query('//*[contains(@class,"sharely--floating")]')->length)->toBe(1)
        ->and($html)->not->toContain('sharely__heading')
        ->and($html)->toContain('sharely--icons');
});
