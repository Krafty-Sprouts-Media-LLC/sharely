<?php

/**
 * The answers Sharely's public routes give: a redirect, a small page, or
 * not found, each telling search engines not to list it.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Routes;

use KraftySprouts\Sharely\Render\Html;
use Teme\Contracts\Routes\PublicResponse;

/**
 * The answers Sharely's public routes give: a redirect, a small page, or
 * not found, each telling search engines not to list it.
 *
 * @since 1.0.0
 */
final class Answers
{
    /** @since 1.0.0 */
    public const array NOINDEX = ['x-robots-tag' => 'noindex, nofollow'];

    /** @since 1.0.0 */
    public static function redirect(string $to): PublicResponse
    {
        return new PublicResponse(302, 'text/plain; charset=utf-8', '', ['location' => $to] + self::NOINDEX);
    }

    /** @since 1.0.0 */
    public static function notFound(): PublicResponse
    {
        return new PublicResponse(404, 'text/plain; charset=utf-8', __('Not found.'), self::NOINDEX);
    }

    /**
     * A small page of Sharely's own, in the theme-neutral look of the
     * buttons, with its heading and body.
     *
     * @since 1.0.0
     */
    public static function page(string $title, string $body, int $status = 200): PublicResponse
    {
        $html = '<!doctype html><html lang="'.Html::e(str_replace('_', '-', (string) (function_exists('app') ? app()->getLocale() : 'en'))).'"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">'
            .'<title>'.Html::e($title).'</title>'
            .'<link rel="stylesheet" href="/sharely/assets/page.css"></head>'
            .'<body><main class="sharely-page"><h1>'.Html::e($title).'</h1>'.$body.'</main></body></html>';

        return new PublicResponse($status, 'text/html; charset=utf-8', $html, self::NOINDEX);
    }
}
