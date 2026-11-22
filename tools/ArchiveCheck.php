<?php

/**
 * Checks a zip against the rules Teme applies when a site uploads and unpacks a plugin.
 *
 * @package KraftySprouts\Sharely
 * @since 1.1.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Tools;

use ZipArchive;

/**
 * Checks a zip against the rules Teme applies when a site uploads and
 * unpacks a plugin: the upload's size and ZIP signature, one package with
 * composer.json (and its manifest beside it) at the root or inside one
 * top-level folder, no path that leaves the folder, no symbolic link, no
 * repeated name, and a composer.json whose name, version and type are right.
 * The limits are Teme 1.3's (`UploadExtension` and `ArchiveReader`); the
 * list of problems is empty when a site would accept the archive. It adds
 * three rules of Teme's code scan that a zip can break: no vendor folder, no
 * Composer files autoloaded on every request, and no requirement on
 * `teme/teme`.
 *
 * @since 1.1.0
 */
final class ArchiveCheck
{
    /** The largest upload Teme accepts by default (TEME_EXTENSION_UPLOAD_MAX). @since 1.1.0 */
    public const int UPLOAD_MAX_BYTES = 33_554_432;

    /** The most entries an archive may hold. @since 1.1.0 */
    public const int MOST_ENTRIES = 20_000;

    /** The most bytes an archive may unpack to. @since 1.1.0 */
    public const int MOST_BYTES = 268_435_456;

    /** The most of composer.json the upload reads. @since 1.1.0 */
    public const int MOST_COMPOSER_BYTES = 65_536;

    /** Composer's pattern for a package name, as Teme applies it. @since 1.1.0 */
    public const string PACKAGE_NAME = '{^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$}';

    /** A plugin's version, as its manifest's schema writes it. @since 1.1.0 */
    public const string VERSION = '/^\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\.\d+)?$/';

    /** Folders that never belong in an uploaded plugin. @since 1.1.0 */
    private const array FORBIDDEN = ['vendor', 'node_modules', '.git'];

    /**
     * Every problem with the archive, in plain words; none when a site
     * would accept it.
     *
     * @return list<string>
     *
     * @since 1.1.0
     */
    public function problems(string $path): array
    {
        $bytes = (string) @file_get_contents($path, false, null, 0, 4);

        if (! is_file($path) || ! str_starts_with($bytes, "PK\x03\x04")) {
            return ['That file isn’t a ZIP archive.'];
        }

        if ((int) filesize($path) > self::UPLOAD_MAX_BYTES) {
            return ['The archive is larger than '.round(self::UPLOAD_MAX_BYTES / 1_048_576, 1).' MB.'];
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY | ZipArchive::CHECKCONS) !== true) {
            return ['The upload isn’t a ZIP archive the installer can read.'];
        }

        try {
            $problems = [];
            $entries = $this->entries($zip, $problems);

            if ($problems !== []) {
                return $problems;
            }

            $root = $this->root($entries, $problems);

            if ($problems !== []) {
                return $problems;
            }

            return $this->package($zip, $entries, $root);
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  list<string>  $problems
     * @return array<int, string>
     */
    private function entries(ZipArchive $zip, array &$problems): array
    {
        if ($zip->numFiles > self::MOST_ENTRIES) {
            $problems[] = 'The archive holds more than '.number_format(self::MOST_ENTRIES).' files.';

            return [];
        }

        $entries = [];
        $seen = [];
        $total = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i, ZipArchive::FL_ENC_RAW);
            $name = $zip->getNameIndex($i, ZipArchive::FL_ENC_RAW);

            if ($stat === false || $name === false) {
                $problems[] = 'The upload isn’t a ZIP archive the installer can read.';

                return [];
            }

            if (! self::safeName($name)) {
                $problems[] = 'The archive holds a file whose name could reach outside its folder: '.self::shown($name).'.';

                continue;
            }

            if ($zip->getExternalAttributesIndex($i, $system, $attributes) && $system === ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0170000) === 0120000) {
                $problems[] = 'The archive holds a symbolic link: '.self::shown($name).'.';
            }

            if (isset($seen[$name])) {
                $problems[] = 'The archive holds '.self::shown($name).' twice.';
            }

