<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Nextus\ErrorLogMonitor\Actions\ChangeMonitoringState;
use Nextus\ErrorLogMonitor\Models\LogFile;
use Nextus\ErrorLogMonitor\Services\LogIndexer;
use Nextus\ErrorLogMonitor\Services\MonitoringState;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class MonitoringStateTest extends TestCase
{
    public function test_suspended_monitoring_does_not_index_log_files(): void
    {
        File::put($this->logDirectory.'/laravel.log', "[2026-08-26 10:00:00] production.ERROR: Failure\n");
        app(ChangeMonitoringState::class)->handle(false, null, null);

        $stats = app(LogIndexer::class)->run();

        $this->assertSame([
            'files' => 0,
            'entries' => 0,
            'issues' => 0,
            'skipped' => 0,
        ], $stats);
        $this->assertDatabaseCount('error_log_monitor_files', 0);
    }

    public function test_reactivating_from_now_moves_each_cursor_to_the_current_file_end(): void
    {
        $path = $this->logDirectory.'/laravel.log';
        File::put($path, 'existing log content');
        app(ChangeMonitoringState::class)->handle(false, null, null);

        $movedCursors = app(ChangeMonitoringState::class)->handle(true, 'from_now', null);

        $this->assertSame(1, $movedCursors);
        $this->assertTrue(app(MonitoringState::class)->isEnabled());
        $this->assertSame(filesize($path), LogFile::query()->firstOrFail()->last_offset);
    }

    public function test_reactivating_with_catch_up_preserves_the_existing_cursor(): void
    {
        $path = $this->logDirectory.'/laravel.log';
        File::put($path, str_repeat('x', 100));
        LogFile::query()->create([
            'path' => $path,
            'relative_path' => 'laravel.log',
            'filename' => 'laravel.log',
            'size' => 100,
            'last_offset' => 25,
        ]);
        app(ChangeMonitoringState::class)->handle(false, null, null);

        app(ChangeMonitoringState::class)->handle(true, 'catch_up', null);

        $this->assertSame(25, LogFile::query()->firstOrFail()->last_offset);
    }

    public function test_hard_configuration_switch_prevents_reactivation(): void
    {
        config()->set('error-log-monitor.enabled', false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Monitoring is disabled by application configuration.');

        app(ChangeMonitoringState::class)->handle(true, 'catch_up', null);
    }
}
