<?php

/**
 * Saves the options the editor's Sharing panel sends for one item: share
 * buttons off, the words to post, and the Pinterest description and image.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Jobs;

use KraftySprouts\Sharely\Store\Items;
use Teme\Contracts\Content\ContentReader;
use Teme\Contracts\Jobs\JobContext;
use Teme\Contracts\Jobs\JobHandler;

/**
 * Saves the options the editor's Sharing panel sends for one item: share
 * buttons off, the words to post, and the Pinterest description and image.
 * It first reads the item's draft as the person, so only someone who may
 * edit the item can change them.
 *
 * @since 1.0.0
 */
final readonly class PanelOptions implements JobHandler
{
    /** @since 1.0.0 */
    public function __construct(private ContentReader $content) {}

    /** @since 1.0.0 */
    public function handle(JobContext $context): void
    {
        $payload = $context->payload();
        $itemId = (string) ($payload['itemId'] ?? '');
        $values = Payload::values($payload);

        // Throws ReadRefused for someone who may not edit the item.
        $this->content->draft($itemId, $context->personId());

        $image = trim((string) ($values['pin_image'] ?? ''));

        if ($image !== '' && preg_match('#^https://[^\s]+$#i', $image) !== 1) {
            $image = '';
        }

        (new Items)->options(
            $itemId,
            (bool) ($values['off'] ?? false),
            isset($values['share_text']) ? mb_substr((string) $values['share_text'], 0, 280) : null,
            isset($values['pin_description']) ? mb_substr((string) $values['pin_description'], 0, 500) : null,
            $image,
        );

        $context->progress(1, 1, __('Sharing options saved.'));
    }
}
