<?php

/**
 * Draws a set of share buttons as HTML: plain links wherever a link can do
 * the work, script buttons drawn hidden until their script shows them, and
 * a More button made of details and summary, so nothing needs a script to
 * work.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Render;

use KraftySprouts\Sharely\Links\ShareLinks;
use KraftySprouts\Sharely\Networks\Icons;
use KraftySprouts\Sharely\Networks\Network;
use KraftySprouts\Sharely\Networks\Networks;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Sharing\SharedItem;

/**
 * Draws a set of share buttons as HTML: plain links wherever a link can do
 * the work, script buttons drawn hidden until their script shows them, and a
 * More button made of details and summary, so nothing needs a script to work.
 *
 * @since 1.0.0
 */
final class Buttons
{
    /** The address Sharely counts a click at before sending the reader on. @since 1.0.0 */
    public const string GO = '/sharely/go/';

    /** The address of Sharely's Mastodon server picker. @since 1.0.0 */
    public const string MASTODON = '/sharely/mastodon/';

    private ShareLinks $links;

    private int $sets = 0;

    /** @since 1.0.0 */
    public function __construct(private readonly Config $config)
    {
        $this->links = new ShareLinks($config->utm, $config->xAccount);
    }

    /**
     * The buttons that sit in an article, with their heading, the phone set
     * when there is one, and the total when counts show.
     *
     * @param  array<string, int>  $counts  shares by network
     *
     * @since 1.0.0
     */
    public function inline(SharedItem $item, array $counts): string
    {
        $c = $this->config;
        $id = 'sharely-heading-'.substr($item->id, -8).'-'.(++$this->sets);
        $heading = $c->heading !== ''
            ? '<p class="sharely__heading" id="'.Html::e($id).'">'.Html::e($c->heading).'</p>'
            : '';
        $named = $c->heading !== ''
            ? ' aria-labelledby="'.Html::e($id).'"'
            : ' aria-label="'.Html::e(__('Share this article')).'"';

        $shown = $this->shown($counts);

        return '<div class="'.Html::e($this->classes('inline', $c->labels)).'" data-sharely role="group"'.$named.'>'
            .$heading
            .$this->sets($item, $shown, $c->moreAfter)
            .$this->total($shown)
            .'<span class="sharely-vh" role="status" data-sharely-status></span>'
            .'</div>';
    }

    /**
     * The floating bar's buttons: icons only, no heading, no More button.
     *
     * @param  array<string, int>  $counts
     *
     * @since 1.0.0
     */
    public function floating(SharedItem $item, array $counts): string
    {
        $shown = $this->shown($counts);

        return '<div class="'.Html::e($this->classes('floating', 'icons')).'" data-sharely>'
            .$this->sets($item, $shown, 0)
            .$this->total($shown)
            .'<span class="sharely-vh" role="status" data-sharely-status></span>'
            .'</div>';
    }

    /**
     * The counts that show: none below the minimum, or when counts are off.
     *
     * @since 1.0.0
     *
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    private function shown(array $counts): array
    {
        if (! $this->config->showCounts) {
            return [];
        }

        $mine = array_intersect_key($counts, array_flip($this->config->allNetworks()));

        return array_sum($mine) >= $this->config->minCount ? array_filter($mine) : [];
    }

    /**
     * @since 1.0.0
     *
     * @param  array<string, int>  $counts
     */
    private function sets(SharedItem $item, array $counts, int $moreAfter): string
    {
        $main = $this->list($item, $this->config->networks, $counts, $moreAfter, 'main');

        if ($this->config->phoneNetworks === []) {
            return $main;
        }

        return '<div class="sharely__set sharely__set--main">'.$main.'</div>'
            .'<div class="sharely__set sharely__set--phone">'
            .$this->list($item, $this->config->phoneNetworks, $counts, $moreAfter, 'phone')
            .'</div>';
    }

