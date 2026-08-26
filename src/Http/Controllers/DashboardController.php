<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Nextus\ErrorLogMonitor\Models\LogFile;
use Nextus\ErrorLogMonitor\Queries\DashboardIssuesQuery;
use Nextus\ErrorLogMonitor\Queries\DashboardStatsQuery;
use Nextus\ErrorLogMonitor\Services\MonitoringState;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardIssuesQuery $issuesQuery,
        DashboardStatsQuery $statsQuery,
        MonitoringState $monitoringState,
    ): View {
        $filters = [
            'level' => $request->query('level'),
            'interval' => $request->query('interval', config('error-log-monitor.dashboard.default_interval', '24h')),
            'query' => $request->query('query'),
            'file' => $request->query('file'),
            'directory' => $request->query('directory'),
            'status' => $request->query('status', 'open'),
            'issue_id' => $request->query('issue_id'),
        ];

        $issues = $issuesQuery->paginate($request);

        $files = LogFile::query()
            ->orderBy('relative_path')
            ->pluck('relative_path')
            ->filter()
            ->values();

        $directories = LogFile::query()
            ->whereNotNull('directory')
            ->distinct()
            ->orderBy('directory')
            ->pluck('directory')
            ->filter()
            ->values();

        $statistics = $statsQuery->build($request);

        return view('error-log-monitor::dashboard', [
            'issues' => $issues,
            'statistics' => $statistics,
            'levels' => config('error-log-monitor.dashboard.levels', []),
            'intervals' => config('error-log-monitor.dashboard.intervals', []),
            'files' => $files,
            'directories' => $directories,
            'filters' => $filters,
            'dateFormat' => config('error-log-monitor.dashboard.date_format', 'Y-m-d H:i:s'),
            'statisticsCollapsedByDefault' => (bool) config('error-log-monitor.dashboard.statistics_collapsed_by_default', false),
            'defaultTheme' => config('error-log-monitor.dashboard.default_theme', 'light'),
            'monitoring' => [
                'enabled' => $monitoringState->isEnabled(),
                'allowed_by_configuration' => $monitoringState->isAllowedByConfiguration(),
                'setting' => $monitoringState->setting(),
            ],
        ]);
    }
}
