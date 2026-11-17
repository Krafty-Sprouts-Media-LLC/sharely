<?php

/**
 * Draws the site's own follow buttons: a link to each profile, with
 * rel="me" so services such as Mastodon can check the profile belongs to
 * the site, an email link, and the site's feed.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Render;

use KraftySprouts\Sharely\Networks\Icons;
use KraftySprouts\Sharely\Networks\Networks;
use KraftySprouts\Sharely\Sharing\Config;

/**
 * Draws the site's own follow buttons: a link to each profile, with
 * rel="me" so services such as Mastodon can check the profile belongs to
 * the site, an email link, and the site's feed.
 *
 * @since 1.0.0
 */
final class Follow
{
    /**
     * The follow buttons, or nothing when no profile is set.
     *
     * @param  string  $feed  the site's main feed, for RSS without an address
     *
     * @since 1.0.0
     */
    public static function html(Config $config, string $feed): string
    {
        $items = '';

        foreach ($config->follow as $profile) {
            $network = Networks::find($profile['network']);
            $href = self::href($profile['network'], $profile['address'], $feed);

            if ($network === null || $href === null) {
                continue;
            }

            $newTab = ! in_array($network->id, ['email', 'rss'], true);
            $words = $network->followLabel().($newTab ? ' '.__('(opens in a new tab)') : '');
            $items .= '<li class="sharely__item"><a class="sharely__btn sharely__btn--'.$network->id.'" href="'.Html::e($href).'"'
                .($newTab ? ' target="_blank" rel="me noopener"' : ($network->id === 'rss' ? ' rel="alternate" type="application/rss+xml"' : ''))
                .'>'.Icons::svg($network->id)
                .'<span class="sharely-vh">'.Html::e($words).'</span></a></li>';
        }

        if ($items === '') {
            return '';
        }

        $heading = $config->followHeading;
        $id = 'sharely-follow-'.substr(md5($heading.$items), 0, 8);

        return '<div class="sharely sharely-follow sharely--'.Html::e($config->shape === 'square' ? 'square' : 'circle').' sharely--brand sharely--icons sharely--align-'.Html::e($config->align).'" role="group"'
            .($heading !== '' ? ' aria-labelledby="'.$id.'"' : ' aria-label="'.Html::e(__('Follow us')).'"').'>'
            .($heading !== '' ? '<p class="sharely__heading" id="'.$id.'">'.Html::e($heading).'</p>' : '')
            .'<ul class="sharely__list" role="list">'.$items.'</ul></div>';
    }

    /**
     * A safe address for one profile, or null when there is none.
     *
     * @since 1.0.0
     */
    public static function href(string $network, string $address, string $feed): ?string
    {
        $address = trim($address);

        if ($network === 'rss' && $address === '') {
            $address = $feed;
        }

        if ($network === 'email') {
            $email = preg_replace('/^mailto:/i', '', $address);

            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? 'mailto:'.$email : null;
        }

        return preg_match('#^https?://[^\s/$.?\#][^\s]*$#i', $address) === 1 ? $address : null;
    }
}
