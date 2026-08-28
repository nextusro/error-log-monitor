<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Nextus\ErrorLogMonitor\Models\Setting;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class IndexingSettingsControllerTest extends TestCase
{
    public function test_indexing_settings_can_be_overridden_and_reset_from_dashboard(): void
    {
        $response = $this->put(route('error-log-monitor.settings.indexing.update'), [
            'max_runtime_seconds' => 120,
            'max_files_per_run' => 75,
            'max_lines_per_file' => 10000,
            'incomplete_notification_enabled' => true,
            'incomplete_notification_mode' => 'immediate',
            'stale_after_minutes' => 20,
            'notification_cooldown_minutes' => 90,
            'recovery_notification_enabled' => true,
            'run_history_days' => 45,
        ]);

        $response->assertRedirect();
        $this->assertSame(120, app(SettingStore::class)->get('indexing', 'max_runtime_seconds'));
        $this->assertSame(9, Setting::query()->where('group', 'indexing')->count());

        $this->delete(route('error-log-monitor.settings.override.destroy'), [
            'group' => 'indexing',
            'key' => '*',
        ])->assertRedirect();

        $this->assertSame(0, Setting::query()->where('group', 'indexing')->count());
        $this->assertSame(30, app(SettingStore::class)->get('indexing', 'max_runtime_seconds'));
    }

    public function test_indexing_settings_are_validated(): void
    {
        $this->from(route('error-log-monitor.dashboard'))->put(route('error-log-monitor.settings.indexing.update'), [
            'max_runtime_seconds' => 0,
        ])->assertRedirect(route('error-log-monitor.dashboard'))->assertSessionHasErrors([
            'max_runtime_seconds', 'max_files_per_run', 'max_lines_per_file',
        ]);
    }

    public function test_retention_settings_can_be_overridden(): void
    {
        $this->put(route('error-log-monitor.settings.retention.update'), [
            'occurrences_days' => 14,
            'max_occurrences_per_issue' => 250,
            'optimize_tables_after_prune' => true,
            'resolved_issues_days' => 30,
            'ignored_issues_days' => 45,
            'open_issues_days' => 0,
        ])->assertRedirect();

        $this->assertSame(14, app(SettingStore::class)->get('retention', 'occurrences_days'));
        $this->assertSame(250, app(SettingStore::class)->get('retention', 'max_occurrences_per_issue'));
        $this->assertTrue(app(SettingStore::class)->get('retention', 'optimize_tables_after_prune'));
        $this->assertSame(6, Setting::query()->where('group', 'retention')->count());
    }

    public function test_dashboard_preferences_can_be_overridden_without_resetting_locale(): void
    {
        app(SettingStore::class)->put('dashboard', 'locale', 'ro');

        $this->put(route('error-log-monitor.settings.dashboard.update'), [
            'per_page' => 100,
            'default_interval' => '7d',
            'date_format' => 'Y-m-d H:i:s',
            'statistics_collapsed_by_default' => true,
            'default_theme' => 'dark',
        ])->assertRedirect();

        $this->assertSame(100, app(SettingStore::class)->get('dashboard', 'per_page'));

        $this->delete(route('error-log-monitor.settings.override.destroy'), [
            'group' => 'dashboard',
            'keys' => ['per_page', 'default_interval', 'date_format', 'statistics_collapsed_by_default', 'default_theme'],
        ])->assertRedirect();

        $this->assertSame('ro', app(SettingStore::class)->get('dashboard', 'locale'));
        $this->assertSame(50, app(SettingStore::class)->get('dashboard', 'per_page'));
    }
}
