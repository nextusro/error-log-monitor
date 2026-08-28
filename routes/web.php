<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nextus\ErrorLogMonitor\Http\Controllers\DashboardController;
use Nextus\ErrorLogMonitor\Http\Controllers\DeleteIssueController;
use Nextus\ErrorLogMonitor\Http\Controllers\DeleteIssuesController;
use Nextus\ErrorLogMonitor\Http\Controllers\IgnoreIssueController;
use Nextus\ErrorLogMonitor\Http\Controllers\IgnoreIssuesController;
use Nextus\ErrorLogMonitor\Http\Controllers\ReopenIssueController;
use Nextus\ErrorLogMonitor\Http\Controllers\ResolveIssueController;
use Nextus\ErrorLogMonitor\Http\Controllers\ResolveIssuesController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateBulkActionsSettingController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateLocaleSettingController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateMonitoringStateController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateNotificationSettingsController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateIndexingSettingsController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateRetentionSettingsController;
use Nextus\ErrorLogMonitor\Http\Controllers\ResetSettingController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateDashboardSettingsController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateDeletionSettingController;
use Nextus\ErrorLogMonitor\Http\Middleware\SetDashboardLocale;

$routeConfig = config('error-log-monitor.route', []);
$middleware = $routeConfig['middleware'] ?? ['web'];

if (! empty($routeConfig['authorization_gate'])) {
    $middleware[] = 'can:' . $routeConfig['authorization_gate'];
}

Route::prefix($routeConfig['prefix'] ?? 'admin/error-log-monitor')
    ->as($routeConfig['name'] ?? 'error-log-monitor.')
    ->middleware([...$middleware, SetDashboardLocale::class])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('/issues/{issue}/resolve', ResolveIssueController::class)->name('issues.resolve');
        Route::post('/issues/resolve', ResolveIssuesController::class)->name('issues.resolve-bulk');
        Route::post('/issues/ignore', IgnoreIssuesController::class)->name('issues.ignore-bulk');
        Route::post('/issues/{issue}/ignore', IgnoreIssueController::class)->name('issues.ignore');
        Route::post('/issues/{issue}/reopen', ReopenIssueController::class)->name('issues.reopen');
        Route::post('/issues/delete', DeleteIssuesController::class)->name('issues.destroy-bulk');
        Route::delete('/issues/{issue}', DeleteIssueController::class)->name('issues.destroy');
        Route::put('/settings/monitoring', UpdateMonitoringStateController::class)->name('settings.monitoring.update');
        Route::put('/settings/bulk-actions', UpdateBulkActionsSettingController::class)->name('settings.bulk-actions.update');
        Route::put('/settings/deletion', UpdateDeletionSettingController::class)->name('settings.deletion.update');
        Route::put('/settings/locale', UpdateLocaleSettingController::class)->name('settings.locale.update');
        Route::put('/settings/notifications', UpdateNotificationSettingsController::class)->name('settings.notifications.update');
        Route::put('/settings/indexing', UpdateIndexingSettingsController::class)->name('settings.indexing.update');
        Route::put('/settings/retention', UpdateRetentionSettingsController::class)->name('settings.retention.update');
        Route::delete('/settings/override', ResetSettingController::class)->name('settings.override.destroy');
        Route::put('/settings/dashboard', UpdateDashboardSettingsController::class)->name('settings.dashboard.update');
    });
