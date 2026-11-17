<?php

/**
 * Pinterest's count of pins for an address, from its widgets count, which
 * may answer JSONP (`receiveCount({...})`) or plain JSON.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Counts\Sources;

use KraftySprouts\Sharely\Counts\CountSource;
use Teme\Contracts\Http\OutboundHttp;

/**
 * Pinterest's count of pins for an address, from its widgets count, which may
 * answer JSONP (`receiveCount({...})`) or plain JSON.
 *
 * @since 1.0.0
 */
final readonly class PinterestSource implements CountSource
{
    /** The widgets count address. @since 1.0.0 */
    public const string ENDPOINT = 'https://widgets.pinterest.com/v1/urls/count.json';

    /** @since 1.0.0 */
    public function __construct(private OutboundHttp $http) {}

    /** @since 1.0.0 */
    public function network(): string
    {
        return 'pinterest';
    }

    /** @since 1.0.0 */
    public function host(): string
    {
        return 'widgets.pinterest.com';
    }

    /** @since 1.0.0 */
    public function count(string $url): ?int
    {
        $data = Fetch::object($this->http, self::ENDPOINT.'?'.Fetch::query(['source' => '6', 'url' => $url]), jsonp: true);
        $count = Fetch::int($data['count'] ?? null);

        return $count === null ? null : max(0, $count);
    }
}
