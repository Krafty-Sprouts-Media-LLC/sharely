<?php

/**
 * Tests for sharely in teme.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Teme\Testing\PluginUnderTest;
use Teme\Testing\TemeTestCase;

uses(TemeTestCase::class);

function sharely(object $test): PluginUnderTest
{
    return $test->plugin(__DIR__.'/../..');
}

it('is enabled through the same checks the admin makes, and makes its tables', function (): void {
    sharely($this)->enable();

    foreach (['sharely_items', 'sharely_addresses', 'sharely_clicks', 'sharely_counts', 'sharely_state'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue($table);
    }
});

it('records an article when it is published, and draws share buttons above it', function (): void {
    sharely($this)->enable();
    $item = $this->publish('Feeding roses in spring');

    $row = DB::table('sharely_items')->where('id', $item->id)->first();
    expect($row)->not->toBeNull()
        ->and($row->title)->toBe('Feeding roses in spring')
        ->and((bool) $row->published)->toBeTrue();

    $page = $this->fetch(parse_url((string) $item->url, PHP_URL_PATH));
    $html = $page->getContent();

    expect($page->getStatusCode())->toBe(200)
        ->and($html)->toContain('class="sharely sharely--inline')
        ->and($html)->toContain('/sharely/go/x/'.$item->id)
        ->and($html)->toContain('Share on X (opens in a new tab)')
        ->and($html)->toContain('data-sharely-action="copy"')
        ->and($html)->toContain('<script src="/sharely/assets/actions.js"')
        ->and($html)->toContain('<template data-sharely-icons');

    $policy = (string) ($page->headers->get('content-security-policy') ?? $page->headers->get('content-security-policy-report-only'));
    fwrite(STDERR, "\nCSP: ".$policy."\n");
    expect($policy === '' || preg_match("/script-src[^;]*'self'/", $policy) === 1)->toBeTrue();
});

it('counts a share click and sends the reader on to the network, keeping nothing about them', function (): void {
    sharely($this)->enable();
    $item = $this->publish('Pruning roses');

    $answer = $this->fetch('/sharely/go/x/'.$item->id);

    expect($answer->getStatusCode())->toBe(302)
        ->and($answer->headers->get('location'))->toStartWith('https://x.com/intent/post?text=Pruning%20roses&url=')
        ->and(DB::table('sharely_clicks')->where('item_id', $item->id)->where('network', 'x')->value('clicks'))->toBe(1);

    $columns = Schema::getColumnListing('sharely_clicks');
    sort($columns);
    expect($columns)->toBe(['clicks', 'day', 'item_id', 'network']);
});

it('refuses to redirect for a network that isn’t shown, an unknown item or a draft', function (): void {
    sharely($this)->enable();
    $item = $this->publish('Roses');
    $draft = $this->draft('Not yet');

    expect($this->fetch('/sharely/go/vk/'.$item->id)->getStatusCode())->toBe(404)
        ->and($this->fetch('/sharely/go/x/01jz0000000000000000000000')->getStatusCode())->toBe(404)
        ->and($this->fetch('/sharely/go/x/'.$draft->id)->getStatusCode())->toBe(404)
        ->and(DB::table('sharely_clicks')->count())->toBe(0);
});

it('asks for a Mastodon server without a script, and sends the reader to the one they type', function (): void {
    sharely($this)->enable();
    $item = $this->publish('Roses on Mastodon');

    $form = $this->fetch('/sharely/mastodon/'.$item->id);
    expect($form->getStatusCode())->toBe(200)
        ->and($form->getContent())->toContain('data-sharely-mastodon-form')
        ->and($form->getContent())->toContain('value="mastodon.social"');

    $go = $this->fetch('/sharely/mastodon/'.$item->id.'?server=https://Fosstodon.org/');
    expect($go->getStatusCode())->toBe(302)
        ->and($go->headers->get('location'))->toStartWith('https://fosstodon.org/share?text=Roses%20on%20Mastodon%20');

    expect($this->fetch('/sharely/mastodon/'.$item->id.'?server=localhost')->getStatusCode())->toBe(422);
});

it('serves its scripts from its own address', function (): void {
    sharely($this)->enable();

    $js = $this->fetch('/sharely/assets/actions.js');

    expect($js->getStatusCode())->toBe(200)
        ->and($js->headers->get('content-type'))->toContain('javascript')
        ->and($this->fetch('/sharely/assets/nope.js')->getStatusCode())->toBe(404);
});

it('forgets an article’s buttons when it is unpublished', function (): void {
    sharely($this)->enable();
    $item = $this->publish('Here today');
    $this->unpublish($item);

    expect((bool) DB::table('sharely_items')->where('id', $item->id)->value('published'))->toBeFalse()
        ->and($this->fetch('/sharely/go/x/'.$item->id)->getStatusCode())->toBe(404);
});

it('uses the saved settings for the buttons and the redirect', function (): void {
    $plugin = sharely($this)->enable();
    $plugin->changeSettings([
        'networks' => [['network' => 'bluesky'], ['network' => 'facebook']],
        'utm' => true,
        'heading' => 'Pass it on',
    ]);
    $item = $this->publish('Roses for everyone');

    $html = $this->fetch(parse_url((string) $item->url, PHP_URL_PATH))->getContent();
    expect($html)->toContain('Pass it on')
        ->and($html)->toContain('/sharely/go/bluesky/'.$item->id)
        ->and($html)->not->toContain('/sharely/go/x/');

    expect($this->fetch('/sharely/go/facebook/'.$item->id)->headers->get('location'))
        ->toContain(rawurlencode('utm_source=facebook'));
});

it('runs its jobs', function (): void {
    $plugin = sharely($this)->enable();
    $this->publish('One');

    expect($plugin->runJob('sync-items')->state)->toBe('finished')
        ->and($plugin->runJob('refresh-counts')->state)->toBe('finished')
        ->and($plugin->runJob('shorten')->state)->toBe('finished')
        ->and($plugin->runJob('apply-import')->state)->toBe('finished');
});
