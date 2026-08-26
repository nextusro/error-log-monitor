<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nextus\ErrorLogMonitor\Http\Controllers\DashboardController;
use Nextus\ErrorLogMonitor\Http\Controllers\IgnoreIssueController;
use Nextus\ErrorLogMonitor\Http\Controllers\IgnoreIssuesController;
use Nextus\ErrorLogMonitor\Http\Controllers\ReopenIssueController;
use Nextus\ErrorLogMonitor\Http\Controllers\ResolveIssueController;
use Nextus\ErrorLogMonitor\Http\Controllers\ResolveIssuesController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateBulkActionsSettingController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateLocaleSettingController;
use Nextus\ErrorLogMonitor\Http\Controllers\UpdateMonitoringStateController;
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
        Route::put('/settings/monitoring', UpdateMonitoringStateController::class)->name('settings.monitoring.update');
        Route::put('/settings/bulk-actions', UpdateBulkActionsSettingController::class)->name('settings.bulk-actions.update');
        Route::put('/settings/locale', UpdateLocaleSettingController::class)->name('settings.locale.update');
    });
