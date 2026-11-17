<?php

/**
 * Short links from Bitly's v4 API, with the site's own access token.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Links;

use JsonException;
use SensitiveParameter;
use Teme\Contracts\Http\AnswerTooLarge;
use Teme\Contracts\Http\HostRefused;
use Teme\Contracts\Http\OutboundHttp;
use Teme\Contracts\Http\RequestFailed;

/**
 * Short links from Bitly's v4 API, with the site's own access token. Any
 * failure answers null, so the long address is shared instead; the token is
 * never put in anything this class answers, throws or dumps.
 *
 * @since 1.0.0
 */
final class Bitly
{
    /** The shorten address. @since 1.0.0 */
    public const string ENDPOINT = 'https://api-ssl.bitly.com/v4/shorten';

    /** The host the http capability must allow. @since 1.0.0 */
    public const string HOST = 'api-ssl.bitly.com';

    /** @since 1.0.0 */
    public function __construct(
        private readonly OutboundHttp $http,
        #[SensitiveParameter] private readonly string $token,
    ) {}

    /**
     * The short link for that address, or null when there is no token or
     * Bitly gave none.
     *
     * @since 1.0.0
     */
    public function shorten(string $longUrl): ?string
    {
        $token = trim($this->token);
        $longUrl = trim($longUrl);

        if ($token === '' || $longUrl === '') {
            return null;
        }

        try {
            $answer = $this->http->postJson(self::ENDPOINT, ['long_url' => $longUrl], ['Authorization' => 'Bearer '.$token]);
        } catch (HostRefused|RequestFailed|AnswerTooLarge) {
            return null;
        }

        if ($answer->status !== 200 && $answer->status !== 201) {
            return null;
        }

        try {
            $data = json_decode($answer->body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        $link = is_array($data) ? ($data['link'] ?? null) : null;

        if (! is_string($link) || preg_match('#^https?://[^\s/]+/\S*$#i', $link) !== 1 || str_contains($link, $token)) {
            return null;
        }

        return $link;
    }

    /**
     * What a dump shows, without the token.
     *
     * @return array<string, string>
     *
     * @since 1.0.0
     */
    public function __debugInfo(): array
    {
        return ['endpoint' => self::ENDPOINT];
    }
}
