<?php

/**
 * Tests for jobs in teme.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use KraftySprouts\Sharely\Admin\Report;
use KraftySprouts\Sharely\Import\NovashareImporter;
use KraftySprouts\Sharely\Store\State;
use Teme\Contracts\Admin\AreaContext;
use Teme\Contracts\Admin\TableQuery;
use Teme\Testing\PluginUnderTest;
use Teme\Testing\TemeTestCase;

uses(TemeTestCase::class);

function sharelyJobs(object $test): PluginUnderTest
{
    return $test->plugin(__DIR__.'/../..');
}

it('asks only the chosen networks for counts, across the article’s addresses, and keeps the larger number', function (): void {
    $plugin = sharelyJobs($this)->enable();
    $item = $this->publish('Counted roses');
    $http = $this->outbound();
    $http->answer('hn.algolia.com', 200, ['hits' => [['url' => $item->url, 'points' => 42]]]);
    $plugin->changeSettings(['fetch_counts' => true, 'count_networks' => ['hackernews']]);

    expect($plugin->runJob('refresh-counts')->state)->toBe('finished');

    expect((int) DB::table('sharely_counts')->where('item_id', $item->id)->where('network', 'hackernews')->value('count'))->toBeGreaterThanOrEqual(42)
        ->and(collect($http->sent())->pluck('host')->unique()->values()->all())->toBe(['hn.algolia.com']);
});

it('sends nothing out while asking for counts is off', function (): void {
    $plugin = sharelyJobs($this)->enable();
    $this->publish('Quiet roses');
    $http = $this->outbound();

    expect($plugin->runJob('refresh-counts')->state)->toBe('finished');
    $http->assertNothingSent();
});

it('makes a short link with Bitly once a token is saved, sending the token only in a header', function (): void {
    $plugin = sharelyJobs($this)->enable();
    $item = $this->publish('Short roses');
    $http = $this->outbound();
    $http->answer('api-ssl.bitly.com', 200, ['link' => 'https://bit.ly/roses']);
    $plugin->changeSettings(['shortener' => 'bitly', 'bitly_token' => 'made-up-test-token']);

    expect($plugin->runJob('shorten')->state)->toBe('finished')
        ->and(DB::table('sharely_items')->where('id', $item->id)->value('short_url'))->toBe('https://bit.ly/roses');

    $sent = $http->sent('api-ssl.bitly.com')[0];
    expect($sent->url)->not->toContain('made-up-test-token')
        ->and($sent->json()['long_url'])->toBe($item->url);
});

it('saves the Sharing panel’s options for an item, and turning buttons off removes them', function (): void {
    $plugin = sharelyJobs($this)->enable();
    $item = $this->publish('Private roses');

    $run = $plugin->runJob('panel-options', ['itemId' => $item->id, 'values' => ['off' => true, 'share_text' => 'Look at these']]);

    expect($run->state)->toBe('finished')
        ->and((bool) DB::table('sharely_items')->where('id', $item->id)->value('off'))->toBeTrue()
        ->and(DB::table('sharely_items')->where('id', $item->id)->value('share_text'))->toBe('Look at these')
        ->and($this->fetch(parse_url((string) $item->url, PHP_URL_PATH))->getContent())->not->toContain('sharely--inline');
});

it('previews how an item looks on each network for the panel', function (): void {
    $plugin = sharelyJobs($this)->enable();
    $item = $this->publish(str_repeat('A very long title about roses ', 4));

    expect($plugin->runJob('panel-preview', ['itemId' => $item->id])->state)->toBe('finished');

    $preview = (new State)->get('preview:'.$item->id);
    expect($preview['lines'])->not->toBeEmpty()
        ->and(collect($preview['lines'])->firstWhere('network', 'x')['fitTitle'])->not->toBeNull();
});

it('reports share clicks by article and network', function (): void {
    sharelyJobs($this)->enable();
    $item = $this->publish('Popular roses');
    $this->fetch('/sharely/go/x/'.$item->id);
    $this->fetch('/sharely/go/x/'.$item->id);
    $this->fetch('/sharely/go/facebook/'.$item->id);

    $report = new Report;
    $context = new AreaContext('someone', 'en', 'UTC', ['report']);
    $figures = $report->figures('overview', 'totals', $context);
    $rows = $report->rows('items', new TableQuery(1, 25, '', [], null, 'desc'), $context);

    expect($figures['clicks-30']->value)->toBe(3)
        ->and($figures['top-network']->value)->toBe('X')
        ->and($rows->total)->toBe(1)
        ->and($rows->rows[0]->cells['clicks']->value)->toBe(3)
        ->and($report->chart('overview', 'per-day', $context)->series['clicks'])->toHaveCount(30);
});

it('keeps what an import mapped, and saves it when someone applies it', function (): void {
    $plugin = sharelyJobs($this)->enable();
    (new State)->put(NovashareImporter::PENDING, [
        'writable' => ['inline_position' => 'both', 'heading' => 'Spread the word'],
        'kept' => ['networks' => [['network' => 'x'], ['network' => 'bluesky']]],
        'secrets' => ['bitly_token'],
        'leftOut' => [],
    ]);

    $run = $plugin->runJob('apply-import');

    $todo = (new Report)->figures('import', 'import', new AreaContext('someone', 'en', 'UTC', ['report']))['import-todo']->value;

    expect($run->state)->toBe('finished')
        ->and($todo)->toContain('X, Bluesky')
        ->and($todo)->toContain('Bitly')
        ->and(app(\Teme\Contracts\Settings\SettingsReader::class)->string('plugin.sharely', 'heading'))->toBe('Spread the word');
});
