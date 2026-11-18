<?php

/**
 * Sharely's stylesheet, drawn with the active theme's tokens (its accent,
 * text color, rules, spacing and type steps) so the buttons fit any theme,
 * with each network's color and the phone width from the settings.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Render;

use KraftySprouts\Sharely\Networks\Contrast;
use KraftySprouts\Sharely\Networks\Networks;
use KraftySprouts\Sharely\Sharing\Config;

/**
 * Sharely's stylesheet, drawn with the active theme's tokens (its accent,
 * text color, rules, spacing and type steps) so the buttons fit any theme,
 * with each network's color and the phone width from the settings.
 *
 * @since 1.0.0
 */
final class Styles
{
    /**
     * The CSS for the buttons the settings show, plus the extra files of the
     * scripts named (highlight, pins, dialog).
     *
     * @param  list<string>  $extras
     *
     * @since 1.0.0
     */
    public static function css(Config $config, array $extras = []): string
    {
        $css = self::base();

        foreach (array_unique([...$config->allNetworks(), ...$config->highlightNetworks, ...array_column($config->follow, 'network')]) as $id) {
            $network = Networks::find($id);

            if ($network !== null) {
                $css .= '.sharely--brand .sharely__btn--'.$id.'{--sharely-bg:'.$network->color.';--sharely-fg:'.Contrast::on($network->color).'}';
            }
        }

        if ($config->phoneNetworks !== []) {
            $w = $config->phoneWidth;
            $css .= '@media (max-width:'.($w - 0.02).'px){.sharely__set--main{display:none}}'
                .'@media (min-width:'.$w.'px){.sharely__set--phone{display:none}}';
        }

        foreach ($extras as $extra) {
            $file = dirname(__DIR__, 2).'/resources/css/'.basename($extra).'.css';

            if (is_file($file)) {
                $css .= (string) file_get_contents($file);
            }
        }

        return str_ireplace('</style', '<\/style', $css);
    }

    /** @since 1.0.0 */
    private static function base(): string
    {
        return <<<'CSS'
.sharely-vh{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0}
.sharely{--sharely-gap:var(--teme-space-2,.5rem);--sharely-h:2.5rem;--sharely-radius:.375rem;--sharely-bg:var(--teme-contrast,#1f2937);--sharely-fg:var(--teme-base,#fff);margin-block:var(--teme-space-4,1.5rem);font-size:var(--teme-step--1,.875rem);line-height:1.2}
.sharely--small{--sharely-h:2rem}.sharely--large{--sharely-h:3rem;font-size:var(--teme-step-0,1rem)}
.sharely--square{--sharely-radius:0}.sharely--pill,.sharely--circle{--sharely-radius:999px}
.sharely__heading{margin:0 0 var(--teme-space-2,.5rem);font-family:var(--teme-font-heading,inherit);font-weight:700;color:var(--teme-contrast,inherit)}
.sharely__list{display:flex;flex-wrap:wrap;gap:var(--sharely-gap);margin:0;padding:0;list-style:none}
.sharely--align-center .sharely__list,.sharely--align-center .sharely__heading,.sharely--align-center .sharely__total{justify-content:center;text-align:center}
.sharely--align-end .sharely__list,.sharely--align-end .sharely__heading,.sharely--align-end .sharely__total{justify-content:flex-end;text-align:end}
.sharely--align-fill .sharely__list>.sharely__item{flex:1 1 0}.sharely--align-fill .sharely__btn{width:100%;justify-content:center}
.sharely__item{margin:0;padding:0}
.sharely__btn{box-sizing:border-box;display:inline-flex;align-items:center;justify-content:center;gap:.4em;min-width:var(--sharely-h);min-height:max(var(--sharely-h),24px);padding:0 .75em;border:2px solid var(--sharely-bg);border-radius:var(--sharely-radius);background:var(--sharely-bg);color:var(--sharely-fg);font:inherit;font-weight:600;text-decoration:none;cursor:pointer;transition:filter .15s ease}
.sharely--icons .sharely__btn{padding:0}
.sharely--icons .sharely__label{display:none}
.sharely--circle .sharely__btn{padding:0;width:var(--sharely-h)}.sharely--circle .sharely__label{display:none}
.sharely__btn:hover{filter:brightness(.88)}
.sharely__btn:focus-visible,.sharely__more>summary:focus-visible{outline:3px solid var(--teme-accent,#2563eb);outline-offset:2px}
.sharely__btn[hidden]{display:none}
.sharely-icon{width:1.25em;height:1.25em;flex:none;fill:currentColor}
.sharely-icon--done{display:none}
.sharely__btn.is-done .sharely-icon:not(.sharely-icon--done){display:none}.sharely__btn.is-done .sharely-icon--done{display:block}
.sharely__count{font-weight:700;padding-inline-start:.25em;border-inline-start:1px solid currentColor}
.sharely--accent .sharely__btn{--sharely-bg:var(--teme-accent,#2563eb);--sharely-fg:var(--teme-on-accent,#fff)}
.sharely--plain .sharely__btn{--sharely-bg:transparent;--sharely-fg:var(--teme-contrast,#111);border-color:var(--teme-rule,currentColor)}
.sharely--plain .sharely__btn:hover{filter:none;border-color:var(--teme-contrast,#111)}
.sharely__more{position:relative}
.sharely__more>summary{list-style:none;--sharely-bg:var(--teme-muted,#4b5563);--sharely-fg:var(--teme-base,#fff)}
.sharely__more>summary::-webkit-details-marker{display:none}
.sharely__more[open]>.sharely__list{position:absolute;z-index:20;inset-inline-start:0;top:calc(100% + var(--sharely-gap));min-width:12rem;padding:var(--sharely-gap);flex-direction:column;background:var(--teme-base,#fff);border:1px solid var(--teme-rule,#ddd);border-radius:var(--sharely-radius);box-shadow:0 .5rem 1.5rem rgb(0 0 0/.15)}
.sharely__more[open]>.sharely__list .sharely__btn{width:100%;justify-content:flex-start;padding:0 .75em}
.sharely__more[open]>.sharely__list .sharely__label{display:inline}
.sharely__total{margin:var(--teme-space-2,.5rem) 0 0;color:var(--teme-muted,inherit)}
.sharely__total-number{color:var(--teme-contrast,inherit);font-size:1.25em}
.sharely--floating{margin:0}
.sharely--floating .sharely__list{flex-direction:column;align-items:center}
.sharely--floating .sharely__total{text-align:center}
.sharely--bar .sharely__list{flex-direction:row;justify-content:center}
.sharely-follow .sharely__btn{--sharely-h:2.5rem}
@media (prefers-reduced-motion:reduce){.sharely__btn{transition:none}}
@media print{.sharely,.sharely-follow{display:none!important}}
CSS;
    }
}
