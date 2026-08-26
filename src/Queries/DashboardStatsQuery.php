<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Queries;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Nextus\ErrorLogMonitor\Models\LogFile;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\LogOccurrence;
use Throwable;

class DashboardStatsQuery
{
    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $interval = $this->interval($request);
        $from = $this->resolveIntervalStart($interval);
        $levels = $this->levelsWithoutWarnings();
        $criticalLevels = array_values(array_intersect($levels, ['critical', 'alert', 'emergency']));

        return [
            'interval' => $interval,
            'interval_label' => trans("error-log-monitor::messages.intervals.{$interval}"),
            'from' => $from,
            'cards' => [
                'open_issues' => $this->openIssues($from, $levels),
                'new_issues' => $this->newIssues($from, $levels),
                'occurrences' => $this->occurrences($from, $levels),
                'critical_open' => $this->openIssues($from, $criticalLevels),
                'regressions' => $this->regressions($from, $levels),
                'last_indexed_at' => $this->lastIndexedAt(),
            ],
            'database' => $this->databaseUsage(),
            'top_issues' => $this->topIssues($from, $levels),
            'top_sources' => $this->topSources($from, $levels),
        ];
    }

    /**
     * @return list<string>
     */
    private function levelsWithoutWarnings(): array
    {
        $levels = config('error-log-monitor.dashboard.levels', ['error', 'critical', 'alert', 'emergency']);

        return array_values(array_filter($levels, function (mixed $level): bool {
            return is_string($level) && $level !== 'warning';
        }));
    }

    private function interval(Request $request): string
    {
        $interval = $request->query('interval', config('error-log-monitor.dashboard.default_interval', '24h'));

        if (! is_string($interval) || $interval === '') {
            return (string) config('error-log-monitor.dashboard.default_interval', '24h');
        }

        return $interval;
    }

    private function resolveIntervalStart(string $interval): ?Carbon
    {
        return match ($interval) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '14d' => now()->subDays(14),
            'all' => null,
            default => null,
        };
    }

    /**
     * @param list<string> $levels
     */
    private function openIssues(?Carbon $from, array $levels): int
    {
        if ($levels === []) {
            return 0;
        }

        return LogIssue::query()
            ->whereIn('level', $levels)
            ->open()
            ->when($from, fn ($query) => $query->where('last_seen_at', '>=', $from))
            ->count();
    }

    /**
     * @param list<string> $levels
     */
    private function newIssues(?Carbon $from, array $levels): int
    {
        if ($levels === []) {
            return 0;
        }

        return LogIssue::query()
            ->whereIn('level', $levels)
            ->when($from, fn ($query) => $query->where('first_seen_at', '>=', $from))
            ->count();
    }

    /**
     * @param list<string> $levels
     */
    private function occurrences(?Carbon $from, array $levels): int
    {
        if ($levels === []) {
            return 0;
        }

        return LogOccurrence::query()
            ->whereIn('level', $levels)
            ->when($from, fn ($query) => $query->where('occurred_at', '>=', $from))
            ->count();
    }

    /**
     * @param list<string> $levels
     */
    private function regressions(?Carbon $from, array $levels): int
    {
        if ($levels === []) {
            return 0;
        }

        return LogIssue::query()
            ->whereIn('level', $levels)
            ->regressions()
            ->when($from, fn ($query) => $query->where('last_seen_at', '>=', $from))
            ->count();
    }

    private function lastIndexedAt(): ?Carbon
    {
        /** @var Carbon|string|null $value */
        $value = LogFile::query()->max('last_scanned_at');

        if ($value === null) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    /**
     * @return array{records:int, issues:int, occurrences:int, files:int, size_bytes:int|null, size_label:string}
     */
    private function databaseUsage(): array
    {
        $issues = LogIssue::query()->count();
        $occurrences = LogOccurrence::query()->count();
        $files = LogFile::query()->count();
        $sizeBytes = $this->databaseSizeBytes();

        return [
            'records' => $issues + $occurrences + $files,
            'issues' => $issues,
            'occurrences' => $occurrences,
            'files' => $files,
            'size_bytes' => $sizeBytes,
            'size_label' => $this->formatBytes($sizeBytes),
        ];
    }

    private function databaseSizeBytes(): ?int
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => $this->mysqlTableSizeBytes(),
            'pgsql' => $this->postgresTableSizeBytes(),
            'sqlite' => $this->sqliteDatabaseSizeBytes(),
            default => null,
        };
    }

    private function mysqlTableSizeBytes(): ?int
    {
        try {
            $tableNames = [
                'error_log_monitor_files',
                'error_log_monitor_issues',
                'error_log_monitor_occurrences',
            ];

            $placeholders = implode(',', array_fill(0, count($tableNames), '?'));
            $row = DB::selectOne(
                "select sum(data_length + index_length) as size_bytes
                 from information_schema.tables
                 where table_schema = database()
                   and table_name in ($placeholders)",
                $tableNames,
            );

            if ($row === null || $row->size_bytes === null) {
                return null;
            }

            return (int) $row->size_bytes;
        } catch (Throwable) {
            return null;
        }
    }

    private function postgresTableSizeBytes(): ?int
    {
        try {
            $row = DB::selectOne(<<<'SQL'
                select
                    pg_total_relation_size('error_log_monitor_files') +
                    pg_total_relation_size('error_log_monitor_issues') +
                    pg_total_relation_size('error_log_monitor_occurrences') as size_bytes
            SQL);

            if ($row === null || $row->size_bytes === null) {
                return null;
            }

            return (int) $row->size_bytes;
        } catch (Throwable) {
            return null;
        }
    }

    private function sqliteDatabaseSizeBytes(): ?int
    {
        $database = DB::connection()->getDatabaseName();

        if (! is_string($database) || $database === ':memory:' || ! is_file($database)) {
            return null;
        }

        $size = filesize($database);

        return $size === false ? null : $size;
    }

    private function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return 'n/a';
        }

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024) {
                return number_format($value, $value >= 10 ? 1 : 2) . ' ' . $unit;
            }

            $value /= 1024;
        }

        return number_format($value, 2) . ' PB';
    }

    /**
     * @param list<string> $levels
     * @return list<array{issue:LogIssue, occurrences:int}>
     */
    private function topIssues(?Carbon $from, array $levels): array
    {
        if ($levels === []) {
            return [];
        }

        $rows = LogOccurrence::query()
            ->select('issue_id', DB::raw('COUNT(*) as occurrences'))
            ->whereIn('level', $levels)
            ->when($from, fn ($query) => $query->where('occurred_at', '>=', $from))
            ->groupBy('issue_id')
            ->orderByDesc('occurrences')
            ->limit(5)
            ->get();

        $issueIds = $rows->pluck('issue_id')->filter()->values();

        if ($issueIds->isEmpty()) {
            return [];
        }

        $issues = LogIssue::query()
            ->whereIn('id', $issueIds)
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function ($row) use ($issues): ?array {
                /** @var LogIssue|null $issue */
                $issue = $issues->get($row->issue_id);

                if ($issue === null) {
                    return null;
                }

                return [
                    'issue' => $issue,
                    'occurrences' => (int) $row->occurrences,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param list<string> $levels
     * @return list<array{source:string, occurrences:int}>
     */
    private function topSources(?Carbon $from, array $levels): array
    {
        if ($levels === []) {
            return [];
        }

        return LogOccurrence::query()
            ->selectRaw("COALESCE(NULLIF(file_path_snapshot, ''), '-') as source")
            ->selectRaw('COUNT(*) as occurrences')
            ->whereIn('level', $levels)
            ->when($from, fn ($query) => $query->where('occurred_at', '>=', $from))
            ->groupByRaw("COALESCE(NULLIF(file_path_snapshot, ''), '-')")
            ->orderByDesc('occurrences')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'source' => (string) $row->source,
                'occurrences' => (int) $row->occurrences,
            ])
            ->all();
    }
}
