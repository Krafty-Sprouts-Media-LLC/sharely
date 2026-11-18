<?php

/**
 * Rate-limits click counting, so repeated requests can't inflate a count,
 * and signs the Mastodon continue link, so a crafted link can't skip the
 * page that names the server.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Routes;

use Throwable;

/**
 * Rate-limits click counting, so repeated requests can't inflate a count,
 * and signs the Mastodon continue link, so a crafted link can't skip the
 * page that names the server. Neither stores anything about a reader: a
 * click's key is a keyed hash of the item and the address under a salt that
 * turns over daily, kept for ten minutes and never readable back; a
 * token is a keyed hash of the item and the server. When the cache is down
 * nothing is counted and no token is valid: the share itself still works.
 *
 * @since 1.0.0
 */
final readonly class Guard
{
    /** How long one address's click on one item is remembered. @since 1.0.0 */
    public const int WINDOW_SECONDS = 600;

    /** @since 1.0.0 */
    public function __construct(private Keys $keys) {}

    /**
     * Whether this click should be counted: the first from that address on
     * that item in the window.
     *
     * @since 1.0.0
     */
    public function allowClick(string $itemId, string $address): bool
    {
        try {
            $key = 'sharely:click:'.hash_hmac('sha256', $itemId."\n".$address, $this->keys->salt());

            return $this->keys->add($key, self::WINDOW_SECONDS);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * The token the continue link carries for that item and server.
     *
     * @since 1.0.0
     */
    public function token(string $itemId, string $server): string
    {
        try {
            return substr(hash_hmac('sha256', 'mastodon'."\n".$itemId."\n".$server, $this->keys->salt()), 0, 32);
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Whether the token is the one made for that item and server today.
     *
     * @since 1.0.0
     */
    public function validToken(string $itemId, string $server, string $token): bool
    {
        $expected = $this->token($itemId, $server);

        return $expected !== '' && hash_equals($expected, $token);
    }
}
