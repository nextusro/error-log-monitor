<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Actions\MarkIssueAsResolved;
use Nextus\ErrorLogMonitor\Models\LogIssue;

class ResolveIssueController extends Controller
{
    public function __invoke(Request $request, LogIssue $issue, MarkIssueAsResolved $action): RedirectResponse
    {
        $action->handle($issue);

        return redirect()
            ->back()
            ->with('error-log-monitor.success', 'Issue updated successfully.');
    }
}
