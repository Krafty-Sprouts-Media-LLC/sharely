<?php

/**
 * Reads one saved settings group value by value, each checked against its
 * declaration and replaced by the default when it doesn't fit.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Sharing;

/**
 * Reads one saved settings group value by value, each checked against its
 * declaration and replaced by the default when it doesn't fit.
 *
 * @since 1.0.0
 */
final readonly class Values
{
    /**
     * @param  array<string, mixed>  $saved
     *
     * @since 1.0.0
     */
    public function __construct(private array $saved) {}

    /** @since 1.0.0 */
    public function raw(string $key): mixed
    {
        return array_key_exists($key, $this->saved) && $this->saved[$key] !== null
            ? $this->saved[$key]
            : Declared::default($key);
    }

    /** @since 1.0.0 */
    public function string(string $key): string
    {
        $value = $this->raw($key);

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /** @since 1.0.0 */
    public function bool(string $key): bool
    {
        $value = $this->raw($key);

        return is_bool($value) ? $value : in_array($value, [1, '1', 'true', 'on'], true);
    }

    /** @since 1.0.0 */
    public function int(string $key): int
    {
        $value = $this->raw($key);
        $number = is_numeric($value) ? (int) $value : (int) Declared::default($key);
        [$min, $max] = Declared::range($key);

        return max($min, min($max, $number));
    }

    /**
     * A select's value, or its default when the value isn't one of its options.
     *
     * @since 1.0.0
     */
    public function choice(string $key): string
    {
        $value = $this->string($key);

        return in_array($value, Declared::options($key), true) ? $value : (string) Declared::default($key);
    }

    /**
     * A multiselect's values that are among its options, in order.
     *
     * @return list<string>
     *
     * @since 1.0.0
     */
    public function choices(string $key): array
    {
        $options = Declared::options($key);

        return array_values(array_unique(array_filter(
            $this->stringList($key),
            static fn (string $v): bool => in_array($v, $options, true),
        )));
    }

    /**
     * A list of strings, from a list or a JSON list.
     *
     * @return list<string>
     *
     * @since 1.0.0
     */
    public function stringList(string $key): array
    {
        $value = $this->decoded($key);

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    /**
     * A repeater's rows, from a list or a JSON list.
     *
     * @return list<array<string, mixed>>
     *
     * @since 1.0.0
     */
    public function rows(string $key): array
    {
        $value = $this->decoded($key);

        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }

    /**
     * One field of each of a repeater's rows.
     *
     * @return list<mixed>
     *
     * @since 1.0.0
     */
    public function items(string $key, string $field): array
    {
        return array_map(static fn (array $row): mixed => $row[$field] ?? null, $this->rows($key));
    }

    /** @since 1.0.0 */
    private function decoded(string $key): mixed
    {
        $value = $this->raw($key);

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return $value;
    }
}
