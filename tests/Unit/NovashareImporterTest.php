<?php

/**
 * Tests for novashare importer.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use KraftySprouts\Sharely\Import\NovashareImporter;
use KraftySprouts\Sharely\Import\NovashareMap;
use KraftySprouts\Sharely\Tests\Support\FakeImportScope;
use KraftySprouts\Sharely\Tests\Support\FakeSettingsWriter;
use KraftySprouts\Sharely\Tests\Support\NovashareFixtures;
use Teme\Contracts\Import\ImportOutcome;
use Teme\Contracts\Import\WordPressImporter;
use Teme\Contracts\Import\WordPressRecord;
use Teme\Contracts\Settings\PluginSettingsWriter;

/**
 * The declared options as an import hands them over.
 *
 * @since 1.0.0
 */
function novashareImportOptions(): array
{
    return [
        'novashare' => NovashareFixtures::option(),
        'widget_novashare_follow_widget' => [
            2 => ['selected_networks' => [
                'twitter' => 'https://x.com/gardenbeds',
                'instagram' => 'https://www.instagram.com/gardenbeds',
            ]],
            '_multiwidget' => 1,
        ],
    ];
}

it('is a WordPressImporter asking only for a door Teme gives Sharely', function (): void {
    expect(new NovashareImporter(new FakeSettingsWriter))->toBeInstanceOf(WordPressImporter::class);

    $params = (new ReflectionMethod(NovashareImporter::class, '__construct'))->getParameters();
    expect(array_map(fn (ReflectionParameter $p): string => (string) $p->getType(), $params))
        ->toBe([PluginSettingsWriter::class]);
});

it('maps the options but saves nothing, because the run names no person', function (): void {
    $writer = new FakeSettingsWriter;
    $importer = new NovashareImporter($writer);

    $importer->options(novashareImportOptions(), new FakeImportScope([1 => 'person-1']));

    $result = $importer->lastResult();
    expect($writer->attempts)->toBe([])
        ->and($result->written)->toBe([])
        ->and($result->unsaved)->toHaveKey('inline_position')
        ->and($result->unsaved['inline_position'])->toBe('both')
        ->and($result->refusal)->toContain('who started the import')
        ->and($result->kept['networks'][0])->toBe(['network' => 'x'])
        ->and($result->kept['follow'])->toBe([['network' => 'x', 'address' => 'https://x.com/gardenbeds']])
        ->and($result->secrets)->toBe(['bitly_token', 'facebook_token'])
        ->and(implode(' ', $result->leftOut))->toContain('Instagram');
});

it('copes with options WordPress doesn\'t have or that held a PHP object', function (): void {
    $importer = new NovashareImporter(new FakeSettingsWriter);

    $importer->options(['novashare' => null, 'widget_novashare_follow_widget' => null], new FakeImportScope);

    expect($importer->lastResult()->unsaved)->toBe([])
        ->and($importer->lastResult()->kept)->toBe([])
        ->and($importer->lastResult()->leftOut)->toContain('Novashare’s settings weren’t found on the WordPress site, so there was nothing to bring over.');
});

it('saves the values a plugin may write, for the person the work runs for, in one go', function (): void {
    $writer = new FakeSettingsWriter('person-1');
    $importer = new NovashareImporter($writer);
    $values = NovashareMap::settings(NovashareFixtures::option());

    $result = $importer->save($values, 'person-1');

    expect($writer->saves)->toHaveCount(1)
        ->and($writer->saves[0]['person'])->toBe('person-1')
        ->and($writer->saved())->not->toHaveKey('networks')
        ->and($writer->saved()['inline_position'])->toBe('both')
        ->and($result->written)->toContain('inline_position', 'old_domain', 'highlight_networks')
        ->and($result->kept)->toHaveKey('networks')
        ->and($result->refusals)->toBe([]);
});

it('saves the rest when one value is refused, and records the refusal without throwing', function (): void {
    $writer = new FakeSettingsWriter('person-1', refuseKeys: ['x_account']);
    $importer = new NovashareImporter($writer);

    $result = $importer->save(NovashareMap::settings(NovashareFixtures::option()), 'person-1');

    expect($result->refusals)->toBe(['x_account' => 'This value isn’t one the setting allows.'])
        ->and($result->written)->not->toContain('x_account')
        ->and($result->written)->toContain('inline_position')
        ->and($writer->saved())->not->toHaveKey('x_account')
        ->and($writer->saved()['shape'])->toBe('pill');
});

it('records one refusal and writes nothing when the person is refused', function (): void {
    $writer = new FakeSettingsWriter('person-1');
    $importer = new NovashareImporter($writer);

    $result = $importer->save(NovashareMap::settings(NovashareFixtures::option()), 'someone-else');

    expect($writer->saves)->toBe([])
        ->and($result->refusals)->toBe([])
        ->and($result->written)->toBe([])
        ->and($result->refusal)->toBe('Only the person this work runs for can save these settings.')
        ->and($result->unsaved)->toHaveKey('inline_position');
});

it('skips every record, saying why', function (string $kind): void {
    $outcome = (new NovashareImporter(new FakeSettingsWriter))->record(
        new WordPressRecord($kind, 42, $kind === WordPressRecord::USER ? null : 'post', [], []),
        new FakeImportScope,
    );

    expect($outcome->status)->toBe(ImportOutcome::SKIPPED)
        ->and($outcome->words)->toContain('novashare_meta');
})->with([WordPressRecord::POST, WordPressRecord::USER, WordPressRecord::TERM]);
