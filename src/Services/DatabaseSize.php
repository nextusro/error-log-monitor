<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseSize
{
    public function bytes(): ?int
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => $this->mysqlBytes(),
            'pgsql' => $this->postgresBytes(),
            'sqlite' => $this->sqliteBytes(),
            default => null,
        };
    }

    public function format(?int $bytes): string
    {
        if ($bytes === null) {
            return 'n/a';
        }

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024) {
                return number_format($value, $value >= 10 ? 1 : 2).' '.$unit;
            }

            $value /= 1024;
        }

        return number_format($value, 2).' PB';
    }

    private function mysqlBytes(): ?int
    {
        try {
            $tables = [
                'error_log_monitor_files',
                'error_log_monitor_issues',
                'error_log_monitor_occurrences',
                'error_log_monitor_settings',
                'error_log_monitor_setting_changes',
                'error_log_monitor_notification_states',
            ];
            $placeholders = implode(',', array_fill(0, count($tables), '?'));
            $row = DB::selectOne(
                "select sum(data_length + index_length) as size_bytes
                 from information_schema.tables
                 where table_schema = database() and table_name in ($placeholders)",
                $tables,
            );

            return $row?->size_bytes === null ? null : (int) $row->size_bytes;
        } catch (Throwable) {
            return null;
        }
    }

    private function postgresBytes(): ?int
    {
        try {
            $tables = [
                'error_log_monitor_files',
                'error_log_monitor_issues',
                'error_log_monitor_occurrences',
                'error_log_monitor_settings',
                'error_log_monitor_setting_changes',
                'error_log_monitor_notification_states',
            ];
            $bytes = 0;

            foreach ($tables as $table) {
                $row = DB::selectOne('select pg_total_relation_size(?) as size_bytes', [$table]);
                $bytes += (int) ($row?->size_bytes ?? 0);
            }

            return $bytes;
        } catch (Throwable) {
            return null;
        }
    }

    private function sqliteBytes(): ?int
    {
        $database = DB::connection()->getDatabaseName();

        if (! is_string($database) || $database === ':memory:' || ! is_file($database)) {
            return null;
        }

        $size = filesize($database);

        return $size === false ? null : $size;
    }
}
