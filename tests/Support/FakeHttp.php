<?php

/**
 * A stand-in for Teme's outbound door in unit tests: it answers from a
 * queue kept per host, records every request, and can throw a chosen
 * exception for a host.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Tests\Support;

use LogicException;
use Teme\Contracts\Http\HttpAnswer;
use Teme\Contracts\Http\OutboundHttp;
use Throwable;

/**
 * A stand-in for Teme's outbound door in unit tests: it answers from a queue
 * kept per host, records every request, and can throw a chosen exception for
 * a host. A request to a host with nothing queued is a test failure.
 *
 * @since 1.0.0
 */
final class FakeHttp implements OutboundHttp
{
    /**
     * Every request sent, in order.
     *
     * @var list<array{method: string, url: string, headers: array<string, string>, body: ?string}>
     *
     * @since 1.0.0
     */
    public array $requests = [];

    /** @var array<string, list<HttpAnswer>> */
    private array $answers = [];

    /** @var array<string, Throwable> */
    private array $failures = [];

    /**
     * Queue an answer for the next request to that host.
     *
     * @param  array<string, list<string>>  $headers
     *
     * @since 1.0.0
     */
    public function answer(string $host, int $status, string $body, array $headers = []): self
    {
        $this->answers[$host][] = new HttpAnswer($status, $body, $headers);

        return $this;
    }

    /**
     * Queue a JSON answer for the next request to that host.
     *
     * @since 1.0.0
     */
    public function answerJson(string $host, mixed $data, int $status = 200): self
    {
        return $this->answer($host, $status, (string) json_encode($data), ['Content-Type' => ['application/json']]);
    }

    /**
     * Throw this exception for every request to that host.
     *
     * @since 1.0.0
     */
    public function fail(string $host, Throwable $error): self
    {
        $this->failures[$host] = $error;

        return $this;
    }

    /** @since 1.0.0 */
    public function get(string $url, array $headers = [], int $timeoutSeconds = 5): HttpAnswer
    {
        return $this->send('GET', $url, $headers, null);
    }

    /** @since 1.0.0 */
    public function postJson(string $url, array $json, array $headers = [], int $timeoutSeconds = 5): HttpAnswer
    {
        return $this->send('POST', $url, $headers, (string) json_encode($json));
    }

    /** @since 1.0.0 */
    public function postForm(string $url, array $fields, array $headers = [], int $timeoutSeconds = 5): HttpAnswer
    {
        return $this->send('POST', $url, $headers, http_build_query($fields, '', '&', PHP_QUERY_RFC3986));
    }

    /**
     * Record the request, then throw or answer for its host.
     *
     * @param  array<string, string>  $headers
     *
     * @since 1.0.0
     */
    private function send(string $method, string $url, array $headers, ?string $body): HttpAnswer
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];
        $host = (string) parse_url($url, PHP_URL_HOST);

        if (isset($this->failures[$host])) {
            throw $this->failures[$host];
        }

        if (($this->answers[$host] ?? []) === []) {
            throw new LogicException('FakeHttp has no answer queued for '.$host);
        }

        return array_shift($this->answers[$host]);
    }
}
