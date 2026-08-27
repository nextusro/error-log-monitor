<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Nextus\ErrorLogMonitor\Models\IndexRun;
use Nextus\ErrorLogMonitor\Notifications\IndexingHealthNotification;
use Nextus\ErrorLogMonitor\Services\NotificationManager;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class IndexingHealthNotificationTest extends TestCase
{
    public function test_incomplete_and_recovery_notifications_are_sent(): void
    {
        Notification::fake();
        $settings = app(SettingStore::class);
        $settings->put('notifications', 'enabled', true);
        $settings->put('notifications', 'recipients', ['alerts@example.com']);
        $settings->put('indexing', 'incomplete_notification_enabled', true);
        $settings->put('indexing', 'incomplete_notification_mode', 'immediate');
        $settings->put('indexing', 'recovery_notification_enabled', true);

        app(NotificationManager::class)->checkIndexingHealth($this->createIndexRun('partial', 'file_limit', 2));

        Notification::assertSentOnDemand(
            IndexingHealthNotification::class,
            static fn (IndexingHealthNotification $notification): bool => ! $notification->recovered,
        );

        app(NotificationManager::class)->checkIndexingHealth($this->createIndexRun('completed', null, 0));

        Notification::assertSentOnDemand(
            IndexingHealthNotification::class,
            static fn (IndexingHealthNotification $notification): bool => $notification->recovered,
        );
    }

    private function createIndexRun(string $status, ?string $reason, int $pending): IndexRun
    {
        return IndexRun::query()->create([
            'started_at' => now(),
            'finished_at' => now(),
            'status' => $status,
            'stop_reason' => $reason,
            'discovered_files' => 3,
            'processed_files' => 3 - $pending,
            'pending_files' => $pending,
        ]);
    }
}
