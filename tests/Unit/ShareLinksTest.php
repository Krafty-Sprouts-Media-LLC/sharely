<?php

declare(strict_types=1);

use KraftySprouts\Sharely\Links\ShareLinks;
use KraftySprouts\Sharely\Links\Utm;
use KraftySprouts\Sharely\Networks\Networks;
use KraftySprouts\Sharely\Sharing\SharedItem;

function item(array $over = []): SharedItem
{
    return new SharedItem(...array_merge([
        'id' => '01jabc',
        'url' => 'https://example.com/roses/',
        'title' => 'Feeding roses & more',
        'description' => 'When and how to feed them.',
        'image' => 'https://example.com/media/rose.jpg',
    ], $over));
}

it('knows every network the brief names, in a stable order', function (): void {
    $ids = array_keys(Networks::all());

    expect($ids)->toContain(
        'x', 'facebook', 'linkedin', 'whatsapp', 'telegram', 'reddit', 'pinterest', 'bluesky', 'threads',
        'mastodon', 'flipboard', 'tumblr', 'buffer', 'line', 'messenger', 'vk', 'hackernews',
        'email', 'sms', 'print', 'copy', 'sheet',
    );
});

it('builds plain share addresses with the address and title encoded', function (string $network, string $expected): void {
    $links = new ShareLinks(Utm::off());

    expect($links->href($network, item()))->toBe($expected);
})->with([
    ['x', 'https://x.com/intent/post?text=Feeding%20roses%20%26%20more&url=https%3A%2F%2Fexample.com%2Froses%2F'],
    ['facebook', 'https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fexample.com%2Froses%2F'],
    ['linkedin', 'https://www.linkedin.com/sharing/share-offsite/?url=https%3A%2F%2Fexample.com%2Froses%2F'],
    ['bluesky', 'https://bsky.app/intent/compose?text=Feeding%20roses%20%26%20more%20https%3A%2F%2Fexample.com%2Froses%2F'],
    ['threads', 'https://www.threads.com/intent/post?text=Feeding%20roses%20%26%20more%20https%3A%2F%2Fexample.com%2Froses%2F'],
    ['whatsapp', 'https://wa.me/?text=Feeding%20roses%20%26%20more%20https%3A%2F%2Fexample.com%2Froses%2F'],
    ['telegram', 'https://t.me/share/url?url=https%3A%2F%2Fexample.com%2Froses%2F&text=Feeding%20roses%20%26%20more'],
    ['reddit', 'https://www.reddit.com/submit?url=https%3A%2F%2Fexample.com%2Froses%2F&title=Feeding%20roses%20%26%20more'],
    ['hackernews', 'https://news.ycombinator.com/submitlink?u=https%3A%2F%2Fexample.com%2Froses%2F&t=Feeding%20roses%20%26%20more'],
    ['email', 'mailto:?subject=Feeding%20roses%20%26%20more&body=When%20and%20how%20to%20feed%20them.%0A%0Ahttps%3A%2F%2Fexample.com%2Froses%2F'],
    ['sms', 'sms:?&body=Feeding%20roses%20%26%20more%20https%3A%2F%2Fexample.com%2Froses%2F'],
]);

it('adds the X account as via, without its @', function (): void {
    $links = new ShareLinks(Utm::off(), xAccount: '@kraftysprouts');

    expect($links->href('x', item()))->toEndWith('&via=kraftysprouts');
});

it('gives Pinterest the chosen pin image and description before the social ones', function (): void {
    $links = new ShareLinks(Utm::off());

    expect($links->href('pinterest', item()))
        ->toBe('https://www.pinterest.com/pin/create/button/?url=https%3A%2F%2Fexample.com%2Froses%2F&media=https%3A%2F%2Fexample.com%2Fmedia%2Frose.jpg&description=Feeding%20roses%20%26%20more')
        ->and($links->href('pinterest', item(['pinImage' => 'https://example.com/media/pin.jpg', 'pinDescription' => 'Pin me'])))
        ->toContain('media=https%3A%2F%2Fexample.com%2Fmedia%2Fpin.jpg')
        ->toContain('description=Pin%20me');
});

it('uses the item’s own share text for the networks that post words', function (): void {
    $links = new ShareLinks(Utm::off());

    expect($links->href('bluesky', item(['shareText' => 'Read this'])))
        ->toBe('https://bsky.app/intent/compose?text=Read%20this%20https%3A%2F%2Fexample.com%2Froses%2F');
});

it('answers null for networks that need a script or Sharely’s own page', function (string $network): void {
    expect((new ShareLinks(Utm::off()))->href($network, item()))->toBeNull();
})->with(['print', 'copy', 'sheet', 'mastodon']);

it('tags the shared address with UTM values, the network filling {network}', function (): void {
    $links = new ShareLinks(new Utm(true, '{network}', 'social', 'sharely'));

    expect($links->href('facebook', item()))
        ->toBe('https://www.facebook.com/sharer/sharer.php?u='.rawurlencode('https://example.com/roses/?utm_source=facebook&utm_medium=social&utm_campaign=sharely'));
});

it('keeps an address’s own query and fragment when tagging', function (): void {
    $utm = new Utm(true, 'sharely', '{network}', '');

    expect($utm->apply('https://example.com/a/?p=2#top', 'x'))
        ->toBe('https://example.com/a/?p=2&utm_source=sharely&utm_medium=x#top');
});

it('prefers the short address when one was made, and then adds no UTM tags', function (): void {
    $links = new ShareLinks(new Utm(true, '{network}', 'social', 'sharely'));

    expect($links->href('facebook', item(['shortUrl' => 'https://bit.ly/abc'])))
        ->toBe('https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fbit.ly%2Fabc');
});
