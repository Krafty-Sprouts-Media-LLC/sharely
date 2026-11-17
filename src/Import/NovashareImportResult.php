<?php

/**
 * What became of Novashare's settings: the keys saved, the values not
 * saved and why, the values a plugin may not write (kept for a person to
 * enter), the secrets found by name, and what Sharely left out.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Import;

/**
 * What became of Novashare's settings: the keys saved, the values not
 * saved and why, the values a plugin may not write (kept for a person to
 * enter), the secrets found by name, and what Sharely left out. Never holds
 * a secret's value.
 *
 * @since 1.0.0
 */
final readonly class NovashareImportResult
{
    /**
     * @param  list<string>  $written  the keys saved
     * @param  array<string, mixed>  $unsaved  values a plugin may write that weren't saved, by key
     * @param  string  $refusal  why the values in $unsaved weren't saved, '' when all were
     * @param  array<string, string>  $refusals  a sentence for each key refused on its own
     * @param  array<string, mixed>  $kept  values of settings a plugin may not write (repeaters), by key
     * @param  list<string>  $secrets  the names of secret settings Novashare held a value for
     * @param  list<string>  $leftOut  sentences naming what Sharely couldn't bring over
     *
     * @since 1.0.0
     */
    public function __construct(
        public array $written,
        public array $unsaved,
        public string $refusal,
        public array $refusals,
        public array $kept,
        public array $secrets,
        public array $leftOut,
    ) {}
}
