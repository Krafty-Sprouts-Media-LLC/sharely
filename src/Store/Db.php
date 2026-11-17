<?php

/**
 * The one place Sharely touches Laravel's database layer.
 *
 * @package KraftySprouts\Sharely
 * @since 1.0.0
 */

declare(strict_types=1);

namespace KraftySprouts\Sharely\Store;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB as Laravel;

/**
 * The one place Sharely touches Laravel's database layer. Nothing in
 * teme/contracts covers a plugin's own tables, and the handbook allows the
 * query builder on them, and only on them, kept in one class. Sharely never
 * queries a table of Teme's.
 *
 * @since 1.0.0
 */
final class Db
{
    /**
     * A query on one of Sharely's own tables.
     *
     * @since 1.0.0
     */
    public static function table(string $name): Builder
    {
        return Laravel::table($name);
    }

    /**
     * Runs a statement with bound values.
     *
     * @param  list<mixed>  $bindings
     *
     * @since 1.0.0
     */
    public static function statement(string $sql, array $bindings = []): bool
    {
        return Laravel::statement($sql, $bindings);
    }

    /**
     * Runs a select with bound values.
     *
     * @param  list<mixed>  $bindings
     * @return list<object>
     *
     * @since 1.0.0
     */
    public static function select(string $sql, array $bindings = []): array
    {
        return array_values(Laravel::select($sql, $bindings));
    }
}
