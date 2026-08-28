<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Nextus\ErrorLogMonitor\Models\LogFile;
use Nextus\ErrorLogMonitor\Models\IndexRun;
use Nextus\ErrorLogMonitor\Models\NormalizationRule;
use Nextus\ErrorLogMonitor\Queries\DashboardIssuesQuery;
use Nextus\ErrorLogMonitor\Queries\DashboardStatsQuery;
use Nextus\ErrorLogMonitor\Services\MonitoringState;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Services\GroupingStateManager;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardIssuesQuery $issuesQuery,
        DashboardStatsQuery $statsQuery,
        MonitoringState $monitoringState,
        SettingStore $settings,
        GroupingStateManager $groupingStateManager,
    ): View {
        $filters = [
            'level' => $request->query('level'),
            'interval' => $request->query('interval', $settings->get('dashboard', 'default_interval')),
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
        $settingDetails = static fn (string $group, string $key): array => [
            'value' => $settings->get($group, $key),
            'configured' => $settings->configuredValue($group, $key),
            'overridden' => $settings->hasOverride($group, $key),
        ];
        $indexingKeys = [
            'max_runtime_seconds', 'max_files_per_run', 'max_lines_per_file',
            'incomplete_notification_enabled', 'incomplete_notification_mode',
            'stale_after_minutes', 'notification_cooldown_minutes',
            'recovery_notification_enabled', 'run_history_days',
        ];
        $retentionKeys = [
            'occurrences_days', 'max_occurrences_per_issue', 'optimize_tables_after_prune',
            'resolved_issues_days', 'ignored_issues_days', 'open_issues_days',
        ];
        $latestIndexRun = IndexRun::query()->latest('id')->first();

        return view('error-log-monitor::dashboard', [
            'issues' => $issues,
            'statistics' => $statistics,
            'levels' => config('error-log-monitor.dashboard.levels', []),
            'intervals' => collect(config('error-log-monitor.dashboard.intervals', []))
                ->mapWithKeys(static fn (mixed $interval, int|string $key): array => [
                    is_string($key) ? $key : (string) $interval => trans('error-log-monitor::messages.intervals.'.(is_string($key) ? $key : (string) $interval)),
                ])
                ->all(),
            'files' => $files,
            'directories' => $directories,
            'filters' => $filters,
            'dateFormat' => (string) $settings->get('dashboard', 'date_format'),
            'statisticsCollapsedByDefault' => (bool) $settings->get('dashboard', 'statistics_collapsed_by_default'),
            'defaultTheme' => (string) $settings->get('dashboard', 'default_theme'),
            'monitoring' => [
                'enabled' => $monitoringState->isEnabled(),
                'allowed_by_configuration' => $monitoringState->isAllowedByConfiguration(),
                'setting' => $monitoringState->setting(),
            ],
            'bulkActionsEnabled' => (bool) $settings->get('dashboard', 'bulk_actions_enabled'),
            'deletionEnabled' => (bool) $settings->get('dashboard', 'deletion_enabled'),
            'dashboardLocale' => app()->getLocale(),
            'dashboardLocales' => config('error-log-monitor.dashboard.locales', []),
            'dashboardSettings' => collect(['per_page', 'default_interval', 'date_format', 'statistics_collapsed_by_default', 'default_theme'])
                ->mapWithKeys(static fn (string $key): array => [$key => $settingDetails('dashboard', $key)])
                ->all(),
            'notificationSettings' => [
                'enabled' => (bool) $settings->get('notifications', 'enabled'),
                'recipients' => $settings->get('notifications', 'recipients'),
                'regressions_enabled' => (bool) $settings->get('notifications', 'regressions_enabled'),
                'database_size_enabled' => (bool) $settings->get('notifications', 'database_size_enabled'),
                'database_size_threshold_mb' => (int) $settings->get('notifications', 'database_size_threshold_mb'),
                'levels' => $settings->get('notifications', 'levels'),
                'cooldown_minutes' => (int) $settings->get('notifications', 'cooldown_minutes'),
            ],
            'indexingSettings' => collect($indexingKeys)->mapWithKeys(
                static fn (string $key): array => [$key => $settingDetails('indexing', $key)]
            )->all(),
            'retentionSettings' => collect($retentionKeys)->mapWithKeys(
                static fn (string $key): array => [$key => $settingDetails('retention', $key)]
            )->all(),
            'indexingHealth' => [
                'latest' => $latestIndexRun,
                'recent' => IndexRun::query()->latest('id')->limit(10)->get(),
                'partial_runs_24h' => IndexRun::query()->where('started_at', '>=', now()->subDay())->where('status', '!=', 'completed')->count(),
                'oldest_scan_at' => LogFile::query()->where('is_missing', false)->min('last_scanned_at'),
            ],
            'normalizationRules' => NormalizationRule::query()->orderBy('priority')->orderBy('id')->get(),
            'normalizationRegroupPending' => $groupingStateManager->isPending(),
        ]);
    }
}
