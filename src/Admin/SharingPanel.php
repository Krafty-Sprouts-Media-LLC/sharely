<?php

/**
 * The Sharing panel in the editor: a status line saying whether the item
 * has share buttons and when it was last previewed, and for each network
 * the title its card shows, offered as a suggestion that fits the social
 * title to that network.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Admin;

use KraftySprouts\Sharely\Networks\Networks;
use KraftySprouts\Sharely\Store\Items;
use KraftySprouts\Sharely\Store\State;
use Teme\Contracts\Editor\EditorPanel;
use Teme\Contracts\Editor\EditorPanelContext;
use Teme\Contracts\Editor\PanelView;
use Teme\Contracts\Editor\SuggestedChange;
use Teme\Contracts\Editor\Suggestion;

/**
 * The Sharing panel in the editor: a status line saying whether the item
 * has share buttons and when it was last previewed, and for each network
 * the title its card shows, offered as a suggestion that fits the social
 * title to that network. Core draws it; this class only reads what the
 * preview and options jobs left.
 *
 * @since 1.0.0
 */
final class SharingPanel implements EditorPanel
{
    /** @since 1.0.0 */
    public function view(EditorPanelContext $context): PanelView
    {
        $row = (new Items)->row($context->itemId);
        $preview = (new State)->get('preview:'.$context->itemId);
        $off = $row !== null && (bool) $row->off;

        if (! is_array($preview) || ! isset($preview['lines'])) {
            return new PanelView(
                $off ? __('Share buttons are off for this item. Preview sharing to see how it looks on each network.') : __('Preview sharing to see how this item looks on each network.'),
                $off ? 'warn' : 'neutral',
            );
        }

        $suggestions = [];
        $notes = 0;

        foreach (array_slice((array) $preview['lines'], 0, PanelView::MOST_SUGGESTIONS) as $line) {
            $network = Networks::find((string) ($line['network'] ?? ''));

            if ($network === null) {
                continue;
            }

            $notes += count((array) ($line['notes'] ?? []));
            $fit = $line['fitTitle'] ?? null;
            $detail = trim((string) ($line['description'] ?? '').' '.implode(' ', (array) ($line['notes'] ?? [])));

            $suggestions[] = new Suggestion(
                'card-'.$network->id,
                mb_substr($network->name.': '.(string) $line['title'], 0, 120),
                mb_substr($detail !== '' ? $detail : __('Reads in full.'), 0, 500),
                SuggestedChange::field('social_title', is_string($fit) ? $fit : (string) $line['title']),
            );
        }

        $status = ($off ? __('Share buttons are off for this item.').' ' : '')
            .($notes > 0 ? __(':count things to look at across the networks.', ['count' => $notes]) : __('This item reads well on every network.'));

        return new PanelView(mb_substr($status, 0, PanelView::MOST_STATUS), $off || $notes > 0 ? 'warn' : 'ok', $suggestions);
    }

    /** @since 1.0.0 */
    public function settled(string $suggestionId, bool $used, EditorPanelContext $context): void
    {
        // Using a suggestion sets the social title in the draft; nothing of Sharely's changes.
    }
}
