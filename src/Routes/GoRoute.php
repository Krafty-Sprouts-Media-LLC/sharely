<?php

/**
 * Answers /sharely/go/{network}/{item}: adds one to that item's share
 * clicks for that network today, and sends the reader on to the network's
 * share address with a 302.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Routes;

use KraftySprouts\Sharely\Links\ShareLinks;
use KraftySprouts\Sharely\Networks\Network;
use KraftySprouts\Sharely\Networks\Networks;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Store\Counts;
use KraftySprouts\Sharely\Store\Items;
use KraftySprouts\Sharely\Store\State;
use Teme\Contracts\Routes\PublicRequest;
use Teme\Contracts\Routes\PublicResponse;
use Teme\Contracts\Routes\PublicRoute;

/**
 * Answers /sharely/go/{network}/{item}: adds one to that item's share
 * clicks for that network today, and sends the reader on to the network's
 * share address with a 302. It keeps nothing about the reader, and only
 * redirects to an address it builds itself for a published item, so it can
 * never be used to send people elsewhere.
 *
 * @since 1.0.0
 */
final class GoRoute implements PublicRoute
{
    /** @since 1.0.0 */
    public function handle(PublicRequest $request): PublicResponse
    {
        $network = Networks::find($request->parameters['network'] ?? '');
        $itemId = (string) ($request->parameters['item'] ?? '');
        $item = (new Items)->shared($itemId);

        if ($network === null || $network->kind !== Network::LINK || ! $network->share || $item === null) {
            return Answers::notFound();
        }

        $config = Config::fromSettings((array) (new State)->get(State::SETTINGS, []));
        $target = (new ShareLinks($config->utm, $config->xAccount))->href($network->id, $item);

        if ($target === null || ! in_array($network->id, $config->allNetworks(), true)) {
            return Answers::notFound();
        }

        if ($config->countClicks) {
            (new Counts)->click($item->id, $network->id);
        }

        return Answers::redirect($target);
    }
}
