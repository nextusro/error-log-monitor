<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Actions;

use Nextus\ErrorLogMonitor\Models\LogIssue;

class IgnoreIssues
{
    /**
     * @param  list<int>  $issueIds
     */
    public function handle(array $issueIds): int
    {
        return LogIssue::query()
            ->whereKey($issueIds)
            ->open()
            ->update([
                'ignored_at' => now(),
                'resolved_at' => null,
            ]);
    }
}
