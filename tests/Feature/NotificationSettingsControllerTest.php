<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Nextus\ErrorLogMonitor\Models\Setting;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class NotificationSettingsControllerTest extends TestCase
{
    public function test_notification_settings_fall_back_to_config(): void
    {
        config()->set('error-log-monitor.notifications.recipients', ['config@example.com']);

        $this->assertSame(
            ['config@example.com'],
            app(SettingStore::class)->get('notifications', 'recipients'),
        );
    }

    public function test_notification_settings_can_be_overridden_from_the_dashboard(): void
    {
        $response = $this->put(route('error-log-monitor.settings.notifications.update'), [
            'enabled' => true,
            'recipients' => "One@example.com\ntwo@example.com, one@example.com",
            'regressions_enabled' => true,
            'database_size_enabled' => true,
            'database_size_threshold_mb' => 250,
            'levels' => ['critical', 'emergency'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error-log-monitor.success');

        $settings = app(SettingStore::class);
        $this->assertTrue($settings->get('notifications', 'enabled'));
        $this->assertSame(['one@example.com', 'two@example.com'], $settings->get('notifications', 'recipients'));
        $this->assertSame(250, $settings->get('notifications', 'database_size_threshold_mb'));
        $this->assertSame(['critical', 'emergency'], $settings->get('notifications', 'levels'));
        $this->assertSame(6, Setting::query()->where('group', 'notifications')->count());
    }

    public function test_notifications_cannot_be_enabled_without_a_valid_recipient(): void
    {
        $response = $this->from(route('error-log-monitor.dashboard'))->put(
            route('error-log-monitor.settings.notifications.update'),
            [
                'enabled' => true,
                'recipients' => 'invalid-address',
                'regressions_enabled' => true,
                'database_size_enabled' => true,
                'database_size_threshold_mb' => 100,
                'levels' => [],
            ],
        );

        $response->assertRedirect(route('error-log-monitor.dashboard'));
        $response->assertSessionHasErrors('recipients');
        $this->assertSame(0, Setting::query()->where('group', 'notifications')->count());
    }

    public function test_dashboard_renders_notification_controls(): void
    {
        $this->get(route('error-log-monitor.dashboard'))
            ->assertOk()
            ->assertSee('data-settings-tab="notifications"', false)
            ->assertSee(route('error-log-monitor.settings.notifications.update'), false)
            ->assertSee('Database threshold (MB)');
    }
}
