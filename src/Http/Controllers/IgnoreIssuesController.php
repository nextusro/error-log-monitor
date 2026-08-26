<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Actions\IgnoreIssues;
use Nextus\ErrorLogMonitor\Http\Requests\BulkIssuesRequest;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class IgnoreIssuesController extends Controller
{
    public function __invoke(
        BulkIssuesRequest $request,
        IgnoreIssues $action,
        SettingStore $settings,
    ): RedirectResponse {
        abort_unless(
            (bool) $settings->get('dashboard', 'bulk_actions_enabled'),
            403,
            'Bulk issue actions are disabled.'
        );

        /** @var list<int|string> $issueIds */
        $issueIds = $request->validated('issue_ids');
        $ignoredIssues = $action->handle(array_map(static fn (int|string $issueId): int => (int) $issueId, $issueIds));

        return redirect()
            ->back()
            ->with('error-log-monitor.success', "{$ignoredIssues} issue-uri au fost ignorate.");
    }
}
