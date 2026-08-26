<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Actions;

use Nextus\ErrorLogMonitor\Models\LogIssue;

class ResolveIssues
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
                'resolved_at' => now(),
                'ignored_at' => null,
            ]);
    }
}
