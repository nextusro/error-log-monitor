<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Console;

use Illuminate\Console\Command;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\LogOccurrence;

class PruneErrorLogsCommand extends Command
{
    protected $signature = 'error-log-monitor:prune';

    protected $description = 'Prune old Error Log Monitor occurrences and closed issues according to config.';

    public function handle(): int
    {
        $occurrencesDays = config('error-log-monitor.retention.occurrences_days');
        $deletedOccurrences = 0;

        if (is_int($occurrencesDays) && $occurrencesDays > 0) {
            $deletedOccurrences = LogOccurrence::query()
                ->where('occurred_at', '<', now()->subDays($occurrencesDays))
                ->delete();
        }

        $deletedResolved = $this->deleteClosedIssues('resolved_at', config('error-log-monitor.retention.resolved_issues_days'));
        $deletedIgnored = $this->deleteClosedIssues('ignored_at', config('error-log-monitor.retention.ignored_issues_days'));

        $this->info(sprintf(
            'Deleted %d occurrences, %d resolved issues, %d ignored issues.',
            $deletedOccurrences,
            $deletedResolved,
            $deletedIgnored,
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
