<?php

/**
 * Tests for the packager that builds the zip a site can upload.
 *
 * @package KraftySprouts\Sharely
 * @since 1.1.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Tools\ArchiveCheck;
use KraftySprouts\Sharely\Tools\Packager;
use KraftySprouts\Sharely\Tools\ReleaseCheck;

const PACKAGER_ROOT = __DIR__.'/../..';

function packagerOut(): string
{
    $dir = sys_get_temp_dir().'/sharely-package-test-'.bin2hex(random_bytes(4));
    mkdir($dir);

    return $dir;
}

/** @return list<string> */
function zipNames(string $path): array
{
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::RDONLY);
    $names = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = (string) $zip->getNameIndex($i);
    }

    $zip->close();

    return $names;
}

function zipText(string $path, string $name): string
{
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::RDONLY);
    $text = (string) $zip->getFromName($name);
    $zip->close();

    return $text;
}

/**
 * A zip made by hand, for the cases a good build never makes.
 *
 * @param  array<string, string>  $files
 * @param  array<string, int>  $modes  Unix mode by name
 */
function craftedZip(array $files, array $modes = []): string
{
    $path = sys_get_temp_dir().'/sharely-crafted-'.bin2hex(random_bytes(4)).'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);

    foreach ($files as $name => $body) {
        $zip->addFromString($name, $body);

        if (isset($modes[$name])) {
            $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, $modes[$name] << 16);
        }
    }

    $zip->close();

    return $path;
}

function goodComposer(string $version = '1.0.0', array $over = []): string
{
    return (string) json_encode(array_merge(['name' => 'krafty-sprouts/sharely', 'type' => 'teme-plugin', 'version' => $version], $over));
}

function goodManifest(string $version = '1.0.0'): string
{
    return (string) json_encode(['manifest' => 1, 'name' => 'krafty-sprouts/sharely', 'code' => 'sharely', 'version' => $version]);
}

it('builds sharely-{version}.zip, named after the manifest’s version, and answers its checksum', function (): void {
    $out = packagerOut();
    $built = (new Packager(PACKAGER_ROOT))->build($out);
    $manifest = json_decode((string) file_get_contents(PACKAGER_ROOT.'/teme-plugin.json'), true);

    expect(basename($built->path))->toBe('sharely-'.$manifest['version'].'.zip')
        ->and($built->version)->toBe($manifest['version'])
        ->and($built->sha256)->toBe(hash_file('sha256', $built->path))
        ->and($built->bytes)->toBe(filesize($built->path));
});

it('puts everything in one top-level folder, with the manifest beside composer.json', function (): void {
    $built = (new Packager(PACKAGER_ROOT))->build(packagerOut());
    $names = zipNames($built->path);

    $tops = array_unique(array_map(static fn (string $n): string => explode('/', $n, 2)[0], $names));

    expect($tops)->toBe(['sharely'])
        ->and($names)->toContain('sharely/composer.json')
        ->and($names)->toContain('sharely/teme-plugin.json')
        ->and($names)->toContain('sharely/README.md')
        ->and($names)->toContain('sharely/CHANGELOG.md')
        ->and($names)->toContain('sharely/LICENSE')
        ->and($names)->toContain('sharely/src/Sharely.php')
        ->and($names)->toContain('sharely/resources/js/actions.js')
        ->and($names)->toContain('sharely/resources/css/page.css')
        ->and($names)->toContain('sharely/database/migrations/2026_11_17_100000_create_sharely_tables.php')
        ->and($names)->toContain('sharely/lang/en.json');
});

it('gives the zip’s composer.json the version Teme’s upload needs, and nothing a site doesn’t', function (): void {
    $built = (new Packager(PACKAGER_ROOT))->build(packagerOut());
    $composer = json_decode(zipText($built->path, 'sharely/composer.json'), true);

    expect($composer['name'])->toBe('krafty-sprouts/sharely')
        ->and($composer['type'])->toBe('teme-plugin')
        ->and($composer['version'])->toBe($built->version)
        ->and($composer['license'])->toBe('GPL-2.0-or-later')
        ->and($composer['require'])->toHaveKey('teme/contracts')
        ->and($composer['autoload']['psr-4'])->toHaveKey('KraftySprouts\\Sharely\\')
        ->and($composer)->not->toHaveKeys(['require-dev', 'autoload-dev', 'repositories', 'scripts', 'config', 'minimum-stability']);
});

it('leaves out tests, tools, vendor, git, workflows, working notes and lock files', function (): void {
    $names = zipNames((new Packager(PACKAGER_ROOT))->build(packagerOut())->path);

    foreach ($names as $name) {
        expect($name)->not->toMatch('#^sharely/(tests|tools|vendor|node_modules|\.git|\.github|dist)(/|$)#')
            ->and($name)->not->toMatch('#^sharely/(AGENTS|FINDINGS|DECISIONS|FEATURES|REPORT|RELEASING)\.md$#')
            ->and($name)->not->toMatch('#(composer\.lock|phpunit\.xml|\.gitattributes|\.gitignore|\.DS_Store|Thumbs\.db)$#');
    }
});

it('builds the same bytes every time, so a checksum can be trusted', function (): void {
    $a = (new Packager(PACKAGER_ROOT))->build(packagerOut());
    $b = (new Packager(PACKAGER_ROOT))->build(packagerOut());

    expect($a->sha256)->toBe($b->sha256);
});

it('is far below Teme’s upload limit and entry count', function (): void {
    $built = (new Packager(PACKAGER_ROOT))->build(packagerOut());

    expect($built->bytes)->toBeLessThan(ArchiveCheck::UPLOAD_MAX_BYTES / 4)
        ->and(count(zipNames($built->path)))->toBeLessThan(ArchiveCheck::MOST_ENTRIES / 10);
});

