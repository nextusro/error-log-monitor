<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests;

use Illuminate\Support\Facades\File;
use Nextus\ErrorLogMonitor\ErrorLogMonitorServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $logDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDirectory = storage_path('framework/testing/error-log-monitor-'.uniqid());
        File::ensureDirectoryExists($this->logDirectory);

        config()->set('error-log-monitor.logs.base_path', $this->logDirectory);
        config()->set('error-log-monitor.logs.include_files', ['*.log', '**/*.log']);
        config()->set('error-log-monitor.logs.exclude_files', []);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->logDirectory);

        parent::tearDown();
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ErrorLogMonitorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('error-log-monitor.route.middleware', ['web']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
