<?php

/**
 * Tests for count sources.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Counts\CountSource;
use KraftySprouts\Sharely\Counts\Sources;
use KraftySprouts\Sharely\Counts\Sources\FacebookSource;
use KraftySprouts\Sharely\Counts\Sources\HackerNewsSource;
use KraftySprouts\Sharely\Counts\Sources\PinterestSource;
use KraftySprouts\Sharely\Counts\Sources\RedditSource;
use KraftySprouts\Sharely\Tests\Support\FakeHttp;
use Teme\Contracts\Http\AnswerTooLarge;
use Teme\Contracts\Http\HostRefused;
use Teme\Contracts\Http\RequestFailed;

const COUNT_URL = 'https://example.com/roses/?a=1&b=2';
const COUNT_URL_ENCODED = 'https%3A%2F%2Fexample.com%2Froses%2F%3Fa%3D1%26b%3D2';
const FB_TOKEN = 'test-app|test-secret';

// Facebook

it('asks Facebook for engagement with the address and token encoded', function (): void {
    $http = (new FakeHttp)->answerJson('graph.facebook.com', ['engagement' => ['share_count' => 3, 'reaction_count' => 4, 'comment_count' => 5]]);
    $source = new FacebookSource($http, FB_TOKEN);

    expect($source->count(COUNT_URL))->toBe(12)
        ->and($source->network())->toBe('facebook')
        ->and($source->host())->toBe('graph.facebook.com')
        ->and($http->requests)->toHaveCount(1)
        ->and($http->requests[0]['method'])->toBe('GET')
        ->and($http->requests[0]['url'])->toBe('https://graph.facebook.com/v21.0/?id='.COUNT_URL_ENCODED.'&fields=engagement&access_token=test-app%7Ctest-secret');
});

it('sends nothing to Facebook without a token', function (string $token): void {
    $http = new FakeHttp;

    expect((new FacebookSource($http, $token))->count(COUNT_URL))->toBeNull()
        ->and($http->requests)->toBe([]);
})->with(['', '   ']);

it('counts the Facebook fields that are there and answers null without engagement', function (): void {
    $http = (new FakeHttp)
        ->answerJson('graph.facebook.com', ['engagement' => ['share_count' => 7]])
        ->answerJson('graph.facebook.com', ['id' => COUNT_URL])
        ->answerJson('graph.facebook.com', ['engagement' => 'nope'])
        ->answerJson('graph.facebook.com', ['engagement' => ['share_count' => '9', 'reaction_count' => 1]]);
    $source = new FacebookSource($http, FB_TOKEN);

    expect($source->count(COUNT_URL))->toBe(7)
        ->and($source->count(COUNT_URL))->toBeNull()
        ->and($source->count(COUNT_URL))->toBeNull()
        ->and($source->count(COUNT_URL))->toBe(10);
});

it('answers null when Facebook refuses the token', function (): void {
    $http = (new FakeHttp)->answerJson('graph.facebook.com', ['error' => ['message' => 'Invalid token test-app|test-secret']], 400);

    expect((new FacebookSource($http, FB_TOKEN))->count(COUNT_URL))->toBeNull();
});

// Pinterest

it('asks Pinterest with the address encoded and reads a JSONP answer', function (): void {
    $http = (new FakeHttp)->answer('widgets.pinterest.com', 200, 'receiveCount({"url":"https://example.com/roses/","count":42})');
    $source = new PinterestSource($http);

    expect($source->count(COUNT_URL))->toBe(42)
        ->and($source->network())->toBe('pinterest')
        ->and($source->host())->toBe('widgets.pinterest.com')
        ->and($http->requests[0]['url'])->toBe('https://widgets.pinterest.com/v1/urls/count.json?source=6&url='.COUNT_URL_ENCODED);
});

it('reads Pinterest as plain JSON or JSONP with a semicolon and spaces', function (string $body, ?int $expected): void {
    $http = (new FakeHttp)->answer('widgets.pinterest.com', 200, $body);

    expect((new PinterestSource($http))->count(COUNT_URL))->toBe($expected);
})->with([
    'plain' => ['{"count":5}', 5],
    'jsonp semicolon' => ["  receiveCount( {\"count\": 8} );\n", 8],
    'zero' => ['receiveCount({"count":0})', 0],
    'negative clamped' => ['{"count":-3}', 0],
    'no count' => ['receiveCount({"url":"x"})', null],
    'count not a number' => ['{"count":"lots"}', null],
    'malformed' => ['receiveCount({"count":', null],
    'empty' => ['', null],
    'html' => ['<html>busy</html>', null],
]);

// Reddit

it('asks Reddit with a User-Agent naming the plugin and sums the scores', function (): void {
    $http = (new FakeHttp)->answerJson('www.reddit.com', ['data' => ['children' => [
        ['data' => ['score' => 10]],
        ['data' => ['score' => 5]],
        ['data' => ['title' => 'no score']],
    ]]]);
    $source = new RedditSource($http);

    expect($source->count(COUNT_URL))->toBe(15)
        ->and($source->network())->toBe('reddit')
        ->and($source->host())->toBe('www.reddit.com')
        ->and($http->requests[0]['url'])->toBe('https://www.reddit.com/api/info.json?url='.COUNT_URL_ENCODED)
        ->and($http->requests[0]['headers'])->toBe(['User-Agent' => 'Sharely/1.0 (TemeCMS plugin)']);
});

it('reads Reddit edge cases', function (mixed $data, ?int $expected): void {
    $http = (new FakeHttp)->answerJson('www.reddit.com', $data);

    expect((new RedditSource($http))->count(COUNT_URL))->toBe($expected);
})->with([
    'no posts' => [['data' => ['children' => []]], 0],
    'negative sum clamped' => [['data' => ['children' => [['data' => ['score' => -4]], ['data' => ['score' => 1]]]]], 0],
    'no children' => [['data' => []], null],
    'children not a list' => [['data' => ['children' => 'x']], null],
    'not an object' => ['hello', null],
]);

// Hacker News

it('asks Hacker News with the address encoded and sums points on matching stories only', function (): void {
    $http = (new FakeHttp)->answerJson('hn.algolia.com', ['hits' => [
        ['url' => 'https://example.com/roses/?a=1&b=2', 'points' => 10],
        ['url' => 'http://example.com/roses?a=1&b=2', 'points' => 4],
        ['url' => 'https://example.com/roses/?a=1&b=2', 'points' => 1],
        ['url' => 'https://example.com/roses/other', 'points' => 100],
        ['url' => null, 'points' => 50],
        ['title' => 'no url', 'points' => 50],
    ]]);
    $source = new HackerNewsSource($http);

    expect($source->count(COUNT_URL))->toBe(15)
        ->and($source->network())->toBe('hackernews')
        ->and($source->host())->toBe('hn.algolia.com')
        ->and($http->requests[0]['url'])->toBe('https://hn.algolia.com/api/v1/search?restrictSearchableAttributes=url&hitsPerPage=50&query='.COUNT_URL_ENCODED);
});

it('matches Hacker News stories ignoring scheme and a trailing slash both ways', function (): void {
    $http = (new FakeHttp)->answerJson('hn.algolia.com', ['hits' => [
        ['url' => 'https://example.com/a/', 'points' => 2],
        ['url' => 'http://example.com/a', 'points' => 3],
    ]]);

    expect((new HackerNewsSource($http))->count('http://example.com/a'))->toBe(5);
});

it('reads Hacker News edge cases', function (mixed $data, ?int $expected): void {
    $http = (new FakeHttp)->answerJson('hn.algolia.com', $data);

    expect((new HackerNewsSource($http))->count(COUNT_URL))->toBe($expected);
})->with([
    'no hits' => [['hits' => []], 0],
    'no hits key' => [['nbHits' => 0], null],
    'not an object' => [[1, 2], null],
]);

// What every source shares

function everySource(FakeHttp $http): array
{
    return [
        new FacebookSource($http, FB_TOKEN),
        new PinterestSource($http),
        new RedditSource($http),
        new HackerNewsSource($http),
    ];
}

it('answers null for a status other than 200', function (int $status): void {
    $http = new FakeHttp;

    foreach (everySource($http) as $source) {
        $http->answer($source->host(), $status, '{"count":3,"hits":[],"data":{"children":[]},"engagement":{"share_count":1}}');
        expect($source->count(COUNT_URL))->toBeNull();
    }
})->with([201, 301, 404, 429, 500]);

it('answers null for a malformed body', function (): void {
    $http = new FakeHttp;

    foreach (everySource($http) as $source) {
        $http->answer($source->host(), 200, '{not json');
        expect($source->count(COUNT_URL))->toBeNull();
    }
});

it('answers null when the door throws', function (Throwable $error): void {
    $http = new FakeHttp;

    foreach (everySource($http) as $source) {
        $http->fail($source->host(), $error);
        expect($source->count(COUNT_URL))->toBeNull();
    }
})->with([
    'refused' => [new HostRefused('not allowed')],
    'failed' => [new RequestFailed('timed out')],
    'too large' => [new AnswerTooLarge('too big')],
]);

it('is a CountSource each', function (): void {
    expect(everySource(new FakeHttp))->each->toBeInstanceOf(CountSource::class);
});

// Sources::for

it('builds the chosen sources in order, skipping unknown ids and repeats', function (): void {
    $sources = Sources::for(['reddit', 'x', 'facebook', 'hackernews', 'reddit', 'pinterest'], new FakeHttp, FB_TOKEN);

    expect(array_map(static fn (CountSource $s): string => $s->network(), $sources))
        ->toBe(['reddit', 'facebook', 'hackernews', 'pinterest'])
        ->and(array_is_list($sources))->toBeTrue();
});

it('leaves Facebook out without a token', function (?string $token): void {
    $sources = Sources::for(['facebook', 'pinterest'], new FakeHttp, $token);

    expect(array_map(static fn (CountSource $s): string => $s->network(), $sources))->toBe(['pinterest']);
})->with([null, '', '  ']);
