<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor;

use Illuminate\Support\ServiceProvider;
use Nextus\ErrorLogMonitor\Console\IndexErrorLogsCommand;
use Nextus\ErrorLogMonitor\Console\PruneErrorLogsCommand;

class ErrorLogMonitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /** @var array<string, mixed> $packageConfig */
        $packageConfig = require __DIR__.'/../config/error-log-monitor.php';
        $applicationConfig = config('error-log-monitor', []);

        config()->set(
            'error-log-monitor',
            array_replace_recursive($packageConfig, is_array($applicationConfig) ? $applicationConfig : [])
        );
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'error-log-monitor');
        $this->registerRoutes();
        $this->registerViews();
        $this->registerMigrations();
        $this->registerCommands();
        $this->registerPublishing();
    }

    private function registerRoutes(): void
    {
        if (! config('error-log-monitor.route.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    private function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'error-log-monitor');
    }

    private function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            IndexErrorLogsCommand::class,
            PruneErrorLogsCommand::class,
        ]);
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/error-log-monitor.php' => config_path('error-log-monitor.php'),
        ], 'error-log-monitor-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/error-log-monitor'),
        ], 'error-log-monitor-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/error-log-monitor'),
        ], 'error-log-monitor-translations');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'error-log-monitor-migrations');
    }
}
