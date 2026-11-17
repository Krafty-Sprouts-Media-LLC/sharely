<?php

/**
 * Sharely's importer for Novashare.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Import;

use Teme\Contracts\Content\WriteRefused;
use Teme\Contracts\Import\ImportOutcome;
use Teme\Contracts\Import\ImportScope;
use Teme\Contracts\Import\WordPressImporter;
use Teme\Contracts\Import\WordPressRecord;
use KraftySprouts\Sharely\Store\State;
use Teme\Contracts\Settings\PluginSettingsWriter;
use Throwable;

/**
 * Sharely's importer for Novashare. It maps the `novashare` and follow
 * widget options with NovashareMap, and saves what a plugin may write
 * through PluginSettingsWriter, for a named person. A refused save is
 * recorded in the result, never thrown, so the import goes on.
 *
 * Teme's ImportScope doesn't say who the run acts for, so during an import
 * there is no person to save for: `options()` maps and keeps the result,
 * saving nothing, and `save()` does the saving for a person the caller can
 * name, such as a job's `JobContext::personId()`.
 *
 * @since 1.0.0
 */
final class NovashareImporter implements WordPressImporter
{
    /**
     * Sharely's settings a plugin may not write: repeaters. Secrets never
     * reach the map's values at all.
     *
     * @since 1.0.0
     */
    private const array UNWRITABLE = ['networks', 'phone_networks', 'follow'];

    /** Where the mapped settings wait to be applied, in sharely_state. @since 1.0.0 */
    public const string PENDING = 'novashare-import';

    /**
     * What the last call to options() made of the run's options.
     *
     * @since 1.0.0
     */
    private ?NovashareImportResult $last = null;

    /** @since 1.0.0 */
    public function __construct(
        private readonly PluginSettingsWriter $settings,
    ) {}

    /**
     * Maps the declared options. Nothing is saved, because the run names
     * no person to save for; the result keeps every value, with why.
     *
     * @param  array<string, mixed>  $options
     *
     * @since 1.0.0
     */
    public function options(array $options, ImportScope $scope): void
    {
        $novashare = is_array($options['novashare'] ?? null) ? $options['novashare'] : null;
        $widget = is_array($options['widget_novashare_follow_widget'] ?? null) ? $options['widget_novashare_follow_widget'] : [];

        if ($novashare === null) {
            $follow = NovashareMap::follow($widget);
            $this->last = new NovashareImportResult(
                written: [],
                unsaved: [],
                refusal: '',
                refusals: [],
                kept: $follow === [] ? [] : ['follow' => $follow],
                secrets: [],
                leftOut: [__('Novashare’s settings weren’t found on the WordPress site, so there was nothing to bring over.')],
            );

            return;
        }

        $values = NovashareMap::settings($novashare);
        $follow = NovashareMap::follow($widget);
        if ($follow !== []) {
            $values['follow'] = $follow;
        }

        [$writable, $kept] = $this->split($values);

        self::keep($writable, $kept, NovashareMap::secrets($novashare), NovashareMap::leftOut($novashare, $widget));

        $this->last = new NovashareImportResult(
            written: [],
            unsaved: $writable,
            refusal: __('Sharely couldn’t save Novashare’s settings during the import, because Teme doesn’t tell a plugin who started the import. Set them in Sharely’s settings.'),
            refusals: [],
            kept: $kept,
            secrets: NovashareMap::secrets($novashare),
            leftOut: NovashareMap::leftOut($novashare, $widget),
        );
    }

    /**
     * Skips every record: Sharely declares no post types, people's meta or
     * terms' meta, because Novashare keeps each post's data in its own
     * table, which Teme's import doesn't read.
     *
     * @since 1.0.0
     */
    public function record(WordPressRecord $record, ImportScope $scope): ImportOutcome
    {
        return ImportOutcome::skipped(__('Sharely brings only Novashare’s settings; each post’s Novashare data is in its own novashare_meta table, which Teme’s import doesn’t read.'));
    }

    /**
     * Keeps what the import mapped in Sharely's own table, so the "Apply
     * the Novashare settings" button can save it later as the person who
     * presses it. Never a secret's value: only its name.
     *
     * @param  array<string, mixed>  $writable
     * @param  array<string, mixed>  $kept
     * @param  list<string>  $secrets
     * @param  list<string>  $leftOut
     *
     * @since 1.0.0
     */
    private static function keep(array $writable, array $kept, array $secrets, array $leftOut): void
    {
        try {
            (new State)->put(self::PENDING, compact('writable', 'kept', 'secrets', 'leftOut'));
        } catch (Throwable) {
            // No database (the unit tests): the result stays in memory only.
        }
    }

    /**
     * What the last options() call made of the run's options, or null
     * before one.
     *
     * @since 1.0.0
     */
    public function lastResult(): ?NovashareImportResult
    {
        return $this->last;
    }

    /**
     * Saves the values a plugin may write, for that person, as one save;
     * when that is refused, each value alone, so one bad value doesn't
     * lose the rest. Refusals are recorded in the result, never thrown.
     * Values a plugin may not write come back in the result's `kept`.
     *
     * @param  array<string, mixed>  $values  from NovashareMap
     *
     * @since 1.0.0
     */
    public function save(array $values, string $personId): NovashareImportResult
    {
        [$writable, $kept] = $this->split($values);

        if ($writable === []) {
            return new NovashareImportResult([], [], '', [], $kept, [], []);
        }

        try {
            $this->settings->write($writable, $personId);

            return new NovashareImportResult(array_keys($writable), [], '', [], $kept, [], []);
        } catch (WriteRefused $refused) {
            $whole = $refused->getMessage();
        }

        $written = [];
        $refusals = [];
        foreach ($writable as $key => $value) {
            try {
                $this->settings->write([$key => $value], $personId);
                $written[] = $key;
            } catch (WriteRefused $refused) {
                $refusals[$key] = $refused->getMessage();
            }
        }

        if ($written === []) {
            return new NovashareImportResult([], $writable, $whole, [], $kept, [], []);
        }

        return new NovashareImportResult(
            $written,
            array_intersect_key($writable, $refusals),
            '',
            $refusals,
            $kept,
            [],
            [],
        );
    }

    /**
     * The values a plugin may write, and the rest.
     *
     * @param  array<string, mixed>  $values
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     *
     * @since 1.0.0
     */
    private function split(array $values): array
    {
        $kept = array_intersect_key($values, array_flip(self::UNWRITABLE));

        return [array_diff_key($values, $kept), $kept];
    }
}