            $total += (int) $stat['size'];
            $entries[$i] = $name;
            $seen[$name] = true;
        }

        if ($total > self::MOST_BYTES) {
            $problems[] = 'The archive unpacks to more than '.round(self::MOST_BYTES / 1_048_576).' MB.';
        }

        return $entries;
    }

    /**
     * Whether an entry's name stays inside the folder it is unpacked into:
     * relative, with no empty, "." or ".." part, no backslash, no NUL and no
     * drive letter.
     */
    private static function safeName(string $name): bool
    {
        if ($name === '' || str_starts_with($name, '/') || str_contains($name, '\\') || str_contains($name, "\0") || preg_match('/^[A-Za-z]:/', $name) === 1) {
            return false;
        }

        foreach (explode('/', str_ends_with($name, '/') ? substr($name, 0, -1) : $name) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                return false;
            }
        }

        return true;
    }

    /**
     * The folder the package sits in: none when composer.json is at the
     * root, else the one top-level folder every entry is in.
     *
     * @param  array<int, string>  $entries
     * @param  list<string>  $problems
     */
    private function root(array $entries, array &$problems): string
    {
        if (in_array('composer.json', $entries, true)) {
            return '';
        }

        $tops = array_unique(array_map(static fn (string $n): string => explode('/', $n, 2)[0], $entries));

        if (count($tops) !== 1) {
            $problems[] = 'The archive must hold one package: composer.json at its root, or one folder with composer.json in it.';

            return '';
        }

        return reset($tops).'/';
    }

    /**
     * @param  array<int, string>  $entries
     * @return list<string>
     */
    private function package(ZipArchive $zip, array $entries, string $root): array
    {
        $problems = [];

        foreach ($entries as $name) {
            $inside = substr($name, strlen($root));

            if (in_array(explode('/', $inside)[0], self::FORBIDDEN, true)) {
                $problems[] = 'The archive holds a '.explode('/', $inside)[0].' folder, which a plugin never ships: '.self::shown($name).'.';
                break;
            }
        }

        $composer = $this->json($zip, $root.'composer.json', 'composer.json', $problems);
        $manifest = $this->json($zip, $root.'teme-plugin.json', 'teme-plugin.json', $problems, 'The archive has no teme-plugin.json beside its composer.json.');

        if ($composer === null) {
            return $problems;
        }

        $name = $composer['name'] ?? null;
        $version = $composer['version'] ?? null;

        if (! is_string($name) || strlen($name) > 214 || preg_match(self::PACKAGE_NAME, $name) !== 1) {
            $problems[] = 'The archive’s composer.json must give the package’s name, such as vendor/name.';
        }

        if (! is_string($version) || preg_match(self::VERSION, $version) !== 1) {
            $problems[] = 'The archive’s composer.json must give the package’s version, such as 1.2.0.';
        }

        if (($composer['type'] ?? null) !== 'teme-plugin') {
            $problems[] = 'The archive’s composer.json must give the type teme-plugin.';
        }

        if (is_array($composer['autoload']['files'] ?? null) && $composer['autoload']['files'] !== []) {
            $problems[] = 'The archive’s composer.json loads files on every request (autoload.files); Teme refuses that.';
        }

        if (array_key_exists('teme/teme', (array) ($composer['require'] ?? []))) {
            $problems[] = 'The archive’s composer.json requires teme/teme; a site’s own Teme provides it, so require only teme/contracts.';
        }

        if ($manifest !== null && isset($manifest['version']) && $manifest['version'] !== $version) {
            $problems[] = 'composer.json and teme-plugin.json must give the same version ('.json_encode($version).' and '.json_encode($manifest['version']).').';
        }

        if ($manifest !== null && is_string($name) && isset($manifest['name']) && $manifest['name'] !== $name) {
            $problems[] = 'composer.json and teme-plugin.json must give the same name.';
        }

        return $problems;
    }

    /**
     * One JSON file of the package, read as data.
     *
     * @param  list<string>  $problems
     * @return array<string, mixed>|null
     */
    private function json(ZipArchive $zip, string $name, string $label, array &$problems, ?string $missing = null): ?array
    {
        $stat = $zip->statName($name);

        if ($stat === false) {
            $problems[] = $missing ?? 'The archive has no '.$label.' at its top, or one Teme can’t read.';

            return null;
        }

        if ((int) $stat['size'] > self::MOST_COMPOSER_BYTES) {
            $problems[] = 'The archive’s '.$label.' is larger than '.(self::MOST_COMPOSER_BYTES / 1024).' KB.';

            return null;
        }

        $decoded = json_decode((string) $zip->getFromName($name, self::MOST_COMPOSER_BYTES), true);

        if (! is_array($decoded)) {
            $problems[] = 'The archive’s '.$label.' isn’t valid JSON.';

            return null;
        }

        return $decoded;
    }

    /** An entry's name as it can be shown: printable, and not too long. */
    private static function shown(string $name): string
    {
        $printable = (string) preg_replace('/[^\x20-\x7E]/', '?', $name);

        return strlen($printable) > 120 ? substr($printable, 0, 117).'...' : $printable;
    }
}
