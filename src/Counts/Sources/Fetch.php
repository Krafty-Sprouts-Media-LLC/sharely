<?php

/**
 * The request and reading every count source shares: a GET through Teme's
 * door whose JSON object answer comes back as an array, or null for any
 * failure; and whole numbers read from what a network answered.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Counts\Sources;

use JsonException;
use Teme\Contracts\Http\AnswerTooLarge;
use Teme\Contracts\Http\HostRefused;
use Teme\Contracts\Http\OutboundHttp;
use Teme\Contracts\Http\RequestFailed;

/**
 * The request and reading every count source shares: a GET through Teme's
 * door whose JSON object answer comes back as an array, or null for any
 * failure; and whole numbers read from what a network answered.
 *
 * @since 1.0.0
 */
final class Fetch
{
    /**
     * GET that address and answer its body decoded as a JSON object, or null
     * when the door threw, the status was not 200 or the body was not a JSON
     * object. With $jsonp, a `callback(...)` wrapper is taken off first.
     *
     * @param  array<string, string>  $headers
     * @return array<array-key, mixed>|null
     *
     * @since 1.0.0
     */
    public static function object(OutboundHttp $http, string $url, array $headers = [], bool $jsonp = false): ?array
    {
        try {
            $answer = $http->get($url, $headers);
        } catch (HostRefused|RequestFailed|AnswerTooLarge) {
            return null;
        }

        if ($answer->status !== 200) {
            return null;
        }

        $body = trim($answer->body);

        if ($jsonp && preg_match('/^[A-Za-z_$][\w$.]*\s*\((.*)\)\s*;?$/s', $body, $match) === 1) {
            $body = $match[1];
        }

        try {
            $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * A whole number from an answer: an integer, a finite float, or a string
     * of digits; null for anything else.
     *
     * @since 1.0.0
     */
    public static function int(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) && is_finite($value) && abs($value) < PHP_INT_MAX) {
            return (int) $value;
        }

        if (is_string($value) && preg_match('/^-?\d{1,18}$/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }

    /**
     * The query string of these values, each encoded as RFC 3986 asks.
     *
     * @param  array<string, string>  $values
     *
     * @since 1.0.0
     */
    public static function query(array $values): string
    {
        return http_build_query($values, '', '&', PHP_QUERY_RFC3986);
    }
}
