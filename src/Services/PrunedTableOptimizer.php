<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Facades\DB;

class PrunedTableOptimizer
{
    /**
     * @param  list<string>  $tables
     */
    public function optimize(array $tables): void
    {
        $tables = array_values(array_intersect($tables, $this->allowedTables()));

        if ($tables === []) {
            return;
        }

        match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => $this->optimizeMySql($tables),
            'pgsql' => $this->optimizePostgres($tables),
            'sqlite' => $this->optimizeSqlite(),
            default => null,
        };
    }

    /** @param list<string> $tables */
    private function optimizeMySql(array $tables): void
    {
        foreach ($tables as $table) {
            DB::select("OPTIMIZE TABLE `{$table}`");
        }
    }

    /** @param list<string> $tables */
    private function optimizePostgres(array $tables): void
    {
        foreach ($tables as $table) {
            DB::statement("VACUUM (FULL, ANALYZE) \"{$table}\"");
        }
    }

    private function optimizeSqlite(): void
    {
        if (DB::connection()->getDatabaseName() !== ':memory:') {
            DB::statement('VACUUM');
        }
    }

    /** @return list<string> */
    private function allowedTables(): array
    {
        return [
            'error_log_monitor_occurrences',
            'error_log_monitor_issues',
            'error_log_monitor_index_runs',
        ];
    }
}
