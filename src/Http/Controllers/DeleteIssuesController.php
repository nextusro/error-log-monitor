<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Actions\DeleteIssues;
use Nextus\ErrorLogMonitor\Http\Requests\BulkIssuesRequest;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class DeleteIssuesController extends Controller
{
    public function __invoke(
        BulkIssuesRequest $request,
        DeleteIssues $action,
        SettingStore $settings,
    ): RedirectResponse {
        abort_unless(
            (bool) $settings->get('dashboard', 'deletion_enabled'),
            403,
            'Issue deletion is disabled.'
        );

        /** @var list<int|string> $issueIds */
        $issueIds = $request->validated('issue_ids');
        $deletedIssues = $action->handle(array_map(static fn (int|string $issueId): int => (int) $issueId, $issueIds));

        return redirect()
            ->back()
            ->with('error-log-monitor.success', trans('error-log-monitor::messages.bulk.deleted', ['count' => $deletedIssues]));
    }
}
