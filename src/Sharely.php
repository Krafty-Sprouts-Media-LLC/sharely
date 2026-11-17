<?php

/**
 * The Sharely plugin: share buttons inside articles and a floating bar,
 * follow buttons, highlight-to-share and pin buttons, click counts kept on
 * the site, network counts and short links fetched by scheduled jobs, a
 * share report in its own area, and a Sharing panel in the editor.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely;

use KraftySprouts\Sharely\Admin\Report;
use KraftySprouts\Sharely\Admin\SharingPanel;
use KraftySprouts\Sharely\Jobs\ApplyImport;
use KraftySprouts\Sharely\Jobs\PanelOptions;
use KraftySprouts\Sharely\Jobs\PanelPreview;
use KraftySprouts\Sharely\Jobs\RefreshCounts;
use KraftySprouts\Sharely\Jobs\Shorten;
use KraftySprouts\Sharely\Jobs\SyncItems;
use KraftySprouts\Sharely\Render\Placer;
use KraftySprouts\Sharely\Sharing\Config;
use KraftySprouts\Sharely\Store\Items;
use KraftySprouts\Sharely\Store\State;
use Teme\Contracts\Admin\HasPluginArea;
use Teme\Contracts\Admin\PluginArea;
use Teme\Contracts\Content\ContentDeleted;
use Teme\Contracts\Content\ContentPublished;
use Teme\Contracts\Content\ContentReader;
use Teme\Contracts\Content\ContentTrashed;
use Teme\Contracts\Content\ContentUnpublished;
use Teme\Contracts\Content\ReadRefused;
use Teme\Contracts\Editor\HasEditorPanels;
use Teme\Contracts\Hooks\Hooks;
use Teme\Contracts\Http\OutboundHttp;
use Teme\Contracts\Insertions\ArticleFacts;
use Teme\Contracts\Insertions\InsertionContributor;
use Teme\Contracts\Insertions\PageFacts;
use Teme\Contracts\Insertions\PageInsertionContributor;
use Teme\Contracts\Jobs\Jobs;
use Teme\Contracts\Jobs\PluginHasJobs;
use Teme\Contracts\Jobs\QueueOptions;
use Teme\Contracts\Plugins\Plugin;
use Teme\Contracts\Settings\PluginSettingsWriter;
use Teme\Contracts\Settings\SecretReader;
use Teme\Contracts\Settings\SettingsChanged;
use Teme\Contracts\Settings\SettingsReader;
use Teme\Contracts\Site\SiteInfo;
use Throwable;

/**
 * The Sharely plugin: share buttons inside articles and a floating bar,
 * follow buttons, highlight-to-share and pin buttons, click counts kept on
 * the site, network counts and short links fetched by scheduled jobs, a
 * share report in its own area, and a Sharing panel in the editor.
 *
 * @since 1.0.0
 */
final class Sharely implements HasEditorPanels, HasPluginArea, InsertionContributor, PageInsertionContributor, Plugin, PluginHasJobs
{
    /** The plugin's settings group: `plugin.` and the plugin's code. @since 1.0.0 */
    public const string GROUP = 'plugin.sharely';

    /** @since 1.0.0 */
    public function __construct(
        private readonly SettingsReader $settings,
        private readonly SecretReader $secrets,
        private readonly ContentReader $content,
        private readonly OutboundHttp $http,
        private readonly Jobs $jobs,
        private readonly SiteInfo $site,
        private readonly PluginSettingsWriter $writer,
    ) {}

    /** @since 1.0.0 */
    public function register(Hooks $hooks): void
    {
        $hooks->on(ContentPublished::class, function (ContentPublished $event): void {
            (new Items)->published($event->item, $this->social($event->item->id, $event->actorId));
            $this->queueShortening();
        });
        $hooks->on(ContentUnpublished::class, static fn (ContentUnpublished $e) => (new Items)->unpublished($e->item->id));
        $hooks->on(ContentTrashed::class, static fn (ContentTrashed $e) => (new Items)->unpublished($e->item->id));
        $hooks->on(ContentDeleted::class, static fn (ContentDeleted $e) => (new Items)->deleted($e->id));
        $hooks->on(SettingsChanged::class, function (SettingsChanged $event): void {
            if ($event->group === self::GROUP) {
                $this->snapshot();
            }
        });
    }

    /** @since 1.0.0 */
    public function precedence(): string
    {
        return 'plugin';
    }

    /** @since 1.0.0 */
    public function insertions(ArticleFacts $article): array
    {
        return $this->placer()->insertions($article);
    }

    /** @since 1.0.0 */
    public function pageInsertions(PageFacts $page): array
    {
        return $this->placer()->pageInsertions($page);
    }

    /** @since 1.0.0 */
    public function jobs(): array
    {
        return [
            'sync-items' => new SyncItems($this->content, $this->settings, fn () => $this->snapshot()),
            'refresh-counts' => new RefreshCounts($this->settings, $this->secrets, $this->http),
            'shorten' => new Shorten($this->settings, $this->secrets, $this->http),
            'panel-preview' => new PanelPreview($this->content, $this->settings),
            'panel-options' => new PanelOptions($this->content),
            'apply-import' => new ApplyImport($this->writer),
        ];
    }

    /** @since 1.0.0 */
    public function pluginArea(): PluginArea
    {
        return new Report;
    }

    /** @since 1.0.0 */
    public function editorPanels(): array
    {
        return ['sharing' => new SharingPanel];
    }

    /**
     * The settings as Sharely reads them, now.
     *
     * @since 1.0.0
     */
    public function config(): Config
    {
        return Config::fromSettings($this->settings->group(self::GROUP));
    }

    /**
     * Copies the settings the public routes need to Sharely's own table,
     * since a route handler is given no doors to read them.
     *
     * @since 1.0.0
     */
    private function snapshot(): void
    {
        (new State)->put(State::SETTINGS, $this->settings->group(self::GROUP));
    }

    /** @since 1.0.0 */
    private function placer(): Placer
    {
        return new Placer($this->config(), $this->site);
    }

    /**
     * The item's social words from its draft, read as the person who
     * published it; nothing when Teme won't let the plugin read it, as for
     * a scheduled publish.
     *
     * @return array{description?: string}
     *
     * @since 1.0.0
     */
    private function social(string $itemId, ?string $actorId): array
    {
        if ($actorId === null) {
            return [];
        }

        try {
            $fields = $this->content->draft($itemId, $actorId)->fields;
        } catch (ReadRefused|Throwable) {
            return [];
        }

        return ['description' => Social::description($fields)];
    }

    /**
     * Queues the shortening job once while one waits, when a shortener is
     * connected. A publish with no person behind it can't queue; the hourly
     * schedule catches up.
     *
     * @since 1.0.0
     */
    private function queueShortening(): void
    {
        if ($this->config()->shortener === 'none') {
            return;
        }

        try {
            $this->jobs->queue('shorten', [], null, new QueueOptions(delaySeconds: 5, uniqueKey: 'shorten'));
        } catch (Throwable) {
            // Nobody to run it as: the hourly schedule makes the link.
        }
    }
}
