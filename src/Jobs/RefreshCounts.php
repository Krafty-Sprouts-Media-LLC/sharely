<?php

/**
 * Asks the networks the administrator chose for each published article's
 * share count, across every address the article has had (old addresses,
 * the http and https twins, the old domain), and keeps the larger of the
 * stored and the new sum.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Jobs;

use KraftySprouts\Sharely\Counts\Recovery;
use KraftySprouts\Sharely\Counts\Sources;
use KraftySprouts\Sharely\Sharely;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Store\Counts;
use KraftySprouts\Sharely\Store\Items;
use Teme\Contracts\Http\OutboundHttp;
use Teme\Contracts\Jobs\JobContext;
use Teme\Contracts\Jobs\JobHandler;
use Teme\Contracts\Settings\SecretReader;
use Teme\Contracts\Settings\SettingsReader;

/**
 * Asks the networks the administrator chose for each published article's
 * share count, across every address the article has had (old addresses,
 * the http and https twins, the old domain), and keeps the larger of the
 * stored and the new sum. Never runs while a page loads: only from its
 * schedule or the settings page's button. Takes the articles checked
 * longest ago first, a batch a run.
 *
 * @since 1.0.0
 */
final readonly class RefreshCounts implements JobHandler
{
    /** The most articles one run asks about. @since 1.0.0 */
    public const int BATCH = 150;

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

        if (! $config->fetchCounts || $config->countNetworks === []) {
            $context->progress(0, 0, __('Asking networks for counts is off.'));

            return;
        }

        $sources = Sources::for($config->countNetworks, $this->http, $this->secrets->secret(Sharely::GROUP, 'facebook_token'));
        $items = new Items;
        $counts = new Counts;
        $due = $items->dueForCounts(self::BATCH);

        foreach ($due as $i => $row) {
            $addresses = Recovery::addresses((string) $row->url, $items->addresses((string) $row->id), $config->oldDomain);

            foreach ($sources as $source) {
                $sum = null;

                foreach ($addresses as $address) {
                    $n = $source->count($address);

                    if ($n !== null) {
                        $sum = ($sum ?? 0) + $n;
                    }
                }

                if ($sum !== null) {
                    $counts->reported((string) $row->id, $source->network(), $sum);
                }
            }

            $counts->checked((string) $row->id);
            $context->progress($i + 1, count($due), __('Counts checked for :done of :total articles', ['done' => $i + 1, 'total' => count($due)]));
        }
    }
}
