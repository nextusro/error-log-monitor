<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Actions;

use Nextus\ErrorLogMonitor\Models\LogIssue;

class ReopenIssue
{
    public function handle(LogIssue $issue): void
    {
        $issue->update([
            'resolved_at' => null,
            'ignored_at' => null,
        ]);
    }
}