    /**
     * @since 1.0.0
     *
     * @param  list<string>  $networks
     * @param  array<string, int>  $counts
     */
    private function list(SharedItem $item, array $networks, array $counts, int $moreAfter, string $set): string
    {
        $first = $moreAfter > 0 && count($networks) > $moreAfter + 1 ? array_slice($networks, 0, $moreAfter) : $networks;
        $rest = array_slice($networks, count($first));
        $html = '<ul class="sharely__list sharely__list--'.$set.'" role="list">';

        foreach ($first as $id) {
            $html .= $this->item($item, $id, $counts[$id] ?? 0);
        }

        if ($rest !== []) {
            $html .= '<li class="sharely__item sharely__item--more"><details class="sharely__more">'
                .'<summary class="sharely__btn sharely__btn--more">'.Icons::svg('more')
                .'<span class="sharely__label" aria-hidden="true">'.Html::e(__('More')).'</span>'
                .'<span class="sharely-vh">'.Html::e(__('More ways to share')).'</span></summary>'
                .'<ul class="sharely__list" role="list">';

            foreach ($rest as $id) {
                $html .= $this->item($item, $id, $counts[$id] ?? 0);
            }

            $html .= '</ul></details></li>';
        }

        return $html.'</ul>';
    }

    /** @since 1.0.0 */
    private function item(SharedItem $item, string $id, int $count): string
    {
        $network = Networks::find($id);

        if ($network === null) {
            return '';
        }

        return '<li class="sharely__item">'.$this->button($item, $network, $count).'</li>';
    }

    /** @since 1.0.0 */
    private function button(SharedItem $item, Network $network, int $count): string
    {
        $class = 'sharely__btn sharely__btn--'.$network->id;
        $inner = Icons::svg($network->id)
            .'<span class="sharely__label" aria-hidden="true">'.Html::e($network->shortLabel()).'</span>'
            .($count > 0 ? '<span class="sharely__count" aria-hidden="true">'.Html::e(Number::short($count)).'</span>' : '');
        $name = $network->shareLabel();
        $countWords = $count > 0 ? ', '.__(':count shares', ['count' => number_format($count)]) : '';

        if ($network->kind === Network::SCRIPT) {
            $done = Icons::svg('check');

            return '<button type="button" class="'.$class.'" data-sharely-action="'.$network->id.'"'
                .' data-sharely-url="'.Html::e($item->url).'" data-sharely-title="'.Html::e($item->title).'"'
                .($network->id === 'copy' ? ' data-sharely-done="'.Html::e(__('Link copied')).'"' : '')
                .' hidden>'.$inner
                .($network->id === 'copy' ? str_replace('class="sharely-icon"', 'class="sharely-icon sharely-icon--done" hidden', $done) : '')
                .'<span class="sharely-vh">'.Html::e($name.$countWords).'</span></button>';
        }

        $newTab = ! $network->sameTab;
        $words = $name.($newTab ? ' '.__('(opens in a new tab)') : '').$countWords;
        $attrs = '';

        if ($network->kind === Network::PAGE) {
            $href = self::MASTODON.$item->id;
            $attrs = ' data-sharely-mastodon data-sharely-text="'.Html::e($item->words().' '.$this->links->sharedUrl('mastodon', $item)).'"'
                .' data-sharely-server="'.Html::e($this->config->mastodonServer).'"';
        } elseif ($this->config->countClicks && ! $network->sameTab) {
            $href = self::GO.$network->id.'/'.$item->id;
        } else {
            $href = (string) $this->links->href($network->id, $item);
        }

        if ($network->id === 'pinterest' && $this->config->pinPick) {
            $attrs .= ' data-sharely-pick';
        }

        return '<a class="'.$class.'" href="'.Html::e($href).'"'
            .($newTab ? ' target="_blank" rel="noopener nofollow"' : ' rel="nofollow"')
            .$attrs.'>'.$inner.'<span class="sharely-vh">'.Html::e($words).'</span></a>';
    }

    /**
     * @since 1.0.0
     *
     * @param  array<string, int>  $counts
     */
    private function total(array $counts): string
    {
        if ($counts === [] || ! $this->config->showTotal) {
            return '';
        }

        $total = array_sum($counts);

        return '<p class="sharely__total"><strong class="sharely__total-number">'.Html::e(Number::short($total)).'</strong> '
            .'<span class="sharely__total-word">'.Html::e($total === 1 ? __('share') : __('shares')).'</span></p>';
    }

    /** @since 1.0.0 */
    private function classes(string $place, string $labels): string
    {
        $c = $this->config;

        return implode(' ', [
            'sharely',
            'sharely--'.$place,
            'sharely--'.$c->shape,
            'sharely--'.$c->size,
            'sharely--'.$labels,
            'sharely--'.$c->colors,
            'sharely--align-'.$c->align,
        ]);
    }
}
