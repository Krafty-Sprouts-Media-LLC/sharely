<?php

/**
 * Reads the item's draft as the person who asked, works out how it will
 * look on each network the site shares to, and leaves the result for the
 * Sharing panel to show.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Jobs;

use KraftySprouts\Sharely\Admin\Preview;
use KraftySprouts\Sharely\Sharely;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Store\State;
use Teme\Contracts\Content\ContentReader;
use Teme\Contracts\Jobs\JobContext;
use Teme\Contracts\Jobs\JobHandler;
use Teme\Contracts\Settings\SettingsReader;

/**
 * Reads the item's draft as the person who asked, works out how it will
 * look on each network the site shares to, and leaves the result for the
 * Sharing panel to show. A panel's view must answer from what a job
 * already worked out, never read content itself.
 *
 * @since 1.0.0
 */
final readonly class PanelPreview implements JobHandler
{
    /** @since 1.0.0 */
    public function __construct(
        private ContentReader $content,
        private SettingsReader $settings,
    ) {}

    /** @since 1.0.0 */
    public function handle(JobContext $context): void
    {
        $itemId = (string) ($context->payload()['itemId'] ?? '');
        $draft = $this->content->draft($itemId, $context->personId());
        $config = Config::fromSettings($this->settings->group(Sharely::GROUP));
        $lines = Preview::lines($draft->fields, $draft->item->title, $config->allNetworks());

        (new State)->put('preview:'.$itemId, [
            'revision' => $draft->revisionId,
            'lines' => $lines,
        ]);

        $context->progress(1, 1, __('Preview ready for :count networks.', ['count' => count($lines)]));
    }
}
