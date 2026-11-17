<?php

declare(strict_types=1);

// Teme gives __() on a live site, and the plugin's words go through it.
// In the package's own unit tests it fills :placeholders and nothing more.
if (! function_exists('__')) {
    function __(string $key, array $replace = []): string
    {
        foreach ($replace as $name => $value) {
            $key = str_replace(':'.$name, (string) $value, $key);
        }

        return $key;
    }
}
