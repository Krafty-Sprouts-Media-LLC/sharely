<?php

/**
 * Serves /sharely/assets/{file}: Sharely's scripts from resources/js and
 * its stylesheets from resources/css, from the site's own address, so a
 * page loads them with no outside request and no inline script to approve.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Routes;

use Teme\Contracts\Routes\PublicRequest;
use Teme\Contracts\Routes\PublicResponse;
use Teme\Contracts\Routes\PublicRoute;

/**
 * Serves /sharely/assets/{file}: Sharely's scripts from resources/js and its
 * stylesheets from resources/css, from the site's own address, so a page
 * loads them with no outside request and no inline script to approve. Only
 * files that exist in those two folders are served.
 *
 * @since 1.0.0
 */
final class AssetRoute implements PublicRoute
{
    /** @since 1.0.0 */
    public function handle(PublicRequest $request): PublicResponse
    {
        $file = (string) ($request->parameters['file'] ?? '');

        if (preg_match('/^([a-z]{2,20})\.(js|css)$/', $file, $m) !== 1) {
            return Answers::notFound();
        }

        $path = dirname(__DIR__, 2).'/resources/'.$m[2].'/'.$m[1].'.'.$m[2];

        if (! is_file($path)) {
            return Answers::notFound();
        }

        $type = $m[2] === 'js' ? 'text/javascript; charset=utf-8' : 'text/css; charset=utf-8';

        return new PublicResponse(200, $type, (string) file_get_contents($path), ['x-robots-tag' => 'noindex']);
    }
}
