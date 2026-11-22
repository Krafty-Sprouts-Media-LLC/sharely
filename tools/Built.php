<?php

/**
 * What the packager made: the zip's path, version, size and checksum.
 *
 * @package KraftySprouts\Sharely
 * @since 1.1.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Tools;

/**
 * What the packager made: the zip's path, the version it holds, its size in
 * bytes and its SHA-256.
 *
 * @since 1.1.0
 */
final readonly class Built
{
    /** @since 1.1.0 */
    public function __construct(
        public string $path,
        public string $version,
        public int $bytes,
        public string $sha256,
    ) {}
}
