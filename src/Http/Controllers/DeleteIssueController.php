<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Actions\DeleteIssue;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class DeleteIssueController extends Controller
{
    public function __invoke(
        Request $request,
        LogIssue $issue,
        DeleteIssue $action,
        SettingStore $settings,
    ): RedirectResponse {
        abort_unless(
            (bool) $settings->get('dashboard', 'deletion_enabled'),
            403,
            'Issue deletion is disabled.'
        );

        $action->handle($issue);

        return redirect()
            ->back()
            ->with('error-log-monitor.success', trans('error-log-monitor::messages.issues.deleted'));
    }
}
