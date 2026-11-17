<?php

/**
 * Adds "@since <version>" to every class-like, method and constant docblock in
 * the given folders, creating a one-line docblock where there is none.
 *
 * Usage: php tools/add-since.php <version> <dir> [<dir>...]
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

$version = $argv[1];
$dirs = array_slice($argv, 2);
$changed = 0;

foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

    foreach ($it as $file) {
        if ($file->getExtension() !== 'php' || str_contains(str_replace('\\', '/', $file->getPathname()), '/Packs/')) {
            continue;
        }

        $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);
        $out = [];
        $touched = false;

        foreach ($lines as $i => $line) {
            $isDecl = preg_match('/^(\s*)((final|abstract|readonly)\s+)*(class|interface|enum|trait)\s+\w+/', $line, $m)
                || preg_match('/^(\s*)((public|protected|private)\s+)((static|final|abstract|readonly)\s+)*function\s+\w+/', $line, $m)
                || preg_match('/^(\s*)((public|protected|private)\s+)?((final)\s+)?const\s+(?:\w+\s+)?\w+\s*=/', $line, $m)
                || preg_match('/^(\s*)((public|protected|private)\s+)(static\s+)?function\s+\w+/', $line, $m);

            // A "class" word inside a docblock or a string is not a declaration.
            if (! $isDecl || preg_match('/^\s*(\*|\/\/|#)/', $line)) {
                $out[] = $line;

                continue;
            }

            $indent = $m[1];
            // Walk back over attribute lines to find where a docblock would end.
            $j = count($out) - 1;
            while ($j >= 0 && preg_match('/^\s*#\[/', $out[$j])) {
                $j--;
            }

            if ($j >= 0 && preg_match('/\*\/\s*$/', $out[$j])) {
                $end = $j;
                $start = $end;
                while ($start >= 0 && ! str_contains($out[$start], '/**')) {
                    $start--;
                }

                $block = implode("\n", array_slice($out, $start, $end - $start + 1));

                if (str_contains($block, '@since')) {
                    $out[] = $line;

                    continue;
                }

                if ($start === $end) {
                    // One-line docblock: "/** text */" -> multi-line with @since.
                    $text = trim(preg_replace('/^\s*\/\*\*\s*|\s*\*\/\s*$/', '', $out[$start]));
                    $new = [$indent.'/**'];
                    if ($text !== '') {
                        $new[] = $indent.' * '.$text;
                        $new[] = $indent.' *';
                    }
                    $new[] = $indent.' * @since '.$version;
                    $new[] = $indent.' */';
                    array_splice($out, $start, 1, $new);
                } else {
                    $new = [];
                    $hasTags = false;
                    foreach (array_slice($out, $start + 1, $end - $start - 1) as $l) {
                        if (preg_match('/^\s*\*\s*@/', $l)) {
                            $hasTags = true;
                        }
                    }
                    // Keep @since after the description and before @param tags, as the WordPress plugin does.
                    $body = array_slice($out, $start + 1, $end - $start - 1);
                    $insertAt = count($body);
                    foreach ($body as $k => $l) {
                        if (preg_match('/^\s*\*\s*@/', $l)) {
                            $insertAt = $k;

                            break;
                        }
                    }
                    $add = [$indent.' * @since '.$version];
                    if ($insertAt > 0 && ! preg_match('/^\s*\*\s*$/', $body[$insertAt - 1])) {
                        array_unshift($add, $indent.' *');
                    }
                    if ($hasTags && $insertAt < count($body)) {
                        $add[] = $indent.' *';
                    }
                    array_splice($body, $insertAt, 0, $add);
                    array_splice($out, $start, $end - $start + 1, array_merge([$out[$start]], $body, [$out[$end]]));
                }
            } else {
                array_splice($out, $j + 1, 0, [$indent.'/** @since '.$version.' */']);
            }

            $out[] = $line;
            $touched = true;
        }

        if ($touched) {
            file_put_contents($file->getPathname(), implode("\n", $out)."\n");
            $changed++;
        }
    }
}

echo "files changed: $changed\n";
