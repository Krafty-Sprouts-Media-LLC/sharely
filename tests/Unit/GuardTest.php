<?php

/**
 * Tests for guard.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Routes\Guard;
use KraftySprouts\Sharely\Tests\Support\ArrayKeys;

it('counts one click per item per address in a window, and no more', function (): void {
    $guard = new Guard(new ArrayKeys);

    expect($guard->allowClick('item-a', '203.0.113.7'))->toBeTrue()
        ->and($guard->allowClick('item-a', '203.0.113.7'))->toBeFalse()
        ->and($guard->allowClick('item-a', '203.0.113.7'))->toBeFalse();
});

it('counts a different item or a different address separately', function (): void {
    $guard = new Guard(new ArrayKeys);

    expect($guard->allowClick('item-a', '203.0.113.7'))->toBeTrue()
        ->and($guard->allowClick('item-b', '203.0.113.7'))->toBeTrue()
        ->and($guard->allowClick('item-a', '203.0.113.8'))->toBeTrue();
});

it('holds the window for ten minutes', function (): void {
    $keys = new ArrayKeys;
    $guard = new Guard($keys);
    $guard->allowClick('item-a', '203.0.113.7');

    expect($keys->ttls)->toBe([Guard::WINDOW_SECONDS])
        ->and(Guard::WINDOW_SECONDS)->toBe(600);
});

it('never stores the address: the key is a keyed hash that holds neither it nor the item', function (): void {
    $keys = new ArrayKeys;
    (new Guard($keys))->allowClick('item-a', '203.0.113.7');

    $stored = implode(' ', array_keys($keys->seen));

    expect($stored)->not->toContain('203.0.113.7')
        ->and($stored)->not->toContain('item-a')
        ->and(array_keys($keys->seen)[0])->toMatch('/^sharely:click:[0-9a-f]{64}$/');
});

it('makes a different key for the same address once the daily salt turns over', function (): void {
    $first = new ArrayKeys('salt-one');
    $second = new ArrayKeys('salt-two');
    (new Guard($first))->allowClick('item-a', '203.0.113.7');
    (new Guard($second))->allowClick('item-a', '203.0.113.7');

    expect(array_keys($first->seen))->not->toBe(array_keys($second->seen));
});

it('counts nothing when the cache is down, so a broken cache can never inflate counts', function (): void {
    $guard = new Guard(new ArrayKeys(broken: true));

    expect($guard->allowClick('item-a', '203.0.113.7'))->toBeFalse();
});

it('signs the Mastodon continue link for one item and one server, and refuses any change', function (): void {
    $guard = new Guard(new ArrayKeys);
    $token = $guard->token('item-a', 'fosstodon.org');

    expect($token)->toMatch('/^[0-9a-f]{32}$/')
        ->and($guard->validToken('item-a', 'fosstodon.org', $token))->toBeTrue()
        ->and($guard->validToken('item-b', 'fosstodon.org', $token))->toBeFalse()
        ->and($guard->validToken('item-a', 'evil.example', $token))->toBeFalse()
        ->and($guard->validToken('item-a', 'fosstodon.org', ''))->toBeFalse()
        ->and($guard->validToken('item-a', 'fosstodon.org', str_repeat('0', 32)))->toBeFalse();
});

it('refuses every token when the cache is down', function (): void {
    $guard = new Guard(new ArrayKeys(broken: true));

    expect($guard->validToken('item-a', 'fosstodon.org', str_repeat('a', 32)))->toBeFalse();
});
