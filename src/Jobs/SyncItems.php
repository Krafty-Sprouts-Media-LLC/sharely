<?php

/**
 * Brings Sharely's list of published items up to date: reads every
 * published item of the types that get buttons, records its title and its
 * address (keeping the old ones, so counts follow a moved item), and marks
 * as unpublished what is no longer listed.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Jobs;

use Closure;
use KraftySprouts\Sharely\Sharely;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Store\Db;
use KraftySprouts\Sharely\Store\Items;
use Teme\Contracts\Content\ContentReader;
use Teme\Contracts\Content\ItemQuery;
use Teme\Contracts\Jobs\JobContext;
use Teme\Contracts\Jobs\JobHandler;
use Teme\Contracts\Settings\SettingsReader;

/**
 * Brings Sharely's list of published items up to date: reads every
 * published item of the types that get buttons, records its title and its
 * address (keeping the old ones, so counts follow a moved item), and marks
 * as unpublished what is no longer listed. Also refreshes the copy of the
 * settings the public routes read.
 *
 * @since 1.0.0
 */
final readonly class SyncItems implements JobHandler
{
    /** @since 1.0.0 */
    public function __construct(
        private ContentReader $content,
        private SettingsReader $settings,
        private Closure $snapshot,
    ) {}

    /** @since 1.0.0 */
    public function handle(JobContext $context): void
    {
        ($this->snapshot)();
        $config = Config::fromSettings($this->settings->group(Sharely::GROUP));
        $items = new Items;
        $seen = [];
        $after = null;

        // ItemQuery takes at most 10 types; the rest are read on a later pass.
        $types = array_slice($config->types, 0, 10);

        do {
            $page = $this->content->published(new ItemQuery(types: $types, after: $after, limit: 200));

            foreach ($page->items as $ref) {
                $items->published($ref);
                $seen[] = $ref->id;
            }

            $after = $page->next;
            $context->progress(count($seen), max(count($seen), 1), __(':count articles read', ['count' => count($seen)]));
        } while ($after !== null);

        // Anything still marked published that the walk didn't see has gone.
        $seen = array_flip($seen);
        $query = Db::table('sharely_items')->where('published', true);

        if ($types !== []) {
            $query->whereIn('type', $types);
        }

        foreach ($query->pluck('id') as $id) {
            if (! isset($seen[$id])) {
                $items->unpublished((string) $id);
            }
        }

        $seen = array_keys($seen);

        $context->progress(count($seen), max(count($seen), 1), __('Sharely knows :count published articles.', ['count' => count($seen)]));
    }
}
