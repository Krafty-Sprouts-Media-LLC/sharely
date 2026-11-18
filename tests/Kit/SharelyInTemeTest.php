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
        ->and($html)->toContain('https://x.com/intent/post')
        ->and($html)->not->toContain('/sharely/go/')
        ->and($html)->toContain('Share on X (opens in a new tab)')
        ->and($html)->toContain('data-sharely-action="copy"')
        ->and($html)->toContain('<script src="/sharely/assets/actions.js"')
        ->and($html)->toContain('<template data-sharely-icons');

    $plugin = sharely($this);
    $plugin->changeSettings(['highlight' => true]);
    expect($this->fetch(parse_url((string) $item->url, PHP_URL_PATH))->getContent())
        ->toContain('href="/sharely/assets/highlight.css"')
        ->toContain('data-sharely-highlight');

    $policy = (string) ($page->headers->get('content-security-policy') ?? $page->headers->get('content-security-policy-report-only'));
    fwrite(STDERR, "\nCSP: ".$policy."\n");
    expect($policy === '' || preg_match("/script-src[^;]*'self'/", $policy) === 1)->toBeTrue();
});

it('counts a share click and sends the reader on to the network, keeping nothing about them', function (): void {
    sharely($this)->enable()->changeSettings(['count_clicks' => true]);
    $item = $this->publish('Pruning roses');

    $answer = $this->fetch('/sharely/go/x/'.$item->id);

    expect($answer->getStatusCode())->toBe(302)
        ->and($answer->headers->get('location'))->toStartWith('https://x.com/intent/post?text=Pruning%20roses&url=')
        ->and(DB::table('sharely_clicks')->where('item_id', $item->id)->where('network', 'x')->value('clicks'))->toBe(1);

    $columns = Schema::getColumnListing('sharely_clicks');
    sort($columns);
    expect($columns)->toBe(['clicks', 'day', 'item_id', 'network']);
});

it('counts nothing until counting is turned on, and the share still works', function (): void {
    sharely($this)->enable();
    $item = $this->publish('Uncounted roses');

    $answer = $this->fetch('/sharely/go/x/'.$item->id);

    expect($answer->getStatusCode())->toBe(302)
        ->and($answer->headers->get('location'))->toStartWith('https://x.com/intent/post')
        ->and(DB::table('sharely_clicks')->count())->toBe(0);
});

it('counts one click per article per address in ten minutes, however often it is asked, and still redirects every time', function (): void {
    sharely($this)->enable()->changeSettings(['count_clicks' => true]);
    $item = $this->publish('Hammered roses');

    foreach (range(1, 5) as $_) {
        expect($this->fetch('/sharely/go/x/'.$item->id)->getStatusCode())->toBe(302);
    }

    expect((int) DB::table('sharely_clicks')->where('item_id', $item->id)->sum('clicks'))->toBe(1);
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

    // A typed server never redirects at once: a page names it first, and counts nothing.
    $leaving = $this->fetch('/sharely/mastodon/'.$item->id.'?server=https://Fosstodon.org/');
    $html = $leaving->getContent();
    expect($leaving->getStatusCode())->toBe(200)
        ->and($leaving->headers->get('location'))->toBeNull()
        ->and($html)->toContain('You’re leaving for fosstodon.org to share this post')
        ->and($html)->toContain('Continue')
        ->and(DB::table('sharely_clicks')->count())->toBe(0);

    preg_match('#href="(/sharely/mastodon/[^"]*go=[0-9a-f]{32}[^"]*)"#', $html, $m);
    expect($m)->not->toBeEmpty();

    $go = $this->fetch(html_entity_decode($m[1]));
    expect($go->getStatusCode())->toBe(302)
        ->and($go->headers->get('location'))->toStartWith('https://fosstodon.org/share?text=Roses%20on%20Mastodon%20');
});

it('counts a Mastodon share only when the reader continues, and only when counting is on', function (): void {
    sharely($this)->enable()->changeSettings(['count_clicks' => true]);
    $item = $this->publish('Counted on Mastodon');

    $leaving = $this->fetch('/sharely/mastodon/'.$item->id.'?server=mas.to')->getContent();
    expect(DB::table('sharely_clicks')->count())->toBe(0);

    preg_match('#href="(/sharely/mastodon/[^"]*go=[0-9a-f]{32}[^"]*)"#', $leaving, $m);
    $this->fetch(html_entity_decode($m[1]));

    expect((int) DB::table('sharely_clicks')->where('item_id', $item->id)->where('network', 'mastodon')->sum('clicks'))->toBe(1);
});

it('refuses a Mastodon server that isn’t a public host name, and a continue link that was made up', function (string $server): void {
    sharely($this)->enable();
    $item = $this->publish('Careful roses');

    $answer = $this->fetch('/sharely/mastodon/'.$item->id.'?server='.rawurlencode($server));

    expect($answer->getStatusCode())->toBe(422)
        ->and($answer->headers->get('location'))->toBeNull()
        ->and($answer->getContent())->not->toContain('Continue');
})->with(['localhost', '127.0.0.1', '192.168.1.1', 'mastodon.social:8443', 'user@mastodon.social', 'mastodon.social/@me', 'db.internal']);

it('shows the leaving page again, never a redirect, for a continue link that was made up', function (): void {
    sharely($this)->enable();
    $item = $this->publish('Forged roses');

    $answer = $this->fetch('/sharely/mastodon/'.$item->id.'?server=mas.to&go='.str_repeat('0', 32));

    expect($answer->getStatusCode())->toBe(200)
        ->and($answer->headers->get('location'))->toBeNull()
        ->and($answer->getContent())->toContain('You’re leaving for mas.to to share this post');
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
        'count_clicks' => true,
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
