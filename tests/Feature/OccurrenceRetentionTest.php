<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Nextus\ErrorLogMonitor\Data\ParsedLogEntry;
use Nextus\ErrorLogMonitor\Models\LogFile;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\LogOccurrence;
use Nextus\ErrorLogMonitor\Services\LogIssueIndexer;
use Nextus\ErrorLogMonitor\Services\PrunedTableOptimizer;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class OccurrenceRetentionTest extends TestCase
{
    public function test_total_is_counted_while_only_the_first_and_latest_occurrences_are_stored(): void
    {
        app(SettingStore::class)->put('retention', 'max_occurrences_per_issue', 3);
        app(SettingStore::class)->put('retention', 'optimize_tables_after_prune', false);
        $file = LogFile::query()->create([
            'path' => $this->logDirectory.'/laravel.log',
            'relative_path' => 'laravel.log',
            'filename' => 'laravel.log',
        ]);

        foreach (range(1, 5) as $number) {
            app(LogIssueIndexer::class)->index($file, new ParsedLogEntry(
                level: 'error',
                message: "Repeated failure {$number}",
                context: null,
                stackTrace: 'same trace',
                occurredAt: now()->addSeconds($number),
                exceptionClass: null,
            ));
        }

        $issue = LogIssue::query()->firstOrFail();

        $this->assertSame(5, $issue->occurrences_count);
        $this->assertSame(5, $issue->occurrences()->count());

        $this->artisan('error-log-monitor:prune')->assertSuccessful();

        $this->assertSame(3, $issue->occurrences()->count());
        $this->assertSame(
            ['Repeated failure 1', 'Repeated failure 4', 'Repeated failure 5'],
            $issue->occurrences()->orderBy('id')->pluck('message')->all(),
        );
    }

    public function test_prune_applies_a_reduced_limit_to_existing_occurrences(): void
    {
        config()->set('error-log-monitor.retention.optimize_tables_after_prune', false);
        app(SettingStore::class)->put('retention', 'max_occurrences_per_issue', 2);
        $issue = LogIssue::query()->create([
            'fingerprint' => str_repeat('a', 64),
            'level' => 'error',
            'normalized_message' => 'Failure',
            'occurrences_count' => 4,
        ]);

        foreach (range(1, 4) as $number) {
            LogOccurrence::query()->create([
                'issue_id' => $issue->id,
                'level' => 'error',
                'message' => "Failure {$number}",
                'occurred_at' => now(),
            ]);
        }

        $this->artisan('error-log-monitor:prune')->assertSuccessful();

        $this->assertSame(4, $issue->fresh()->occurrences_count);
        $this->assertSame(
            ['Failure 1', 'Failure 4'],
            $issue->occurrences()->orderBy('id')->pluck('message')->all(),
        );
    }

    public function test_zero_keeps_all_occurrences(): void
    {
        app(SettingStore::class)->put('retention', 'max_occurrences_per_issue', 0);
        $issue = LogIssue::query()->create([
            'fingerprint' => str_repeat('b', 64),
            'level' => 'error',
            'normalized_message' => 'Failure',
            'occurrences_count' => 2,
        ]);

        LogOccurrence::query()->create(['issue_id' => $issue->id, 'level' => 'error', 'message' => 'One']);
        LogOccurrence::query()->create(['issue_id' => $issue->id, 'level' => 'error', 'message' => 'Two']);

        $this->artisan('error-log-monitor:prune')->assertSuccessful();

        $this->assertSame(2, $issue->occurrences()->count());
    }

    public function test_prune_optimizes_the_occurrence_table_after_deleting_samples(): void
    {
        app(SettingStore::class)->put('retention', 'max_occurrences_per_issue', 1);
        $issue = LogIssue::query()->create([
            'fingerprint' => str_repeat('c', 64),
            'level' => 'error',
            'normalized_message' => 'Failure',
            'occurrences_count' => 2,
        ]);
        LogOccurrence::query()->create(['issue_id' => $issue->id, 'level' => 'error', 'message' => 'One']);
        LogOccurrence::query()->create(['issue_id' => $issue->id, 'level' => 'error', 'message' => 'Two']);

        $optimizer = $this->mock(PrunedTableOptimizer::class);
        $optimizer->shouldReceive('optimize')
            ->once()
            ->with(['error_log_monitor_occurrences']);

        $this->artisan('error-log-monitor:prune')->assertSuccessful();
    }

    public function test_resolved_and_ignored_issues_are_retained_indefinitely_by_default(): void
    {
        LogIssue::query()->create([
            'fingerprint' => str_repeat('d', 64),
            'level' => 'error',
            'normalized_message' => 'Resolved failure',
            'resolved_at' => now()->subYear(),
        ]);
        LogIssue::query()->create([
            'fingerprint' => str_repeat('e', 64),
            'level' => 'error',
            'normalized_message' => 'Ignored failure',
            'ignored_at' => now()->subYear(),
        ]);

        $this->artisan('error-log-monitor:prune')->assertSuccessful();

        $this->assertDatabaseHas('error_log_monitor_issues', ['normalized_message' => 'Resolved failure']);
        $this->assertDatabaseHas('error_log_monitor_issues', ['normalized_message' => 'Ignored failure']);
        $this->assertSame(0, app(SettingStore::class)->get('retention', 'resolved_issues_days'));
        $this->assertSame(0, app(SettingStore::class)->get('retention', 'ignored_issues_days'));
    }
}
