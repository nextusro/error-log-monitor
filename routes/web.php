<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nextus\ErrorLogMonitor\Http\Controllers\DashboardController;
use Nextus\ErrorLogMonitor\Http\Controllers\IgnoreIssueController;
use Nextus\ErrorLogMonitor\Http\Controllers\ReopenIssueController;
use Nextus\ErrorLogMonitor\Http\Controllers\ResolveIssueController;

$routeConfig = config('error-log-monitor.route', []);
$middleware = $routeConfig['middleware'] ?? ['web'];

if (! empty($routeConfig['authorization_gate'])) {
    $middleware[] = 'can:' . $routeConfig['authorization_gate'];
}

Route::prefix($routeConfig['prefix'] ?? 'admin/error-log-monitor')
    ->as($routeConfig['name'] ?? 'error-log-monitor.')
    ->middleware($middleware)
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('/issues/{issue}/resolve', ResolveIssueController::class)->name('issues.resolve');
        Route::post('/issues/{issue}/ignore', IgnoreIssueController::class)->name('issues.ignore');
        Route::post('/issues/{issue}/reopen', ReopenIssueController::class)->name('issues.reopen');
    });
