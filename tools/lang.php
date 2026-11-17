<?php

/**
 * Collects every sentence Sharely passes to __() into lang/en.json, the
 * American English source a translator copies to lang/{locale}.json.
 *
 * Usage: php tools/lang.php
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

$root = dirname(__DIR__);
$words = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src', FilesystemIterator::SKIP_DOTS));

foreach ($it as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $code = (string) file_get_contents($file->getPathname());

    // __('…') and __("…") with a literal first argument.
    preg_match_all('/__\(\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")/', $code, $m);

    foreach ($m[1] as $literal) {
        $text = $literal[0] === "'"
            ? str_replace(["\\'", '\\\\'], ["'", '\\'], substr($literal, 1, -1))
            : stripcslashes(substr($literal, 1, -1));

        if (! str_contains($text, "'.") && $text !== '') {
            $words[$text] = $text;
        }
    }
}

// Settings labels and descriptions are drawn by Teme from the manifest.
$manifest = json_decode((string) file_get_contents($root.'/teme-plugin.json'), true);

array_walk_recursive($manifest['contributes'], static function ($value, $key) use (&$words): void {
    if (in_array($key, ['label', 'description', 'plural', 'itemLabel'], true) && is_string($value) && $value !== '') {
        $words[$value] = $value;
    }
});

ksort($words, SORT_STRING | SORT_FLAG_CASE);
@mkdir($root.'/lang');
file_put_contents($root.'/lang/en.json', json_encode($words, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
echo count($words)." sentences\n";
