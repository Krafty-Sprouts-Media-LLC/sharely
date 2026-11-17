<?php

/**
 * Tests for bitly.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Links\Bitly;
use KraftySprouts\Sharely\Tests\Support\FakeHttp;
use Teme\Contracts\Http\AnswerTooLarge;
use Teme\Contracts\Http\HostRefused;
use Teme\Contracts\Http\RequestFailed;

const BITLY_TOKEN = 'test-bitly-token-123';

it('posts the long address as JSON with the bearer token and answers the link', function (): void {
    $http = (new FakeHttp)->answerJson('api-ssl.bitly.com', ['link' => 'https://bit.ly/3abcDEF', 'id' => 'bit.ly/3abcDEF'], 201);

    expect((new Bitly($http, BITLY_TOKEN))->shorten('https://example.com/roses/?a=1&b=2'))->toBe('https://bit.ly/3abcDEF')
        ->and($http->requests)->toHaveCount(1)
        ->and($http->requests[0]['method'])->toBe('POST')
        ->and($http->requests[0]['url'])->toBe('https://api-ssl.bitly.com/v4/shorten')
        ->and(json_decode((string) $http->requests[0]['body'], true))->toBe(['long_url' => 'https://example.com/roses/?a=1&b=2'])
        ->and($http->requests[0]['headers'])->toBe(['Authorization' => 'Bearer '.BITLY_TOKEN]);
});

it('takes a 200 answer for a link Bitly already had', function (): void {
    $http = (new FakeHttp)->answerJson('api-ssl.bitly.com', ['link' => 'https://bit.ly/xyz'], 200);

    expect((new Bitly($http, BITLY_TOKEN))->shorten('https://example.com/'))->toBe('https://bit.ly/xyz');
});

it('answers null for a bad answer', function (int $status, string $body): void {
    $http = (new FakeHttp)->answer('api-ssl.bitly.com', $status, $body);

    expect((new Bitly($http, BITLY_TOKEN))->shorten('https://example.com/'))->toBeNull();
})->with([
    'forbidden' => [403, '{"message":"FORBIDDEN"}'],
    'rate limited' => [429, '{"link":"https://bit.ly/xyz"}'],
    'server error' => [500, ''],
    'malformed' => [200, '{"link":'],
    'no link' => [200, '{"id":"bit.ly/xyz"}'],
    'link not a string' => [201, '{"link":42}'],
    'link not an address' => [201, '{"link":"javascript:alert(1)"}'],
    'not an object' => [200, '"https://bit.ly/xyz"'],
]);

it('answers null when the door throws', function (Throwable $error): void {
    $http = (new FakeHttp)->fail('api-ssl.bitly.com', $error);

    expect((new Bitly($http, BITLY_TOKEN))->shorten('https://example.com/'))->toBeNull();
})->with([
    'refused' => [new HostRefused('not allowed')],
    'failed' => [new RequestFailed('timed out')],
    'too large' => [new AnswerTooLarge('too big')],
]);

it('sends nothing without a token or an address', function (string $token, string $url): void {
    $http = new FakeHttp;

    expect((new Bitly($http, $token))->shorten($url))->toBeNull()
        ->and($http->requests)->toBe([]);
})->with([
    'no token' => ['', 'https://example.com/'],
    'blank token' => ['  ', 'https://example.com/'],
    'no address' => [BITLY_TOKEN, ''],
]);

it('never answers anything holding the token', function (): void {
    $http = (new FakeHttp)->answerJson('api-ssl.bitly.com', ['link' => 'https://bit.ly/'.BITLY_TOKEN], 201);

    expect((new Bitly($http, BITLY_TOKEN))->shorten('https://example.com/'))->toBeNull();
});

it('keeps the token out of a dump of itself', function (): void {
    $bitly = new Bitly(new FakeHttp, BITLY_TOKEN);

    ob_start();
    var_dump($bitly);
    $dump = (string) ob_get_clean();

    expect(print_r($bitly, true))->not->toContain(BITLY_TOKEN)
        ->and($dump)->not->toContain(BITLY_TOKEN);
});
