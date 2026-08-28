<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Actions;

use Nextus\ErrorLogMonitor\Models\LogIssue;

class DeleteIssues
{
    /**
     * @param  list<int>  $issueIds
     */
    public function handle(array $issueIds): int
    {
        return LogIssue::query()
            ->whereKey($issueIds)
            ->delete();
    }
}
