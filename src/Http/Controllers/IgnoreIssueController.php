<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Actions\MarkIssueAsIgnored;
use Nextus\ErrorLogMonitor\Models\LogIssue;

class IgnoreIssueController extends Controller
{
    public function __invoke(Request $request, LogIssue $issue, MarkIssueAsIgnored $action): RedirectResponse
    {
        $action->handle($issue);

        return redirect()
            ->back()
            ->with('error-log-monitor.success', 'Issue updated successfully.');
    }
}
