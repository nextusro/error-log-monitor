<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Str;
use Nextus\ErrorLogMonitor\Data\ParsedLogEntry;
use Nextus\ErrorLogMonitor\Models\LogFile;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\LogOccurrence;

class LogIssueIndexer
{
    public function __construct(
        private readonly LogEntryFingerprinter $fingerprinter,
        private readonly NotificationManager $notifications,
    ) {}

    public function index(LogFile $file, ParsedLogEntry $entry): LogIssue
    {
        $fingerprint = $this->fingerprinter->fingerprint($entry);
        $normalizedMessage = $this->fingerprinter->normalizeMessage($entry->message);
        $occurredAt = $entry->occurredAt ?? now();

        /** @var LogIssue $issue */
        $issue = LogIssue::query()->firstOrNew(['fingerprint' => $fingerprint]);
        $isNew = ! $issue->exists;
        $isRegression = $issue->exists
            && $issue->resolved_at !== null
            && ($issue->last_seen_at === null || $issue->last_seen_at->lte($issue->resolved_at))
            && $occurredAt->greaterThan($issue->resolved_at);

        if (! $issue->exists) {
            $issue->fill([
                'level' => $entry->level,
                'exception_class' => $entry->exceptionClass,
                'normalized_message' => $this->limit($normalizedMessage, 65000),
                'first_seen_at' => $occurredAt,
                'occurrences_count' => 0,
            ]);
        }

        $issue->fill([
            'level' => $entry->level,
            'exception_class' => $entry->exceptionClass,
            'normalized_message' => $this->limit($normalizedMessage, 65000),
            'last_seen_at' => $occurredAt,
            'occurrences_count' => ((int) $issue->occurrences_count) + 1,
            'last_file_path' => $file->relative_path,
            'last_message' => $this->limit($entry->message, (int) config('error-log-monitor.indexing.max_message_length', 65535)),
            'last_context' => $this->limit($entry->context, (int) config('error-log-monitor.indexing.max_context_length', 65535)),
            'last_stack_trace' => $this->limit($entry->stackTrace, (int) config('error-log-monitor.indexing.max_stack_trace_length', 262144)),
        ]);

        if ($issue->first_seen_at === null) {
            $issue->first_seen_at = $occurredAt;
        }

        $issue->save();

        if ((bool) config('error-log-monitor.indexing.store_occurrences', true)) {
            LogOccurrence::query()->create([
                'issue_id' => $issue->id,
                'file_id' => $file->id,
                'level' => $entry->level,
                'file_path_snapshot' => $file->relative_path,
                'message' => $this->limit($entry->message, (int) config('error-log-monitor.indexing.max_message_length', 65535)),
                'context' => $this->limit($entry->context, (int) config('error-log-monitor.indexing.max_context_length', 65535)),
                'stack_trace' => $this->limit($entry->stackTrace, (int) config('error-log-monitor.indexing.max_stack_trace_length', 262144)),
                'occurred_at' => $occurredAt,
            ]);
        }

        if ($isNew) {
            $this->notifications->issueCreated($issue);
        } elseif ($isRegression) {
            $this->notifications->issueRegressed($issue);
        }

        return $issue;
    }

    private function limit(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::limit($value, $limit, '');
    }
}
