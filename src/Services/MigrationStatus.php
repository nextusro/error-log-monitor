<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Facades\Schema;

class MigrationStatus
{
    /** @var list<string>|null */
    private ?array $missing = null;

    /**
     * @var array<string, list<string>>
     */
    private const REQUIREMENTS = [
        'error_log_monitor_files' => ['id'],
        'error_log_monitor_issues' => ['id', 'pending_fingerprint', 'regrouping_token'],
        'error_log_monitor_occurrences' => ['id'],
        'error_log_monitor_settings' => ['id'],
        'error_log_monitor_setting_changes' => ['id'],
        'error_log_monitor_notification_states' => ['id'],
        'error_log_monitor_index_runs' => ['id'],
        'error_log_monitor_normalization_rules' => ['id', 'type'],
        'error_log_monitor_grouping_states' => ['id'],
    ];

    public function isCurrent(): bool
    {
        return $this->missingRequirements() === [];
    }

    /** @return list<string> */
    public function missingRequirements(): array
    {
        if ($this->missing !== null) {
            return $this->missing;
        }

        $missing = [];

        foreach (self::REQUIREMENTS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;

                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missing[] = $table.'.'.$column;
                }
            }
        }

        return $this->missing = $missing;
    }
}
