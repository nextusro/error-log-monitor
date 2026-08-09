<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Actions;

use Nextus\ErrorLogMonitor\Models\LogIssue;

class MarkIssueAsIgnored
{
    public function handle(LogIssue $issue): void
    {
        $issue->update([
            'ignored_at' => now(),
            'resolved_at' => null,
        ]);
    }
}
