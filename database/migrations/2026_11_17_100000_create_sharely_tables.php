<?php

/**
 * Creates Sharely's own tables.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sharely's own tables, all prefixed sharely_ and listed under `owns` in the
 * manifest: the published items it shares with their overrides, every
 * address an item has had, click counts by day, the counts networks report,
 * and a few named values. None holds anything about a reader.
 *
 * @since 1.0.0
 */
return new class extends Migration
{
    /** @since 1.0.0 */
    public function up(): void
    {
        Schema::create('sharely_items', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('type', 40)->default('');
            $table->string('language', 20)->default('');
            $table->text('title')->default('');
            $table->text('url')->default('');
            $table->boolean('published')->default(true)->index();
            $table->text('description')->default('');
            $table->text('image')->nullable();
            $table->boolean('off')->default(false);
            $table->string('share_text', 280)->nullable();
            $table->string('pin_description', 500)->nullable();
            $table->text('pin_image')->nullable();
            $table->text('short_url')->nullable();
            $table->text('short_for')->nullable();
            $table->timestampTz('counts_checked_at')->nullable();
            $table->timestampTz('updated_at')->nullable();
        });

        Schema::create('sharely_addresses', function (Blueprint $table): void {
            $table->id();
            $table->string('item_id', 26)->index();
            $table->text('url');
            $table->timestampTz('first_seen')->nullable();
            $table->unique(['item_id', 'url']);
        });

        Schema::create('sharely_clicks', function (Blueprint $table): void {
            $table->string('item_id', 26);
            $table->string('network', 20);
            $table->date('day');
            $table->unsignedInteger('clicks')->default(0);
            $table->primary(['item_id', 'network', 'day']);
            $table->index('day');
        });

        Schema::create('sharely_counts', function (Blueprint $table): void {
            $table->string('item_id', 26);
            $table->string('network', 20);
            $table->unsignedBigInteger('count')->default(0);
            $table->timestampTz('checked_at')->nullable();
            $table->primary(['item_id', 'network']);
        });

        Schema::create('sharely_state', function (Blueprint $table): void {
            $table->string('key', 60)->primary();
            $table->text('value');
            $table->timestampTz('updated_at')->nullable();
        });
    }
};
