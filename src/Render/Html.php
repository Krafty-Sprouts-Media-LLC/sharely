<?php

/**
 * Escapes text for HTML text and attribute values, the one way Sharely
 * prints anything that came from people or other sites.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Render;

/**
 * Escapes text for HTML text and attribute values, the one way Sharely
 * prints anything that came from people or other sites.
 *
 * @since 1.0.0
 */
final class Html
{
    /**
     * The text, safe inside an element or a double-quoted attribute.
     *
     * @since 1.0.0
     */
    public static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
