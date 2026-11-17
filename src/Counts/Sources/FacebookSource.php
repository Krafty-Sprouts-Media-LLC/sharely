<?php

/**
 * Facebook's count for an address: shares, reactions and comments, from
 * the Graph API's `engagement` field.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Counts\Sources;

use KraftySprouts\Sharely\Counts\CountSource;
use SensitiveParameter;
use Teme\Contracts\Http\OutboundHttp;

/**
 * Facebook's count for an address: shares, reactions and comments, from the
 * Graph API's `engagement` field. It needs an app token (`appid|secret`);
 * without one it asks nothing.
 *
 * @since 1.0.0
 */
final class FacebookSource implements CountSource
{
    /** The Graph API address, its version fixed. @since 1.0.0 */
    public const string ENDPOINT = 'https://graph.facebook.com/v21.0/';

    /** The engagement fields added together. @since 1.0.0 */
    public const array FIELDS = ['share_count', 'reaction_count', 'comment_count'];

    /** @since 1.0.0 */
    public function __construct(
        private readonly OutboundHttp $http,
        #[SensitiveParameter] private readonly string $token,
    ) {}

    /** @since 1.0.0 */
    public function network(): string
    {
        return 'facebook';
    }

    /** @since 1.0.0 */
    public function host(): string
    {
        return 'graph.facebook.com';
    }

    /** @since 1.0.0 */
    public function count(string $url): ?int
    {
        $token = trim($this->token);

        if ($token === '') {
            return null;
        }

        // The token goes in a header, never the address, so no log of the request holds it.
        $data = Fetch::object($this->http, self::ENDPOINT.'?'.Fetch::query([
            'id' => $url,
            'fields' => 'engagement',
        ]), ['Authorization' => 'Bearer '.$token]);
        $engagement = $data['engagement'] ?? null;

        if (! is_array($engagement)) {
            return null;
        }

        $sum = null;

        foreach (self::FIELDS as $field) {
            $value = Fetch::int($engagement[$field] ?? null);

            if ($value !== null) {
                $sum = ($sum ?? 0) + $value;
            }
        }

        return $sum === null ? null : max(0, $sum);
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
        return ['network' => $this->network()];
    }
}
