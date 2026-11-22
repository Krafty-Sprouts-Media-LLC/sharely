<?php

/**
 * Runs Teme's directory inspection on what a tag of this repository would be, before the tag is pushed.
 *
 * Usage: php tools/directory-check.php <path to a clone of temecms/directory> [<version>]
 *
 * Clone https://github.com/temecms/directory once and run `composer install`
 * in it. This script builds the archive GitHub would make of the current
 * commit (`git archive`, which keeps what .gitattributes says to leave out),
 * asks GitHub for Teme's release tags, and runs the directory's own
 * PackageInspector on it with tools/directory-listing.json as the listing:
 * the same checks its pull request and its release builder run, bar the
 * install on a fresh Teme and the pictures a README links. Exits 1 when a
 * check fails.
 *
 * @package KraftySprouts\Sharely
 * @since 1.1.0
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$directory = $argv[1] ?? null;

if ($directory === null || ! is_file($directory.'/vendor/autoload.php')) {
    fwrite(STDERR, "Usage: php tools/directory-check.php <path to a clone of temecms/directory, with composer install run> [<version>]\n");
    exit(2);
}

require $directory.'/vendor/autoload.php';

use Teme\Directory\Listings\Listing;
use Teme\Directory\Packages\Archive;
use Teme\Directory\Packages\ImageFetcher;
use Teme\Directory\Packages\PackageInspector;
use Teme\Directory\Teme\TemeVersions;

$manifest = json_decode((string) file_get_contents($root.'/teme-plugin.json'), true);
$composer = json_decode((string) file_get_contents($root.'/composer.json'), true);
$listing = json_decode((string) file_get_contents($root.'/tools/directory-listing.json'), true);
$version = $argv[2] ?? (string) $manifest['version'];

if (preg_match('#^https://github\.com/([^/]+)/([^/]+)$#', (string) $listing['repository'], $m) !== 1) {
    fwrite(STDERR, "tools/directory-listing.json must name a GitHub repository.\n");
    exit(2);
}

$zip = tempnam(sys_get_temp_dir(), 'sharely-archive-').'.zip';
exec('git -C '.escapeshellarg($root).' archive --format=zip --prefix='.escapeshellarg('sharely-'.$version.'/').' -o '.escapeshellarg($zip).' HEAD 2>&1', $out, $status);

if ($status !== 0) {
    fwrite(STDERR, implode("\n", $out)."\n");
    exit(2);
}

exec('git ls-remote --tags --refs https://github.com/temecms/teme 2>&1', $refs, $status);

if ($status !== 0) {
    fwrite(STDERR, "Couldn't read Teme's tags from GitHub.\n");
    exit(2);
}

$tags = array_values(array_filter(array_map(static fn (string $line): string => substr((string) strrchr($line, '/'), 1), $refs)));
$fetcher = new class implements ImageFetcher
{
    public function fetch(string $url): ?string
    {
        return null; // pictures a README links are fetched by the directory, not here
    }
};

$inspection = (new PackageInspector($directory.'/schema', 'https://temecms.github.io/directory/', $fetcher))->inspect(
    Archive::fromBytes((string) file_get_contents($zip)),
    new Listing((string) $composer['name'], $m[1], $m[2], (string) $listing['kind'], $listing['categories'], (bool) $listing['picked']),
    $version,
    'v'.$version,
    TemeVersions::fromTags($tags),
);

unlink($zip);

foreach ($inspection->checks as $check) {
    echo ($check['passed'] ? '  ✓ ' : '  ✗ ').$check['words']."\n";
}

echo "\n".($inspection->passed() ? 'PASSED' : 'FAILED').'  works with Teme '.implode(', ', $inspection->worksWith).'; the directory installs it on '.implode(', ', $inspection->temeTags)."\n";

exit($inspection->passed() ? 0 : 1);
