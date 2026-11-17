<?php

/**
 * What Sharely's scripts read from a page: the hidden markers inside the
 * article (highlight and pins), and at the end of the page the stylesheet,
 * the translated words, the icon template and the script tags, each script
 * only when a feature on the page needs it.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Render;

use KraftySprouts\Sharely\Links\ShareLinks;
use KraftySprouts\Sharely\Networks\Icons;
use KraftySprouts\Sharely\Networks\Networks;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Sharing\SharedItem;

/**
 * What Sharely's scripts read from a page: the hidden markers inside the
 * article (highlight and pins), and at the end of the page the stylesheet,
 * the translated words, the icon template and the script tags, each script
 * only when a feature on the page needs it.
 *
 * @since 1.0.0
 */
final class Extras
{
    /** Where the scripts are served. @since 1.0.0 */
    public const string ASSETS = '/sharely/assets/';

    /** What the highlight script replaces with the quoted words. @since 1.0.0 */
    public const string QUOTE = '%%SHARELY%%';

    /**
     * The scripts the page needs, in load order.
     *
     * @return list<string>
     *
     * @since 1.0.0
     */
    public static function scripts(Config $config): array
    {
        $networks = $config->allNetworks();
        $scripts = [];

        if (array_intersect($networks, ['copy', 'print', 'sheet']) !== []) {
            $scripts[] = 'actions';
        }

        if (in_array('mastodon', $networks, true) || ($config->highlight && in_array('mastodon', $config->highlightNetworks, true))) {
            $scripts[] = 'mastodon';
        }

        if (($config->highlight && $config->highlightNetworks !== []) || $config->quoteButtons) {
            $scripts[] = 'highlight';
        }

        if ($config->pinImages || ($config->pinPick && in_array('pinterest', $networks, true))) {
            $scripts[] = 'pins';
        }

        return $scripts;
    }

    /**
     * The hidden markers the highlight and pin scripts look for, placed
     * inside the article so the scripts know which part of the page it is.
     *
     * @since 1.0.0
     */
    public static function markers(Config $config, SharedItem $item): string
    {
        $scripts = self::scripts($config);
        $links = new ShareLinks($config->utm, $config->xAccount);
        $html = '';

        if (in_array('highlight', $scripts, true)) {
            $networks = $config->highlight ? $config->highlightNetworks : ['x', 'bluesky', 'copy'];
            $templates = [];
            $labels = [];

            foreach ($networks as $id) {
                $network = Networks::find($id);
                $templates[$id] = (string) $links->href($id, $item, self::QUOTE);
                $labels[$id] = match ($id) {
                    'copy' => __('Copy the quote and link'),
                    'email' => __('Share the quote by email'),
                    default => (string) $network?->shareLabel().' '.__('(opens in a new tab)'),
                };
            }

            $html .= '<span hidden data-sharely-highlight'
                .' data-sharely-networks="'.Html::e(implode(',', $networks)).'"'
                .' data-sharely-templates="'.Html::e(self::json($templates)).'"'
                .' data-sharely-labels="'.Html::e(self::json($labels)).'"'
                .' data-sharely-url="'.Html::e($links->sharedUrl('highlight', $item)).'"'
                .' data-sharely-mastodon-page="'.Html::e(Buttons::MASTODON.$item->id).'"'
                .' data-sharely-quotes="'.($config->quoteButtons ? '1' : '0').'"></span>';
        }

        if (in_array('pins', $scripts, true)) {
            $pin = new SharedItem($item->id, $item->url, $item->title, $item->description, null, '%%SHARELY_MEDIA%%', '%%SHARELY_TEXT%%', null, $item->shortUrl);

            $html .= '<span hidden data-sharely-pins'
                .' data-sharely-template="'.Html::e((string) $links->href('pinterest', $pin)).'"'
                .' data-sharely-description="'.Html::e((string) ($item->pinDescription ?? $item->title)).'"'
                .' data-sharely-min="'.$config->pinMinWidth.'"'
                .' data-sharely-corner="'.Html::e($config->pinCorner).'"'
                .' data-sharely-buttons="'.($config->pinImages ? '1' : '0').'"></span>';
        }

        return $html;
    }

    /**
     * The end of the page: the stylesheet, and for the scripts the words,
     * the icons and the script tags.
     *
     * @since 1.0.0
     */
    public static function foot(Config $config): string
    {
        $scripts = self::scripts($config);
        $css = [];

        if (in_array('highlight', $scripts, true)) {
            $css[] = 'highlight';
        }

        if (in_array('pins', $scripts, true)) {
            $css[] = 'pins';
        }

        if (in_array('pins', $scripts, true) || in_array('mastodon', $scripts, true)) {
            $css[] = 'dialog';
        }

        $html = '<style>'.Styles::css($config, $css).'</style>';

        if ($scripts === []) {
            return $html;
        }

        $html .= '<span hidden data-sharely-words="'.Html::e(self::json(self::words())).'"></span>';
        $icons = array_unique([...$config->highlightNetworks, 'x', 'bluesky', 'copy', 'mastodon', 'email', 'pin', 'share']);
        $html .= '<template data-sharely-icons>';

        foreach ($icons as $id) {
            $html .= preg_replace('/^<svg /', '<svg data-network="'.Html::e($id).'" ', Icons::svg($id));
        }

        $html .= '</template>';

        foreach ($scripts as $script) {
            $html .= '<script src="'.self::ASSETS.$script.'.js" defer></script>';
        }

        return $html;
    }

    /**
     * The words the scripts show, translated on the server.
     *
     * @return array<string, string>
     *
     * @since 1.0.0
     */
    public static function words(): array
    {
        return [
            'mastodonTitle' => __('Share on Mastodon'),
            'mastodonServer' => __('Your server'),
            'mastodonHint' => __('Such as mastodon.social. Your device remembers it for next time.'),
            'share' => __('Share'),
            'cancel' => __('Cancel'),
            'badServer' => __('That doesn’t look like a server’s address. Type its name, such as mastodon.social.'),
            'highlightBar' => __('Share the selected words'),
            'highlightReady' => __('Share buttons for your selection follow this paragraph.'),
            'shareQuote' => __('Share this quote'),
            'pinImage' => __('Save “:alt” to Pinterest (opens in a new tab)'),
            'pickImage' => __('Choose an image to pin'),
            'copied' => __('Copied'),
        ];
    }

    /**
     * @since 1.0.0
     *
     * @param  array<string, string>  $value
     */
    private static function json(array $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
