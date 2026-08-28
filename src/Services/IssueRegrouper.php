<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Nextus\ErrorLogMonitor\Data\ParsedLogEntry;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\LogOccurrence;

class IssueRegrouper
{
    private const ISSUE_CHUNK_SIZE = 50;

    private const OCCURRENCE_CHUNK_SIZE = 500;

    public function __construct(private readonly LogEntryFingerprinter $fingerprinter) {}

    /** @return array{before: int, after: int, occurrences: int} */
    public function regroup(): array
    {
        return DB::transaction(function (): array {
            $regroupingToken = (string) Str::uuid();
            $issuesBefore = LogIssue::query()->whereNull('regrouping_token')->count();
            $processedOccurrences = 0;

            LogIssue::query()
                ->whereNull('regrouping_token')
                ->orderBy('id')
                ->chunkById(self::ISSUE_CHUNK_SIZE, function ($issues) use ($regroupingToken, &$processedOccurrences): void {
                    foreach ($issues as $sourceIssue) {
                        $storedOccurrences = 0;

                        LogOccurrence::query()
                            ->where('issue_id', $sourceIssue->id)
                            ->orderBy('id')
                            ->chunkById(self::OCCURRENCE_CHUNK_SIZE, function ($occurrences) use (
                                $sourceIssue,
                                $regroupingToken,
                                &$storedOccurrences,
                                &$processedOccurrences,
                            ): void {
                                $groups = [];

                                foreach ($occurrences as $occurrence) {
                                    $entry = $this->entryForOccurrence($sourceIssue, $occurrence);
                                    $fingerprint = $this->fingerprinter->fingerprint($entry);
                                    $occurredAt = $entry->occurredAt ?? $sourceIssue->last_seen_at ?? now();
                                    $groups[$fingerprint] ??= $this->emptyChunkGroup($entry, $occurredAt, $occurrence->file_path_snapshot);
                                    $groups[$fingerprint]['count']++;
                                    $groups[$fingerprint]['occurrence_ids'][] = $occurrence->id;
                                    $groups[$fingerprint]['first_seen_at'] = $this->earlier(
                                        $groups[$fingerprint]['first_seen_at'],
                                        $sourceIssue->first_seen_at ?? $occurredAt,
                                    );

                                    if ($occurredAt->gte($groups[$fingerprint]['last_seen_at'])) {
                                        $groups[$fingerprint]['last_seen_at'] = $occurredAt;
                                        $groups[$fingerprint]['last_entry'] = $entry;
                                        $groups[$fingerprint]['last_file_path'] = $occurrence->file_path_snapshot;
                                    }

                                    $storedOccurrences++;
                                    $processedOccurrences++;
                                }

                                foreach ($groups as $fingerprint => $group) {
                                    $this->mergeChunkGroup($regroupingToken, $fingerprint, $sourceIssue, $group);
                                }
                            });

                        $missingOccurrences = max(0, (int) $sourceIssue->occurrences_count - $storedOccurrences);

                        if ($missingOccurrences > 0 || $storedOccurrences === 0) {
                            $entry = $this->entryForIssue($sourceIssue);
                            $occurredAt = $entry->occurredAt ?? $sourceIssue->last_seen_at ?? now();
                            $group = $this->emptyChunkGroup($entry, $occurredAt, $sourceIssue->last_file_path);
                            $group['count'] = max(1, $missingOccurrences);
                            $group['first_seen_at'] = $sourceIssue->first_seen_at ?? $occurredAt;
                            $this->mergeChunkGroup(
                                $regroupingToken,
                                $this->fingerprinter->fingerprint($entry),
                                $sourceIssue,
                                $group,
                            );
                        }
                    }
                });

            LogIssue::query()->whereNull('regrouping_token')->delete();

            LogIssue::query()
                ->where('regrouping_token', $regroupingToken)
                ->orderBy('id')
                ->chunkById(self::ISSUE_CHUNK_SIZE, function ($issues): void {
                    foreach ($issues as $issue) {
                        $issue->update([
                            'fingerprint' => $issue->pending_fingerprint,
                            'pending_fingerprint' => null,
                            'regrouping_token' => null,
                        ]);
                    }
                });

            return [
                'before' => $issuesBefore,
                'after' => LogIssue::query()->count(),
                'occurrences' => $processedOccurrences,
            ];
        }, 3);
    }

    private function entryForOccurrence(LogIssue $issue, LogOccurrence $occurrence): ParsedLogEntry
    {
        return new ParsedLogEntry(
            level: $occurrence->level,
            message: $occurrence->message,
            context: $occurrence->context,
            stackTrace: $occurrence->stack_trace,
            occurredAt: $occurrence->occurred_at,
            exceptionClass: $issue->exception_class,
        );
    }

