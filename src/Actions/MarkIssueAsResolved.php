<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Actions;

use Nextus\ErrorLogMonitor\Models\LogIssue;

class MarkIssueAsResolved
{
    public function handle(LogIssue $issue): void
    {
        $issue->update([
            'resolved_at' => now(),
            'ignored_at' => null,
        ]);
    }
}
