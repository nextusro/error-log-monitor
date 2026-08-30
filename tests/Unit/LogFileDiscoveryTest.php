<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Unit;

use Illuminate\Support\Facades\File;
use Nextus\ErrorLogMonitor\Services\LogFileDiscovery;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class LogFileDiscoveryTest extends TestCase
{
    public function test_it_excludes_suffixed_schedule_logs_but_includes_schedule_log(): void
    {
        $nestedDirectory = $this->logDirectory.'/nested';
        File::ensureDirectoryExists($nestedDirectory);

        File::put($this->logDirectory.'/schedule.log', 'included');
        File::put($this->logDirectory.'/schedule-daily.log', 'excluded');
        File::put($nestedDirectory.'/schedule-hourly.log', 'excluded');
        File::put($nestedDirectory.'/laravel.log', 'included');

        config()->set('error-log-monitor.logs.exclude_files', [
            'schedule-*.log',
            '**/schedule-*.log',
        ]);

        $discoveredPaths = app(LogFileDiscovery::class)->discover();

        $this->assertSame([
            $nestedDirectory.'/laravel.log',
            $this->logDirectory.'/schedule.log',
        ], $discoveredPaths);
    }

    public function test_it_supports_additional_exclusion_patterns(): void
    {
        File::put($this->logDirectory.'/laravel.log', 'included');
        File::put($this->logDirectory.'/worker-debug-123.log', 'excluded');

        config()->set('error-log-monitor.logs.exclude_files', ['worker-debug-*.log']);

        $this->assertSame(
            [$this->logDirectory.'/laravel.log'],
            app(LogFileDiscovery::class)->discover(),
        );
    }
}
