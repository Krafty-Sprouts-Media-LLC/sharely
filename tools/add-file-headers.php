<?php

/**
 * Adds a file header docblock (purpose, @package, @since) on
 * every PHP file in the given folders, before declare(strict_types=1). The
 * purpose is the first sentence of the file's class docblock. A file that
 * already has a header is left alone, so @since keeps the version a file
 * arrived in; give the version being built. It skips the tools folder, whose
 * files are written by hand.
 *
 * Usage: php tools/add-file-headers.php <version> <package> <dir> [<dir>...]
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

$version = $argv[1];
$package = $argv[2];
$dirs = array_slice($argv, 3);
$changed = 0;

foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

    foreach ($it as $file) {
        $path = $file->getPathname();
        $unix = '/'.ltrim(str_replace('\\', '/', $path), '/');

        if ($file->getExtension() !== 'php' || str_contains($unix, '/fixtures/') || str_contains($unix, '/vendor/') || str_contains($unix, '/tools/')) {
            continue;
        }

        $code = (string) file_get_contents($path);

        // Packs carry a header from the WordPress plugin: only bring it up to date.
        if (str_contains($unix, '/Packs/')) {
            $new = str_replace(['@package Sharely', '@since   0.5.0'], ['@package '.$package, '@since   '.$version], $code);

            if ($new !== $code) {
                file_put_contents($path, $new);
                $changed++;
            }

            continue;
        }

        // A file that has a header keeps it, with the version it arrived in: @since says when
        // something was added, so it is never rewritten. Only a file with no header gets one,
        // stamped with the version given, which must be the version being built.
        if (preg_match('/^<\?php\R\R\/\*\*(?:(?!\*\/).)*?@package '.preg_quote($package, '/').'\R \* @since [^\n]*\R \*\/\R\R/s', $code) === 1) {
            continue;
        }

        $purpose = purpose($code, $unix);
        $header = "/**\n".wrap($purpose)." *\n * @package ".$package."\n * @since ".$version."\n */\n\n";
        $new = preg_replace('/^<\?php\R\R/', "<?php\n\n".$header, $code, 1, $count);

        if ($count === 1 && $new !== $code) {
            file_put_contents($path, $new);
            $changed++;
        }
    }
}

echo "files changed: $changed\n";

/** Wraps a sentence into docblock lines of about 76 columns. */
function wrap(string $text): string
{
    $out = '';

    foreach (explode("\n", wordwrap($text, 72, "\n", false)) as $line) {
        $out .= ' * '.$line."\n";
    }

    return $out;
}

/** The file's purpose in one sentence. */
function purpose(string $code, string $unix): string
{
    $name = basename($unix, '.php');

    if (str_contains($unix, '/tests/')) {
        if ($name === 'Pest') {
            return 'Pest bootstrap and the helpers every test shares.';
        }

        return str_ends_with($name, 'Test')
            ? 'Tests for '.strtolower(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', substr($name, 0, -4)))).'.'
            : classPurpose($code, 'Test support: '.$name.'.');
    }

    if (str_contains($unix, '/database/migrations/')) {
        return "Creates Sharely's own tables.";
    }

    return classPurpose($code, $name.'.');
}

/** The first sentence of the first class-like docblock, or a fallback. */
function classPurpose(string $code, string $fallback): string
{
    if (preg_match('/^namespace [^;]+;.*?\/\*\*\R(.*?)\R\s*\*\/\s*\R(?:final |abstract |readonly )*(?:class|interface|enum|trait)\b/ms', $code, $m)) {
        $lines = [];

        foreach (preg_split('/\R/', $m[1]) as $line) {
            $line = trim(preg_replace('/^\s*\*\s?/', '', $line));

            if ($line === '' || str_starts_with($line, '@')) {
                break;
            }

            $lines[] = $line;
        }

        $text = implode(' ', $lines);

        if (preg_match('/^(.+?[.!?])(\s|$)/', $text, $s)) {
            return $s[1];
        }

        if ($text !== '') {
            return rtrim($text, '.').'.';
        }
    }

    return $fallback;
}