it('passes the same checks Teme makes when a site uploads and unpacks it', function (): void {
    $built = (new Packager(PACKAGER_ROOT))->build(packagerOut());

    expect((new ArchiveCheck)->problems($built->path))->toBe([]);
});

it('refuses what Teme refuses', function (string $why, array $files, array $modes, string $expected): void {
    $problems = (new ArchiveCheck)->problems(craftedZip($files, $modes));

    expect(implode(' | ', $problems))->toContain($expected);
})->with([
    'no composer.json' => ['x', ['sharely/teme-plugin.json' => goodManifest()], [], 'composer.json'],
    'no manifest' => ['x', ['sharely/composer.json' => goodComposer()], [], 'teme-plugin.json'],
    'composer.json without a version' => ['x', ['sharely/composer.json' => json_encode(['name' => 'krafty-sprouts/sharely', 'type' => 'teme-plugin']), 'sharely/teme-plugin.json' => goodManifest()], [], 'version'],
    'a version that is not one' => ['x', ['sharely/composer.json' => goodComposer('latest'), 'sharely/teme-plugin.json' => goodManifest('latest')], [], 'version'],
    'the wrong type' => ['x', ['sharely/composer.json' => goodComposer('1.0.0', ['type' => 'library']), 'sharely/teme-plugin.json' => goodManifest()], [], 'type'],
    'manifest and composer disagree' => ['x', ['sharely/composer.json' => goodComposer('1.0.0'), 'sharely/teme-plugin.json' => goodManifest('1.0.1')], [], 'same version'],
    'a path that leaves the folder' => ['x', ['sharely/composer.json' => goodComposer(), 'sharely/teme-plugin.json' => goodManifest(), 'sharely/../evil.php' => '<?php'], [], 'outside'],
    'an absolute path' => ['x', ['sharely/composer.json' => goodComposer(), 'sharely/teme-plugin.json' => goodManifest(), '/etc/passwd' => 'x'], [], 'outside'],
    'a backslash in a name' => ['x', ['sharely/composer.json' => goodComposer(), 'sharely/teme-plugin.json' => goodManifest(), 'sharely\\evil.php' => 'x'], [], 'outside'],
    'a symbolic link' => ['x', ['sharely/composer.json' => goodComposer(), 'sharely/teme-plugin.json' => goodManifest(), 'sharely/link' => 'target'], ['sharely/link' => 0120777], 'symbolic link'],
    'two top-level folders' => ['x', ['a/composer.json' => goodComposer(), 'a/teme-plugin.json' => goodManifest(), 'b/x.php' => 'x'], [], 'one package'],
    'a vendor folder' => ['x', ['sharely/composer.json' => goodComposer(), 'sharely/teme-plugin.json' => goodManifest(), 'sharely/vendor/x.php' => 'x'], [], 'vendor'],
    'Composer loading files on every request' => ['x', ['sharely/composer.json' => goodComposer('1.0.0', ['autoload' => ['files' => ['x.php']]]), 'sharely/teme-plugin.json' => goodManifest()], [], 'files'],
    'requiring Teme itself' => ['x', ['sharely/composer.json' => goodComposer('1.0.0', ['require' => ['teme/teme' => '*']]), 'sharely/teme-plugin.json' => goodManifest()], [], 'teme/teme'],
]);

it('refuses a file that is not a zip', function (): void {
    $path = sys_get_temp_dir().'/sharely-not-a-zip-'.bin2hex(random_bytes(4)).'.zip';
    file_put_contents($path, 'not a zip at all');

    expect(implode(' | ', (new ArchiveCheck)->problems($path)))->toContain('ZIP');
});

it('accepts a version tag only when it matches the manifest and the changelog', function (): void {
    $manifest = json_decode((string) file_get_contents(PACKAGER_ROOT.'/teme-plugin.json'), true);
    $version = $manifest['version'];
    $check = new ReleaseCheck(PACKAGER_ROOT);

    expect($check->problems('v'.$version))->toBe([])
        ->and($check->problems($version))->toBe([])
        ->and(implode(' | ', $check->problems('v9.9.9')))->toContain('9.9.9')
        ->and(implode(' | ', $check->problems('release-1')))->toContain('not a version tag');
});

it('refuses a changelog whose newest version is not the manifest’s', function (): void {
    $root = sys_get_temp_dir().'/sharely-release-test-'.bin2hex(random_bytes(4));
    mkdir($root);
    file_put_contents($root.'/teme-plugin.json', goodManifest('1.2.0'));
    file_put_contents($root.'/CHANGELOG.md', "# Changelog\n\n## 1.1.0 (2026-01-01)\n\n- Old.\n");

    expect(implode(' | ', (new ReleaseCheck($root))->problems('v1.2.0')))->toContain('CHANGELOG.md');
});

it('takes the release notes from the changelog’s section for that version', function (): void {
    $root = sys_get_temp_dir().'/sharely-notes-test-'.bin2hex(random_bytes(4));
    mkdir($root);
    file_put_contents($root.'/teme-plugin.json', goodManifest('1.2.0'));
    file_put_contents($root.'/CHANGELOG.md', "# Changelog\n\n## 1.2.0 (2026-02-01)\n\n- New thing.\n- Another.\n\n## 1.1.0 (2026-01-01)\n\n- Old.\n");

    $notes = (new ReleaseCheck($root))->notes('1.2.0');

    expect($notes)->toContain('New thing')
        ->and($notes)->toContain('Another')
        ->and($notes)->not->toContain('Old.');
});
