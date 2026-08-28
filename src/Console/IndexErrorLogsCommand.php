<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Console;

use Illuminate\Console\Command;
use Nextus\ErrorLogMonitor\Services\LogIndexer;
use Nextus\ErrorLogMonitor\Services\MonitoringState;

class IndexErrorLogsCommand extends Command
{
    protected $signature = 'error-log-monitor:index
        {--fresh : Re-index matching files from the beginning}
        {--file= : Index only one file, by relative or absolute path}';

    protected $description = 'Index Laravel log errors, warnings and critical messages into the Error Log Monitor tables.';

    public function handle(LogIndexer $indexer, MonitoringState $monitoringState): int
    {
        if (! $monitoringState->isEnabled()) {
            $this->warn('Error Log Monitor is disabled. No log entries were indexed.');

            return self::SUCCESS;
        }

        $stats = $indexer->run(
            onlyFile: is_string($this->option('file')) ? $this->option('file') : null,
            fresh: (bool) $this->option('fresh'),
        );

        if ($stats['regrouped']) {
            $this->info(sprintf(
                'Regrouped %d existing issues into %d groups before indexing.',
                $stats['regrouping']['before'],
                $stats['regrouping']['after'],
            ));
        }

        $this->info(sprintf(
            'Indexed %d files, parsed %d entries, stored %d relevant issues. Skipped %d files.',
            $stats['files'],
            $stats['entries'],
            $stats['issues'],
            $stats['skipped'],
        ));

        if (($stats['completed'] ?? true) === false) {
            $this->warn(sprintf(
                'Indexing is incomplete (%s): %d pending, %d partial and %d failed files.',
                $stats['stop_reason'] ?? 'unknown',
                $stats['pending_files'] ?? 0,
                $stats['partially_processed_files'] ?? 0,
                $stats['failed_files'] ?? 0,
            ));
        }

        return self::SUCCESS;
    }
}
