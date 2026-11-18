<?php

/**
 * Answers /sharely/mastodon/{item}.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Routes;

use KraftySprouts\Sharely\Links\ShareLinks;
use KraftySprouts\Sharely\Render\Buttons;
use KraftySprouts\Sharely\Render\Html;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Sharing\SharedItem;
use KraftySprouts\Sharely\Store\Counts;
use KraftySprouts\Sharely\Store\Items;
use KraftySprouts\Sharely\Store\State;
use Teme\Contracts\Routes\PublicRequest;
use Teme\Contracts\Routes\PublicResponse;
use Teme\Contracts\Routes\PublicRoute;

/**
 * Answers /sharely/mastodon/{item}. Without a server it asks which server
 * the reader uses. With ?server= it checks the name is a public host name
 * (PublicHost) and shows a page that says "You’re leaving for that server
 * to share this post", with a Continue link, never a redirect. Only
 * the Continue link, signed for that item and server (Guard), redirects to
 * the server's /share page, and only then is a click counted. Mastodon,
 * Misskey, Sharkey, Firefish and Iceshrimp all take /share?text=. Sharely
 * keeps nothing about the reader; their device remembers the server through
 * the Mastodon script.
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
        $server = PublicHost::from($typed);

        if ($server === null) {
            return $this->ask($item, $config, $typed);
        }

        $guard = new Guard(new SiteKeys);
        $go = (string) ($request->query['go'] ?? '');

        if (! $guard->validToken($item->id, $server, $go)) {
            return $this->leaving($item, $server, $text, $guard->token($item->id, $server));
        }

        if ($config->countClicks && $guard->allowClick($item->id, $request->clientAddress)) {
            (new Counts)->click($item->id, 'mastodon');
        }

        return Answers::redirect('https://'.$server.'/share?text='.rawurlencode($text));
    }

    /**
     * The page that asks which server the reader uses, with the reason when
     * what they typed isn't a public host name.
     *
     * @since 1.0.0
     */
    private function ask(SharedItem $item, Config $config, string $typed): PublicResponse
    {
        $error = $typed !== ''
            ? '<p class="sharely-page__error" id="sharely-server-error">'.Html::e(__('That doesn’t look like a public server’s name. Type just its name, such as mastodon.social, with no address numbers, port or path.')).'</p>'
            : '';
        $body = '<p>'.Html::e(__('Mastodon has many servers. Type the name of yours, and Sharely sends you there to post:')).'</p>'
            .'<blockquote class="sharely-page__quote">'.Html::e($item->words()).'</blockquote>'
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
     * The page that says where the reader is going before they go.
     *
     * @since 1.0.0
     */
    private function leaving(SharedItem $item, string $server, string $text, string $token): PublicResponse
    {
        $continue = Buttons::MASTODON.$item->id.'?'.http_build_query(['server' => $server, 'go' => $token], '', '&', PHP_QUERY_RFC3986);
        $body = '<p>'.Html::e(__(':server will open its own page to post this:', ['server' => $server])).'</p>'
            .'<blockquote class="sharely-page__quote">'.Html::e($text).'</blockquote>'
            .($token !== '' ? '<p><a class="sharely-page__continue" href="'.Html::e($continue).'">'.Html::e(__('Continue')).'</a></p>' : '<p class="sharely-page__error">'.Html::e(__('Sharely can’t make a safe link right now. Please try again in a moment.')).'</p>')
            .'<p><a href="'.Html::e($item->url).'">'.Html::e(__('Cancel and go back to the article')).'</a></p>';

        return Answers::page(__('You’re leaving for :server to share this post', ['server' => $server]), $body);
    }
}
