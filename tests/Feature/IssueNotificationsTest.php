<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Nextus\ErrorLogMonitor\Data\ParsedLogEntry;
use Nextus\ErrorLogMonitor\Models\LogFile;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\NotificationState;
use Nextus\ErrorLogMonitor\Notifications\IssueNotification;
use Nextus\ErrorLogMonitor\Services\LogIssueIndexer;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class IssueNotificationsTest extends TestCase
{
    public function test_selected_level_sends_once_only_when_issue_is_created(): void
    {
        Notification::fake();
        $this->enableNotifications(['emergency']);
        $file = $this->createFile();
        $entry = $this->entry('emergency', 'Database is unavailable', now());

        app(LogIssueIndexer::class)->index($file, $entry);
        app(LogIssueIndexer::class)->index($file, $entry);

        Notification::assertCount(1);
        Notification::assertSentOnDemand(IssueNotification::class, function (IssueNotification $notification): bool {
            return $notification->event === 'new_issue' && $notification->level === 'emergency';
        });
    }

    public function test_unselected_level_does_not_send_a_notification(): void
    {
        Notification::fake();
        $this->enableNotifications(['emergency']);

        app(LogIssueIndexer::class)->index($this->createFile(), $this->entry('error', 'Regular error', now()));

        Notification::assertNothingSent();
    }

    public function test_regression_sends_once_per_resolution_cycle(): void
    {
        Notification::fake();
        $this->enableNotifications([]);
        $file = $this->createFile();
        $indexer = app(LogIssueIndexer::class);
        $indexer->index($file, $this->entry('error', 'Recurring failure', now()->subMinutes(10)));

        $issue = LogIssue::query()->firstOrFail();
        $issue->update(['resolved_at' => now()->subMinutes(5)]);

        $indexer->index($file, $this->entry('error', 'Recurring failure', now()));
        $indexer->index($file, $this->entry('error', 'Recurring failure', now()->addMinute()));

        Notification::assertCount(1);
        Notification::assertSentOnDemand(IssueNotification::class, fn (IssueNotification $notification): bool => $notification->event === 'regression');
        $this->assertSame(1, NotificationState::query()->where('type', 'regression')->count());
    }

    /**
     * @param  list<string>  $levels
     */
    private function enableNotifications(array $levels): void
    {
        $settings = app(SettingStore::class);
        $settings->put('notifications', 'enabled', true);
        $settings->put('notifications', 'recipients', ['alerts@example.com']);
        $settings->put('notifications', 'regressions_enabled', true);
        $settings->put('notifications', 'levels', $levels);
    }

    private function createFile(): LogFile
    {
        return LogFile::query()->create([
            'path' => $this->logDirectory.'/laravel.log',
            'relative_path' => 'laravel.log',
            'filename' => 'laravel.log',
            'size' => 0,
            'last_offset' => 0,
            'is_missing' => false,
        ]);
    }

    private function entry(string $level, string $message, Carbon $occurredAt): ParsedLogEntry
    {
        return new ParsedLogEntry(
            level: $level,
            message: $message,
            context: null,
            stackTrace: null,
            occurredAt: $occurredAt,
            exceptionClass: null,
        );
    }
}
