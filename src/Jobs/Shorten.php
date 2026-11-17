<?php

/**
 * Makes a short link for each published article that has none, or whose
 * address changed since its link was made, with the service the
 * administrator connected (Bitly today).
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Jobs;

use KraftySprouts\Sharely\Links\Bitly;
use KraftySprouts\Sharely\Sharely;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Store\Items;
use Teme\Contracts\Http\OutboundHttp;
use Teme\Contracts\Jobs\JobContext;
use Teme\Contracts\Jobs\JobHandler;
use Teme\Contracts\Settings\SecretReader;
use Teme\Contracts\Settings\SettingsReader;

/**
 * Makes a short link for each published article that has none, or whose
 * address changed since its link was made, with the service the
 * administrator connected (Bitly today). Each address is sent once.
 *
 * @since 1.0.0
 */
final readonly class Shorten implements JobHandler
{
    /** The most links one run makes. @since 1.0.0 */
    public const int BATCH = 50;

    /** @since 1.0.0 */
    public function __construct(
        private SettingsReader $settings,
        private SecretReader $secrets,
        private OutboundHttp $http,
    ) {}

    /** @since 1.0.0 */
    public function handle(JobContext $context): void
    {
        $config = Config::fromSettings($this->settings->group(Sharely::GROUP));
        $token = $this->secrets->secret(Sharely::GROUP, 'bitly_token');

        if ($config->shortener !== 'bitly' || $token === null || trim($token) === '') {
            $context->progress(0, 0, __('No link shortener is connected.'));

            return;
        }

        $bitly = new Bitly($this->http, $token);
        $items = new Items;
        $due = $items->dueForShortening(self::BATCH);
        $made = 0;

        foreach ($due as $i => $row) {
            $short = $bitly->shorten((string) $row->url);

            if ($short !== null) {
                $items->shortened((string) $row->id, (string) $row->url, $short);
                $made++;
            }

            $context->progress($i + 1, count($due), __(':count short links made', ['count' => $made]));
        }
    }
}
