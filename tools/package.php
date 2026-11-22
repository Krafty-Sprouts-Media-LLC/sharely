<?php

/**
 * Builds the zip a site can upload, checks it as Teme would, and writes its checksum.
 *
 * Usage: php tools/package.php [--out=dist] [--tag=v1.0.0] [--notes=dist/release-notes.md]
 *
 *   --out    the folder the zip is written to (dist by default)
 *   --tag    a release tag the manifest and the changelog must agree with
 *   --notes  where to write the changelog's section for this version
 *
 * Exits 1, naming every problem, when the tag disagrees with the package or
 * a site would refuse the zip.
 *
 * @package KraftySprouts\Sharely
 * @since 1.1.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Tools\ArchiveCheck;
use KraftySprouts\Sharely\Tools\Packager;
use KraftySprouts\Sharely\Tools\ReleaseCheck;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$options = [];

foreach (array_slice($argv, 1) as $argument) {
    if (preg_match('/^--([a-z]+)=(.*)$/', $argument, $m) === 1) {
        $options[$m[1]] = $m[2];
    } else {
        fwrite(STDERR, "Unknown argument: {$argument}\n");
        exit(2);
    }
}

$out = $options['out'] ?? $root.'/dist';
$fail = static function (array $problems): never {
    foreach ($problems as $problem) {
        fwrite(STDERR, "✗ {$problem}\n");
    }

    exit(1);
};

if (isset($options['tag'])) {
    $problems = (new ReleaseCheck($root))->problems($options['tag']);

    if ($problems !== []) {
        $fail($problems);
    }
}

$built = (new Packager($root))->build($out);
$problems = (new ArchiveCheck)->problems($built->path);

if ($problems !== []) {
    unlink($built->path);
    $fail($problems);
}

file_put_contents($built->path.'.sha256', $built->sha256.'  '.basename($built->path)."\n");

if (isset($options['notes'])) {
    file_put_contents($options['notes'], (new ReleaseCheck($root))->notes($built->version)."\n");
}

printf("✓ %s\n  version  %s\n  size     %s KB\n  sha256   %s\n  checks   passed (Teme's upload and unpack rules)\n", $built->path, $built->version, number_format($built->bytes / 1024, 1), $built->sha256);
