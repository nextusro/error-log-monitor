<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Actions;

use Nextus\ErrorLogMonitor\Models\LogIssue;

class DeleteIssue
{
    public function handle(LogIssue $issue): void
    {
        $issue->delete();
    }
}
