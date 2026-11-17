<?php

/**
 * Reads the form values an editor panel's action sends to its job.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Jobs;

/**
 * Reads the form values an editor panel's action sends to its job. The
 * handbook says the payload holds "the form's values" without naming their
 * key, so this accepts them under `values` or `fields`, or at the top level.
 *
 * @since 1.0.0
 */
final class Payload
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @since 1.0.0
     */
    public static function values(array $payload): array
    {
        foreach (['values', 'fields', 'form'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }

        return $payload;
    }
}
