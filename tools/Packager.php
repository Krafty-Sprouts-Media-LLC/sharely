<?php

/**
 * Builds the zip a site can upload from Plugins › Add plugin.
 *
 * @package KraftySprouts\Sharely
 * @since 1.1.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Tools;

use RuntimeException;
use ZipArchive;

/**
 * Builds the zip a site can upload from Plugins › Add plugin: one top-level
 * folder holding only what a site runs (the source, the migrations, the
 * resources, the words, the manifest, the README, the changelog and the
 * license), with a composer.json that carries the version Teme's upload
 * needs and nothing only a developer needs. Every file is added in a fixed
 * order with a fixed time and mode, so the same source always makes the same
 * bytes and its checksum can be trusted.
 *
 * @since 1.1.0
 */
final class Packager
{
    /** Folders of the package that ship, when they exist. @since 1.1.0 */
    public const array FOLDERS = ['src', 'database', 'resources', 'lang', 'art'];

    /** Files at the package's root that ship, besides composer.json. @since 1.1.0 */
    public const array FILES = ['teme-plugin.json', 'README.md', 'CHANGELOG.md', 'LICENSE'];

    /** Keys of composer.json a site needs, in the order they are written. @since 1.1.0 */
    public const array COMPOSER_KEYS = ['name', 'description', 'type', 'license', 'authors', 'support', 'version', 'require', 'autoload'];

    /** The time every entry carries: the zip format can't hold a date before 1980. @since 1.1.0 */
    public const string TIME = '2026-01-01 00:00:00 UTC';

    /** Files that are text, so their line endings are made the same on every machine. */
    private const string TEXT = '/\.(php|js|css|json|md|txt|svg)$|(^|\/)LICENSE$/';

    /** @since 1.1.0 */
    public function __construct(private readonly string $root) {}

    /**
     * Builds sharely-{version}.zip in the folder, replacing one that is there.
     *
     * @throws RuntimeException when the package can't be read or the zip can't be written
     *
     * @since 1.1.0
     */
    public function build(string $outDir): Built
    {
        $manifest = $this->read('teme-plugin.json');
        $version = (string) ($manifest['version'] ?? '');
        $name = (string) ($this->read('composer.json')['name'] ?? '');
        $folder = substr($name, (int) strpos($name, '/') + 1);

        if ($version === '' || $folder === '') {
            throw new RuntimeException('teme-plugin.json gives no version, or composer.json no name.');
        }

        if (! is_dir($outDir) && ! mkdir($outDir, 0777, true) && ! is_dir($outDir)) {
            throw new RuntimeException('The folder '.$outDir.' can’t be made.');
        }

        $path = rtrim($outDir, '/\\').DIRECTORY_SEPARATOR.$folder.'-'.$version.'.zip';

        if (is_file($path)) {
            unlink($path);
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The zip '.$path.' can’t be written.');
        }

        $time = (int) strtotime(self::TIME);

        foreach ($this->files($version) as $relative => $contents) {
            $entry = $folder.'/'.$relative;

            $zip->addFromString($entry, $contents);
            $zip->setCompressionName($entry, ZipArchive::CM_DEFLATE, 9);
            $zip->setMtimeName($entry, $time);
            $zip->setExternalAttributesName($entry, ZipArchive::OPSYS_UNIX, 0100644 << 16);
        }

        if (! $zip->close()) {
            throw new RuntimeException('The zip '.$path.' couldn’t be finished.');
        }

        return new Built($path, $version, (int) filesize($path), (string) hash_file('sha256', $path));
    }

    /**
     * What goes in the zip, by path inside the package, in a fixed order.
     *
     * @return array<string, string>
     *
     * @since 1.1.0
     */
    public function files(string $version): array
    {
        $files = ['composer.json' => $this->composerJson($version)];

        foreach (self::FILES as $file) {
            if (is_file($this->root.'/'.$file)) {
                $files[$file] = $this->contents($file);
            }
        }

        foreach (self::FOLDERS as $folder) {
            foreach ($this->under($folder) as $relative) {
                $files[$relative] = $this->contents($relative);
            }
        }

        ksort($files, SORT_STRING);

        return $files;
    }

    /**
     * composer.json as a site needs it: only the keys a site reads, with the
     * version added. The version is not kept in the repository's own
     * composer.json, since Composer takes it from the tag there.
     *
     * @since 1.1.0
     */
    public function composerJson(string $version): string
    {
        $source = $this->read('composer.json');
        $source['version'] = $version;
        $out = [];

        foreach (self::COMPOSER_KEYS as $key) {
            if (array_key_exists($key, $source)) {
                $out[$key] = $source[$key];
            }
        }

        return json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * Every ordinary file under a folder of the package, by path, skipping
     * symbolic links and the files an operating system leaves behind.
     *
     * @return list<string>
     */
    private function under(string $folder): array
    {
        $base = $this->root.'/'.$folder;

        if (! is_dir($base)) {
            return [];
        }

        $found = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if ($file->isLink() || ! $file->isFile() || in_array($file->getFilename(), ['.DS_Store', 'Thumbs.db', 'desktop.ini'], true)) {
                continue;
            }

            $found[] = $folder.'/'.str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
        }

        sort($found, SORT_STRING);

        return $found;
    }

    /** A file's contents, with the same line endings on every machine when it is text. */
    private function contents(string $relative): string
    {
        $contents = (string) file_get_contents($this->root.'/'.$relative);

        return preg_match(self::TEXT, $relative) === 1 ? str_replace("\r\n", "\n", $contents) : $contents;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $file): array
    {
        $decoded = json_decode((string) @file_get_contents($this->root.'/'.$file), true);

        if (! is_array($decoded)) {
            throw new RuntimeException($file.' can’t be read.');
        }

        return $decoded;
    }
}
