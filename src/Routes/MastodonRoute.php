<?php

/**
 * Answers /sharely/mastodon/{item}: without a server, a small page asking
 * which server the reader uses; with ?server=, counts the share and sends
 * them to that server's share page.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Routes;

use KraftySprouts\Sharely\Links\ShareLinks;
use KraftySprouts\Sharely\Render\Html;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Store\Counts;
use KraftySprouts\Sharely\Store\Items;
use KraftySprouts\Sharely\Store\State;
use Teme\Contracts\Routes\PublicRequest;
use Teme\Contracts\Routes\PublicResponse;
use Teme\Contracts\Routes\PublicRoute;

/**
 * Answers /sharely/mastodon/{item}: without a server, a small page asking
 * which server the reader uses; with ?server=, counts the share and sends
 * them to that server's share page. Mastodon, Misskey, Sharkey, Firefish and
 * Iceshrimp all take /share?text=. The reader's device remembers the server
 * through the Mastodon script; Sharely keeps nothing.
 *
 * @since 1.0.0
 */
final class MastodonRoute implements PublicRoute
{
    /** @since 1.0.0 */
    public function handle(PublicRequest $request): PublicResponse
    {
        $item = (new Items)->shared((string) ($request->parameters['item'] ?? ''));

        if ($item === null) {
            return Answers::notFound();
        }

        $config = Config::fromSettings((array) (new State)->get(State::SETTINGS, []));
        $text = $item->words().' '.(new ShareLinks($config->utm, $config->xAccount))->sharedUrl('mastodon', $item);
        $typed = (string) ($request->query['server'] ?? '');
        $server = self::host($typed);

        if ($server !== null) {
            if ($config->countClicks) {
                (new Counts)->click($item->id, 'mastodon');
            }

            return Answers::redirect('https://'.$server.'/share?text='.rawurlencode($text));
        }

        $error = $typed !== ''
            ? '<p class="sharely-page__error" id="sharely-server-error">'.Html::e(__('That doesn’t look like a server’s address. Type its name, such as mastodon.social.')).'</p>'
            : '';
        $body = '<p>'.Html::e(__('Mastodon has many servers. Type the name of yours, and Sharely sends you there to post:')).'</p>'
            .'<blockquote class="sharely-page__quote">'.Html::e($text).'</blockquote>'
            .'<form method="get" data-sharely-mastodon-form>'
            .'<label for="sharely-server">'.Html::e(__('Your server')).'</label>'
            .$error
            .'<input id="sharely-server" name="server" type="text" inputmode="url" autocomplete="url" required'
            .' value="'.Html::e($typed !== '' ? $typed : $config->mastodonServer).'"'
            .($error !== '' ? ' aria-invalid="true" aria-describedby="sharely-server-error"' : '').'>'
            .'<button type="submit">'.Html::e(__('Share')).'</button></form>'
            .'<p><a href="'.Html::e($item->url).'">'.Html::e(__('Back to the article')).'</a></p>'
            .'<script src="/sharely/assets/mastodon.js" defer></script>';

        return Answers::page(__('Share on Mastodon'), $body, $error !== '' ? 422 : 200);
    }

    /**
     * The server's host name from what the reader typed, or null: a name
     * with a dot, an optional port, no path; a scheme, a trailing slash and
     * a handle such as @me@example.social are taken off.
     *
     * @since 1.0.0
     */
    public static function host(string $typed): ?string
    {
        $host = strtolower(trim($typed));
        $host = (string) preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $host);
        $host = rtrim($host, '/');

        if (preg_match('/^@?[^@\s]+@([^@\s]+)$/', $host, $m) === 1) {
            $host = $m[1];
        }

        return preg_match('/^(?=.{4,253}(?::\d{1,5})?$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,61}[a-z0-9](?::\d{1,5})?$/', $host) === 1
            ? $host
            : null;
    }
}
