<?php

/**
 * Checks a release tag against the manifest and the changelog, and reads the release notes.
 *
 * @package KraftySprouts\Sharely
 * @since 1.1.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Tools;

/**
 * Checks that a release tag, the manifest's version and the changelog's
 * newest heading all say the same version, so the zip, the directory's
 * listing and the notes can't disagree; and reads one version's section of
 * the changelog as the release notes.
 *
 * @since 1.1.0
 */
final class ReleaseCheck
{
    /** @since 1.1.0 */
    public function __construct(private readonly string $root) {}

    /**
     * Every way the tag disagrees with the package; none when it's ready.
     *
     * @return list<string>
     *
     * @since 1.1.0
     */
    public function problems(string $tag): array
    {
        if (preg_match(self::tagPattern(), $tag, $m) !== 1) {
            return ['“'.$tag.'” is not a version tag; tag a release such as v1.2.0.'];
        }

        $problems = [];
        $version = $m[1];
        $manifest = json_decode((string) @file_get_contents($this->root.'/teme-plugin.json'), true);

        if (! is_array($manifest) || ! isset($manifest['version'])) {
            return ['teme-plugin.json can’t be read, or gives no version.'];
        }

        if ($manifest['version'] !== $version) {
            $problems[] = 'The tag is '.$version.' but teme-plugin.json says '.$manifest['version'].'.';
        }

        $newest = $this->newestHeading();

        if ($newest !== $version) {
            $problems[] = 'CHANGELOG.md’s newest version is '.($newest ?? 'missing').', not '.$version.'. Add a "## '.$version.' (date)" section first.';
        }

        return $problems;
    }

    /**
     * One version's section of the changelog, without its heading.
     *
     * @since 1.1.0
     */
    public function notes(string $version): string
    {
        $text = str_replace("\r\n", "\n", (string) @file_get_contents($this->root.'/CHANGELOG.md'));
        $parts = preg_split('/^## /m', $text) ?: [];

        foreach ($parts as $part) {
            if (preg_match('/^\[?v?'.preg_quote($version, '/').'\]?(?:\s|$)/', $part) === 1) {
                $lines = explode("\n", $part, 2);

                return trim($lines[1] ?? '');
            }
        }

        return '';
    }

    /** The pattern of a version tag: v1.2.0 or 1.2.0, with an optional pre-release. @since 1.1.0 */
    public static function tagPattern(): string
    {
        return '/^v?(\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\.\d+)?)$/';
    }

    /** The version in the changelog's first "## " heading, or null. */
    private function newestHeading(): ?string
    {
        $text = (string) @file_get_contents($this->root.'/CHANGELOG.md');

        return preg_match('/^## \[?v?(\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\.\d+)?)\]?/m', $text, $m) === 1 ? $m[1] : null;
    }
}
