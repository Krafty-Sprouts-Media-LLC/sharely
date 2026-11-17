<?php

/**
 * Keeps each save, and refuses as Teme would: a person other than the one
 * the work runs for, a type a plugin may not write, or a key told to fail.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Tests\Support;

use Teme\Contracts\Content\WriteRefused;
use Teme\Contracts\Settings\PluginSettingsWriter;

/**
 * Keeps each save, and refuses as Teme would: a person other than the one
 * the work runs for, a type a plugin may not write, or a key told to fail.
 *
 * @since 1.0.0
 */
final class FakeSettingsWriter implements PluginSettingsWriter
{
    /**
     * Every save that went through, in order: values and person.
     *
     * @var list<array{values: array<string, mixed>, person: string}>
     *
     * @since 1.0.0
     */
    public array $saves = [];

    /**
     * Every attempt, refused or not.
     *
     * @var list<array<string, mixed>>
     *
     * @since 1.0.0
     */
    public array $attempts = [];

    /**
     * @param  string|null  $person  the person the current work runs for; null when no one
     * @param  list<string>  $refuseKeys  keys whose value the "settings page" refuses
     *
     * @since 1.0.0
     */
    public function __construct(
        private readonly ?string $person = 'person-1',
        private readonly array $refuseKeys = [],
    ) {}

    /** @since 1.0.0 */
    public function write(array $values, string $actingPersonId): void
    {
        $this->attempts[] = $values;

        if ($this->person === null || $actingPersonId !== $this->person) {
            throw new WriteRefused('Only the person this work runs for can save these settings.');
        }

        foreach ($values as $key => $value) {
            if (in_array($key, ['networks', 'phone_networks', 'follow', 'facebook_token', 'bitly_token'], true)) {
                throw new WriteRefused('A plugin can’t save this kind of setting.');
            }
            if (in_array($key, $this->refuseKeys, true)) {
                throw new WriteRefused('This value isn’t one the setting allows.');
            }
        }

        $this->saves[] = ['values' => $values, 'person' => $actingPersonId];
    }

    /**
     * Every value saved, merged in order.
     *
     * @return array<string, mixed>
     *
     * @since 1.0.0
     */
    public function saved(): array
    {
        return array_merge([], ...array_column($this->saves, 'values'));
    }
}
