<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Nextus\ErrorLogMonitor\Notifications\DatabaseSizeExceededNotification;
use Nextus\ErrorLogMonitor\Services\DatabaseSize;
use Nextus\ErrorLogMonitor\Services\NotificationManager;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class DatabaseSizeNotificationTest extends TestCase
{
    public function test_database_alert_is_deduplicated_and_rearmed_after_size_drops(): void
    {
        Notification::fake();
        config()->set('error-log-monitor.notifications.cooldown_minutes', 60);

        $settings = app(SettingStore::class);
        $settings->put('notifications', 'enabled', true);
        $settings->put('notifications', 'recipients', ['alerts@example.com']);
        $settings->put('notifications', 'database_size_enabled', true);
        $settings->put('notifications', 'database_size_threshold_mb', 1);

        $this->mock(DatabaseSize::class, function (MockInterface $mock): void {
            $mock->shouldReceive('bytes')->times(4)->andReturn(2_097_152, 2_097_152, 524_288, 2_097_152);
            $mock->shouldReceive('format')->twice()->with(2_097_152)->andReturn('2.00 MB');
        });

        $manager = app(NotificationManager::class);
        $manager->checkDatabaseSize();
        $manager->checkDatabaseSize();
        $manager->checkDatabaseSize();
        $manager->checkDatabaseSize();

        Notification::assertCount(2);
        Notification::assertSentOnDemand(DatabaseSizeExceededNotification::class, 2);
    }

    public function test_database_alert_is_disabled_independently(): void
    {
        Notification::fake();
        $settings = app(SettingStore::class);
        $settings->put('notifications', 'enabled', true);
        $settings->put('notifications', 'recipients', ['alerts@example.com']);
        $settings->put('notifications', 'database_size_enabled', false);

        $this->mock(DatabaseSize::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('bytes');
        });

        app(NotificationManager::class)->checkDatabaseSize();

        Notification::assertNothingSent();
    }
}
