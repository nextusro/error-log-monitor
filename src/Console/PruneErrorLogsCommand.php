<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Console;

use Illuminate\Console\Command;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\LogOccurrence;
use Nextus\ErrorLogMonitor\Models\IndexRun;
use Nextus\ErrorLogMonitor\Services\OccurrenceRetention;
use Nextus\ErrorLogMonitor\Services\PrunedTableOptimizer;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class PruneErrorLogsCommand extends Command
{
    protected $signature = 'error-log-monitor:prune';

    protected $description = 'Prune old Error Log Monitor occurrences and closed issues according to config.';

    public function handle(
        SettingStore $settings,
        OccurrenceRetention $occurrenceRetention,
        PrunedTableOptimizer $optimizer,
    ): int
    {
        $occurrencesDays = $settings->get('retention', 'occurrences_days');
        $deletedOccurrences = 0;

        if (is_int($occurrencesDays) && $occurrencesDays > 0) {
            $deletedOccurrences = $occurrenceRetention->deleteInChunks(
                LogOccurrence::query()->where('occurred_at', '<', now()->subDays($occurrencesDays)),
            );
        }

        $deletedOccurrences += $occurrenceRetention->trimAll(
            (int) $settings->get('retention', 'max_occurrences_per_issue'),
        );

        $deletedResolved = $this->deleteClosedIssues('resolved_at', $settings->get('retention', 'resolved_issues_days'));
        $deletedIgnored = $this->deleteClosedIssues('ignored_at', $settings->get('retention', 'ignored_issues_days'));
        $openDays = (int) $settings->get('retention', 'open_issues_days');
        $deletedOpen = $openDays > 0
            ? LogIssue::query()->whereNull('resolved_at')->whereNull('ignored_at')->where('last_seen_at', '<', now()->subDays($openDays))->delete()
            : 0;
        $historyDays = max(1, (int) $settings->get('indexing', 'run_history_days'));
        $deletedRuns = IndexRun::query()->where('started_at', '<', now()->subDays($historyDays))->delete();

        if ((bool) $settings->get('retention', 'optimize_tables_after_prune')) {
            $optimizer->optimize(array_values(array_filter([
                ($deletedOccurrences + $deletedResolved + $deletedIgnored + $deletedOpen) > 0
                    ? 'error_log_monitor_occurrences'
                    : null,
                ($deletedResolved + $deletedIgnored + $deletedOpen) > 0 ? 'error_log_monitor_issues' : null,
                $deletedRuns > 0 ? 'error_log_monitor_index_runs' : null,
            ])));
        }

        $this->info(sprintf(
            'Deleted %d occurrences, %d resolved issues, %d ignored issues, %d open issues and %d index runs.',
            $deletedOccurrences,
            $deletedResolved,
            $deletedIgnored,
            $deletedOpen,
            $deletedRuns,
        ));

        return self::SUCCESS;
    }

    private function deleteClosedIssues(string $column, mixed $days): int
    {
        if (! is_int($days) || $days <= 0) {
            return 0;
        }

        return LogIssue::query()
            ->whereNotNull($column)
            ->where($column, '<', now()->subDays($days))
            ->delete();
    }
}
