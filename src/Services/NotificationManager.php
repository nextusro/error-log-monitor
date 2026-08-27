<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\IndexRun;
use Nextus\ErrorLogMonitor\Models\NotificationState;
use Nextus\ErrorLogMonitor\Notifications\DatabaseSizeExceededNotification;
use Nextus\ErrorLogMonitor\Notifications\IssueNotification;
use Nextus\ErrorLogMonitor\Notifications\IndexingHealthNotification;

class NotificationManager
{
    public function __construct(
        private readonly SettingStore $settings,
        private readonly DatabaseSize $databaseSize,
    ) {}

    public function issueCreated(LogIssue $issue): void
    {
        $levels = $this->settings->get('notifications', 'levels');

        if (! is_array($levels) || ! in_array($issue->level, $levels, true)) {
            return;
        }

        $this->sendIssue($issue, 'new_issue', 'issue-level:'.$issue->id);
    }

    public function issueRegressed(LogIssue $issue): void
    {
        if (! (bool) $this->settings->get('notifications', 'regressions_enabled')) {
            return;
        }

        $resolvedAt = $issue->resolved_at?->format('Y-m-d-H-i-s-u') ?? 'unknown';
        $this->sendIssue($issue, 'regression', 'issue-regression:'.$issue->id.':'.$resolvedAt);
    }

    public function checkDatabaseSize(): void
    {
        if (! $this->canNotify() || ! (bool) $this->settings->get('notifications', 'database_size_enabled')) {
            return;
        }

        $sizeBytes = $this->databaseSize->bytes();

        if ($sizeBytes === null) {
            return;
        }

        $thresholdMb = max(1, (int) $this->settings->get('notifications', 'database_size_threshold_mb'));
        $thresholdBytes = $thresholdMb * 1024 * 1024;
        $state = NotificationState::query()->firstOrCreate(
            ['state_key' => 'database-size'],
            ['type' => 'database_size'],
        );

        if ($sizeBytes <= $thresholdBytes) {
            if ($state->active) {
                $state->update(['active' => false, 'metadata' => ['size_bytes' => $sizeBytes, 'threshold_mb' => $thresholdMb]]);
            }

            return;
        }

        $cooldownMinutes = max(0, (int) $this->settings->get('notifications', 'cooldown_minutes'));
        $cooldownElapsed = $state->last_notified_at === null
            || $state->last_notified_at->lte(now()->subMinutes($cooldownMinutes));

        if ($state->active && ! $cooldownElapsed) {
            return;
        }

        Notification::route('mail', $this->recipients())->notify(new DatabaseSizeExceededNotification(
            sizeBytes: $sizeBytes,
            thresholdMb: $thresholdMb,
            sizeLabel: $this->databaseSize->format($sizeBytes),
        ));

        $state->update([
            'active' => true,
            'last_notified_at' => now(),
            'notification_count' => $state->notification_count + 1,
            'metadata' => ['size_bytes' => $sizeBytes, 'threshold_mb' => $thresholdMb],
        ]);
    }

    public function checkIndexingHealth(IndexRun $run): void
    {
        if (! (bool) $this->settings->get('indexing', 'incomplete_notification_enabled')) {
            return;
        }

        $state = NotificationState::query()->firstOrCreate(
            ['state_key' => 'indexing-health'],
            ['type' => 'indexing_health', 'metadata' => []],
        );

        if ($run->status === 'completed') {
            if ($state->active && $this->canNotify() && (bool) $this->settings->get('indexing', 'recovery_notification_enabled')) {
                $this->sendIndexingHealth($run, true);
            }

            $state->update(['active' => false, 'metadata' => ['recovered_at' => now()->toIso8601String()]]);

            return;
        }

        $metadata = is_array($state->metadata) ? $state->metadata : [];
        $firstDetectedAt = isset($metadata['first_detected_at'])
            ? Carbon::parse($metadata['first_detected_at'])
            : now();
        $mode = (string) $this->settings->get('indexing', 'incomplete_notification_mode');
        $staleMinutes = max(1, (int) $this->settings->get('indexing', 'stale_after_minutes'));
        $shouldNotify = $mode === 'immediate' || $firstDetectedAt->lte(now()->subMinutes($staleMinutes));
        $cooldownMinutes = max(0, (int) $this->settings->get('indexing', 'notification_cooldown_minutes'));
        $cooldownElapsed = $state->last_notified_at === null
            || $state->last_notified_at->lte(now()->subMinutes($cooldownMinutes));

        $state->update([
            'active' => $state->active || $shouldNotify,
            'metadata' => [
                'first_detected_at' => $firstDetectedAt->toIso8601String(),
                'run_id' => $run->id,
                'pending_files' => $run->pending_files,
                'partial_files' => $run->partially_processed_files,
                'failed_files' => $run->failed_files,
            ],
        ]);

        if ($shouldNotify && $cooldownElapsed && $this->canNotify()) {
            $this->sendIndexingHealth($run, false);
            $state->update([
                'active' => true,
                'last_notified_at' => now(),
                'notification_count' => $state->notification_count + 1,
            ]);
        }
    }

    private function sendIndexingHealth(IndexRun $run, bool $recovered): void
    {
        Notification::route('mail', $this->recipients())->notify(new IndexingHealthNotification(
            recovered: $recovered,
            processedFiles: (int) $run->processed_files,
            discoveredFiles: (int) $run->discovered_files,
            pendingFiles: (int) $run->pending_files,
            partialFiles: (int) $run->partially_processed_files,
            failedFiles: (int) $run->failed_files,
            reason: $run->stop_reason,
        ));
    }

    private function sendIssue(LogIssue $issue, string $event, string $stateKey): void
    {
        if (! $this->canNotify() || NotificationState::query()->where('state_key', $stateKey)->exists()) {
            return;
        }

        Notification::route('mail', $this->recipients())->notify(new IssueNotification(
            issueId: (int) $issue->id,
            event: $event,
            level: (string) $issue->level,
            message: (string) $issue->normalized_message,
            exceptionClass: $issue->exception_class,
            filePath: $issue->last_file_path,
            occurrencesCount: (int) $issue->occurrences_count,
        ));

        NotificationState::query()->create([
            'state_key' => $stateKey,
            'type' => $event,
            'issue_id' => $issue->id,
            'active' => true,
            'last_notified_at' => now(),
            'notification_count' => 1,
        ]);

        $issue->forceFill([
            'last_notified_at' => now(),
            'notification_count' => (int) $issue->notification_count + 1,
        ])->save();
    }

    private function canNotify(): bool
    {
        return (bool) $this->settings->get('notifications', 'enabled') && $this->recipients() !== [];
    }

    /**
     * @return list<string>
     */
    private function recipients(): array
    {
        $recipients = $this->settings->get('notifications', 'recipients');

        if (! is_array($recipients)) {
            return [];
        }

        return array_values(array_unique(array_filter($recipients, static fn (mixed $recipient): bool => is_string($recipient) && $recipient !== '')));
    }
}
