<?php

/**
 * Decides which of Sharely's pieces a page gets, and where: the inline
 * buttons and the script markers inside articles, the floating bar fixed
 * to an edge, follow buttons beside a region or after the article, and at
 * the end of the page the stylesheet and scripts those pieces need.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Render;

use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Sharing\SharedItem;
use KraftySprouts\Sharely\Store\Counts;
use KraftySprouts\Sharely\Store\Items;
use Teme\Contracts\Insertions\ArticleFacts;
use Teme\Contracts\Insertions\Insertion;
use Teme\Contracts\Insertions\PageFacts;
use Teme\Contracts\Insertions\PageInsertion;
use Teme\Contracts\Insertions\PagePosition;
use Teme\Contracts\Insertions\Position;
use Teme\Contracts\Site\SiteInfo;
use Throwable;

/**
 * Decides which of Sharely's pieces a page gets, and where: the inline
 * buttons and the script markers inside articles, the floating bar fixed to
 * an edge, follow buttons beside a region or after the article, and at the
 * end of the page the stylesheet and scripts those pieces need. It only
 * reads; a page never writes.
 *
 * @since 1.0.0
 */
final class Placer
{
    /** @since 1.0.0 */
    public function __construct(
        private readonly Config $config,
        private readonly SiteInfo $site,
    ) {}

    /**
     * The insertions inside one article.
     *
     * @return list<Insertion>
     *
     * @since 1.0.0
     */
    public function insertions(ArticleFacts $article): array
    {
        $c = $this->config;
        $out = [];
        $item = $this->item($article->contentId, $article->type, $article->path);

        if ($item !== null) {
            $buttons = new Buttons($c);
            $counts = $c->showCounts ? (new Counts)->forItem($item->id, $c->fetchCounts ? $c->countNetworks : []) : [];

            if (in_array($c->inlinePosition, ['above', 'both'], true)) {
                $out[] = new Insertion('sharely.above', Position::beforeContent(), $buttons->inline($item, $counts));
            }

            if (in_array($c->inlinePosition, ['below', 'both'], true)) {
                $out[] = new Insertion('sharely.below', Position::afterContent(), $buttons->inline($item, $counts));
            }

            $markers = Extras::markers($c, $item);

            if ($markers !== '') {
                $out[] = new Insertion('sharely.markers', Position::afterContent(), $markers);
            }
        }

        if ($c->followPlace === 'after-content') {
            $follow = Follow::html($c, $this->feed());

            if ($follow !== '') {
                $out[] = new Insertion('sharely.follow', Position::afterContent(), $follow);
            }
        }

        return $out;
    }

    /**
     * The insertions on every page: the floating bar, the follow buttons
     * beside a region, and the stylesheet and scripts.
     *
     * @return list<PageInsertion>
     *
     * @since 1.0.0
     */
    public function pageInsertions(PageFacts $page): array
    {
        $c = $this->config;
        $out = [];
        $item = $page->kind === 'article' && $page->contentId !== null
            ? $this->item($page->contentId, (string) $page->type, $page->path)
            : null;
        $draws = $item !== null;

        if ($item !== null && $c->floating !== 'none') {
            $counts = $c->showCounts ? (new Counts)->forItem($item->id, $c->fetchCounts ? $c->countNetworks : []) : [];
            $html = (new Buttons($c))->floating($item, $counts);

            if ($c->floating === 'bottom') {
                $html = str_replace('sharely--floating', 'sharely--floating sharely--bar', $html);
            }

            $out[] = new PageInsertion('sharely.floating', PagePosition::fixed($c->floating, $c->floating === 'bottom' ? 0 : 160), $html);
        }

        if ($c->followPlace === 'region' && $c->followRegion !== '') {
            $follow = Follow::html($c, $this->feed());

            try {
                if ($follow !== '') {
                    $out[] = new PageInsertion('sharely.follow-region', PagePosition::region($c->followRegion, $c->followRegionWhere), $follow);
                    $draws = true;
                }
            } catch (Throwable) {
                // A region slug that can't be one draws nothing.
            }
        }

        if ($draws || ($c->followPlace === 'after-content' && $page->kind === 'article')) {
            $out[] = new PageInsertion('sharely.foot', PagePosition::bodyEnd(), $item !== null ? Extras::foot($c) : '<style>'.Styles::css($c).'</style>');
        }

        return $out;
    }

    /**
     * The item as Sharely shares it, or null when this type gets no buttons
     * or the item has them turned off. An item Sharely hasn't recorded yet
     * is shared by its address alone; the networks read its title from the
     * page.
     *
     * @since 1.0.0
     */
    private function item(string $id, string $type, string $path): ?SharedItem
    {
        if (! $this->config->sharesType($type)) {
            return null;
        }

        $items = new Items;
        $row = $items->row($id);

        if ($row !== null) {
            return $row->off || ! $row->published || $row->url === '' ? null : Items::toShared($row);
        }

        if ($path === '') {
            return null;
        }

        return new SharedItem($id, $this->site->address().$path, '');
    }

    /** @since 1.0.0 */
    private function feed(): string
    {
        return $this->site->feeds()[0] ?? '';
    }
}
