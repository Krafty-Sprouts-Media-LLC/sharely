<?php

/**
 * Saves the settings a WordPress import brought from Novashare, as the
 * person who pressed "Apply the Novashare settings": Teme's import doesn't
 * say who it runs for, so it can't save them itself.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Jobs;

use KraftySprouts\Sharely\Import\NovashareImporter;
use KraftySprouts\Sharely\Networks\Networks;
use KraftySprouts\Sharely\Store\State;
use Teme\Contracts\Jobs\JobContext;
use Teme\Contracts\Jobs\JobHandler;
use Teme\Contracts\Settings\PluginSettingsWriter;

/**
 * Saves the settings a WordPress import brought from Novashare, as the
 * person who pressed "Apply the Novashare settings": Teme's import doesn't
 * say who it runs for, so it can't save them itself. What a plugin may not
 * write (the network lists, the follow profiles, the keys) is named in the
 * run's message, for the person to set by hand.
 *
 * @since 1.0.0
 */
final readonly class ApplyImport implements JobHandler
{
    /** @since 1.0.0 */
    public function __construct(private PluginSettingsWriter $writer) {}

    /** @since 1.0.0 */
    public function handle(JobContext $context): void
    {
        $state = new State;
        $pending = $state->get(NovashareImporter::PENDING);

        if (! is_array($pending)) {
            $context->progress(0, 0, __('No Novashare settings are waiting: import the WordPress site first.'));

            return;
        }

        $result = (new NovashareImporter($this->writer))->save((array) ($pending['writable'] ?? []), $context->personId());
        $todo = [];

        foreach ((array) ($pending['kept'] ?? []) as $key => $rows) {
            $names = [];

            foreach ((array) $rows as $row) {
                $names[] = Networks::find((string) ($row['network'] ?? ''))?->name ?? (string) ($row['network'] ?? '');
            }

            $todo[] = match ($key) {
                'follow' => __('follow profiles: :list', ['list' => implode(', ', $names)]),
                default => __('share buttons: :list', ['list' => implode(', ', $names)]),
            };
        }

        foreach ((array) ($pending['secrets'] ?? []) as $secret) {
            $todo[] = $secret === 'bitly_token' ? __('your Bitly access token') : __('your Facebook access token');
        }

        $message = __(':count settings saved.', ['count' => count($result->written)]);

        if ($result->refusal !== '') {
            $message .= ' '.$result->refusal;
        }

        if ($todo !== []) {
            $message .= ' '.__('Set by hand: :list.', ['list' => implode('; ', $todo)]);
        }

        $context->progress(1, 1, mb_substr($message, 0, 1000));
    }
}
