<?php

/**
 * Tests for recovery.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Counts\Recovery;

it('asks about the current address and its http twin when nothing else is known', function (): void {
    expect(Recovery::addresses('https://example.com/roses/', [], ''))
        ->toBe(['https://example.com/roses/', 'http://example.com/roses/']);
});

it('adds older addresses and their twins, current first, without repeats', function (): void {
    expect(Recovery::addresses('https://example.com/roses/', [
        'http://example.com/feeding-roses/',
        'https://example.com/roses/',
        'http://example.com/feeding-roses/',
    ], ''))->toBe([
        'https://example.com/roses/',
        'http://example.com/feeding-roses/',
        'http://example.com/roses/',
        'https://example.com/feeding-roses/',
    ]);
});

it('swaps in the old domain, its scheme and port, keeping path, query and fragment', function (): void {
    expect(Recovery::addresses('https://example.com/roses/?a=1#top', ['https://example.com/old/'], 'http://old.example.com:8080/'))
        ->toBe([
            'https://example.com/roses/?a=1#top',
            'https://example.com/old/',
            'http://example.com/roses/?a=1#top',
            'http://example.com/old/',
            'http://old.example.com:8080/roses/?a=1#top',
            'http://old.example.com:8080/old/',
        ]);
});

it('keeps an old-domain address without a twin of its own', function (): void {
    expect(Recovery::addresses('https://example.com/', [], 'https://old.example.com'))
        ->toBe(['https://example.com/', 'http://example.com/', 'https://old.example.com/']);
});

it('ignores an old domain that is not an http address', function (string $old): void {
    expect(Recovery::addresses('https://example.com/a', [], $old))
        ->toBe(['https://example.com/a', 'http://example.com/a']);
})->with(['', '   ', 'old.example.com', 'ftp://old.example.com', 'https://']);

it('skips blank known addresses and gives no twin to one that is not http', function (): void {
    expect(Recovery::addresses('https://example.com/a', ['', '  ', 'mailto:x@example.com'], ''))
        ->toBe(['https://example.com/a', 'mailto:x@example.com', 'http://example.com/a']);
});

it('asks about at most 12 addresses, the current one first', function (): void {
    $known = array_map(static fn (int $i): string => 'https://example.com/old-'.$i.'/', range(1, 20));
    $addresses = Recovery::addresses('https://example.com/now/', $known, 'https://old.example.com');

    expect($addresses)->toHaveCount(12)
        ->and($addresses[0])->toBe('https://example.com/now/')
        ->and(array_unique($addresses))->toBe($addresses)
        ->and(array_is_list($addresses))->toBeTrue();
});
