<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Nextus\ErrorLogMonitor\Services\MigrationStatus;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class InstallationTest extends TestCase
{
    public function test_install_command_is_registered(): void
    {
        $this->assertArrayHasKey('error-log-monitor:install', Artisan::all());
    }

    public function test_migration_status_reports_a_fully_migrated_database(): void
    {
        $status = app(MigrationStatus::class);

        $this->assertTrue($status->isCurrent());
        $this->assertSame([], $status->missingRequirements());
    }

    public function test_dashboard_shows_upgrade_instructions_when_a_package_migration_is_missing(): void
    {
        Schema::drop('error_log_monitor_grouping_states');

        $this->get(route('error-log-monitor.dashboard'))
            ->assertServiceUnavailable()
            ->assertSee('php artisan migrate')
            ->assertSee('error_log_monitor_grouping_states');
    }
}