    private function entryForIssue(LogIssue $issue): ParsedLogEntry
    {
        return new ParsedLogEntry(
            level: $issue->level,
            message: (string) $issue->last_message,
            context: $issue->last_context,
            stackTrace: $issue->last_stack_trace,
            occurredAt: $issue->last_seen_at,
            exceptionClass: $issue->exception_class,
        );
    }

    /**
     * @return array{count: int, occurrence_ids: list<int>, first_seen_at: Carbon, last_seen_at: Carbon, last_entry: ParsedLogEntry, last_file_path: ?string}
     */
    private function emptyChunkGroup(ParsedLogEntry $entry, Carbon $occurredAt, ?string $filePath): array
    {
        return [
            'count' => 0,
            'occurrence_ids' => [],
            'first_seen_at' => $occurredAt,
            'last_seen_at' => $occurredAt,
            'last_entry' => $entry,
            'last_file_path' => $filePath,
        ];
    }

    /** @param array<string, mixed> $group */
    private function mergeChunkGroup(
        string $regroupingToken,
        string $fingerprint,
        LogIssue $sourceIssue,
        array $group,
    ): void {
        $targetIssue = LogIssue::query()->firstOrCreate(
            ['pending_fingerprint' => $fingerprint],
            $this->newTargetAttributes($regroupingToken, $fingerprint, $sourceIssue, $group),
        );

        if (! $targetIssue->wasRecentlyCreated) {
            $this->mergeTargetAttributes($targetIssue, $sourceIssue, $group);
        }

        if ($group['occurrence_ids'] !== []) {
            LogOccurrence::query()
                ->whereIn('id', $group['occurrence_ids'])
                ->update(['issue_id' => $targetIssue->id]);
        }
    }

    /** @param array<string, mixed> $group */
    private function newTargetAttributes(
        string $regroupingToken,
        string $fingerprint,
        LogIssue $sourceIssue,
        array $group,
    ): array {
        $entry = $group['last_entry'];

        return [
            'fingerprint' => hash('sha256', $regroupingToken.'|'.$fingerprint),
            'regrouping_token' => $regroupingToken,
            'level' => $entry->level,
            'exception_class' => $entry->exceptionClass,
            'normalized_message' => $this->fingerprinter->normalizeMessage($entry->message),
            'first_seen_at' => $group['first_seen_at'],
            'last_seen_at' => $group['last_seen_at'],
            'occurrences_count' => $group['count'],
            'last_file_path' => $group['last_file_path'],
            'last_message' => $entry->message,
            'last_context' => $entry->context,
            'last_stack_trace' => $entry->stackTrace,
            'resolved_at' => $sourceIssue->status === 'resolved' ? $sourceIssue->resolved_at : null,
            'ignored_at' => $sourceIssue->status === 'ignored' ? $sourceIssue->ignored_at : null,
            'last_notified_at' => null,
            'notification_count' => 0,
        ];
    }

    /** @param array<string, mixed> $group */
    private function mergeTargetAttributes(LogIssue $targetIssue, LogIssue $sourceIssue, array $group): void
    {
        $attributes = [
            'occurrences_count' => (int) $targetIssue->occurrences_count + $group['count'],
            'first_seen_at' => $this->earlier($targetIssue->first_seen_at, $group['first_seen_at']),
        ];

        if ($group['last_seen_at']->gte($targetIssue->last_seen_at)) {
            $entry = $group['last_entry'];
            $attributes = array_merge($attributes, [
                'last_seen_at' => $group['last_seen_at'],
                'last_file_path' => $group['last_file_path'],
                'last_message' => $entry->message,
                'last_context' => $entry->context,
                'last_stack_trace' => $entry->stackTrace,
                'level' => $entry->level,
                'exception_class' => $entry->exceptionClass,
                'normalized_message' => $this->fingerprinter->normalizeMessage($entry->message),
            ]);
        }

        if ($targetIssue->status !== $sourceIssue->status) {
            $attributes['resolved_at'] = null;
            $attributes['ignored_at'] = null;
        } elseif ($targetIssue->status === 'resolved' && $sourceIssue->resolved_at?->gt($targetIssue->resolved_at)) {
            $attributes['resolved_at'] = $sourceIssue->resolved_at;
        } elseif ($targetIssue->status === 'ignored' && $sourceIssue->ignored_at?->gt($targetIssue->ignored_at)) {
            $attributes['ignored_at'] = $sourceIssue->ignored_at;
        }

        $targetIssue->update($attributes);
    }

    private function earlier(Carbon $first, Carbon $second): Carbon
    {
        return $first->lte($second) ? $first : $second;
    }
}
