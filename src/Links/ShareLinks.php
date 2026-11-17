<?php

declare(strict_types=1);

namespace KraftySprouts\Sharely\Links;

use KraftySprouts\Sharely\Sharing\SharedItem;

/**
 * Builds each network's own share address for an item: the plain link a
 * button opens with no script at all.
 *
 * @since 1.0.0
 */
final readonly class ShareLinks
{
    /**
     * @param  Utm  $utm  the tags added to the shared address
     * @param  string  $xAccount  the site's X account, named as "via" on X
     *
     * @since 1.0.0
     */
    public function __construct(
        private Utm $utm,
        private string $xAccount = '',
    ) {}

    /**
     * The address shared to that network: the short address when one was
     * made, else the item's address with the UTM tags for that network.
     *
     * @since 1.0.0
     */
    public function sharedUrl(string $network, SharedItem $item): string
    {
        if ($item->shortUrl !== null && $item->shortUrl !== '') {
            return $item->shortUrl;
        }

        return $this->utm->apply($item->url, $network);
    }

    /**
     * The network's share address, or null for a network whose button needs
     * a script or Sharely's own page.
     *
     * @since 1.0.0
     */
    public function href(string $network, SharedItem $item, ?string $words = null): ?string
    {
        $url = $this->sharedUrl($network, $item);
        $text = $words ?? $item->words();
        $withUrl = $text.' '.$url;

        return match ($network) {
            'x' => $this->build('https://x.com/intent/post', ['text' => $text, 'url' => $url, 'via' => ltrim(trim($this->xAccount), '@')]),
            'facebook' => $this->build('https://www.facebook.com/sharer/sharer.php', ['u' => $url]),
            'linkedin' => $this->build('https://www.linkedin.com/sharing/share-offsite/', ['url' => $url]),
            'bluesky' => $this->build('https://bsky.app/intent/compose', ['text' => $withUrl]),
            'threads' => $this->build('https://www.threads.com/intent/post', ['text' => $withUrl]),
            'whatsapp' => $this->build('https://wa.me/', ['text' => $withUrl]),
            'telegram' => $this->build('https://t.me/share/url', ['url' => $url, 'text' => $text]),
            'reddit' => $this->build('https://www.reddit.com/submit', ['url' => $url, 'title' => $text]),
            'pinterest' => $this->build('https://www.pinterest.com/pin/create/button/', [
                'url' => $url,
                'media' => (string) ($item->pinImage ?? $item->image),
                'description' => (string) ($item->pinDescription ?: $text),
            ]),
            'flipboard' => $this->build('https://share.flipboard.com/bookmarklet/popout', ['v' => '2', 'url' => $url, 'title' => $text]),
            'tumblr' => $this->build('https://www.tumblr.com/widgets/share/tool', ['canonicalUrl' => $url, 'title' => $text, 'caption' => $item->description]),
            'buffer' => $this->build('https://buffer.com/add', ['url' => $url, 'text' => $text]),
            'line' => $this->build('https://social-plugins.line.me/lineit/share', ['url' => $url, 'text' => $text]),
            'messenger' => $this->build('fb-messenger://share/', ['link' => $url]),
            'vk' => $this->build('https://vk.com/share.php', ['url' => $url, 'title' => $text]),
            'hackernews' => $this->build('https://news.ycombinator.com/submitlink', ['u' => $url, 't' => $text]),
            'email' => $this->build('mailto:', ['subject' => $text, 'body' => trim($item->description."\n\n".$url)]),
            'sms' => 'sms:?&body='.rawurlencode($withUrl),
            default => null,
        };
    }

    /**
     * Joins a base and its parameters, leaving out empty ones, encoded as
     * RFC 3986 asks (spaces as %20, never +).
     *
     * @param  array<string, string>  $params
     *
     * @since 1.0.0
     */
    private function build(string $base, array $params): string
    {
        $params = array_filter($params, static fn (string $v): bool => $v !== '');

        return $base.'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
