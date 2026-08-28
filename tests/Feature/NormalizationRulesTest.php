<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Nextus\ErrorLogMonitor\Models\LogFile;
use Nextus\ErrorLogMonitor\Models\GroupingState;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\LogOccurrence;
use Nextus\ErrorLogMonitor\Models\NormalizationRule;
use Nextus\ErrorLogMonitor\Services\IssueRegrouper;
use Nextus\ErrorLogMonitor\Services\LogEntryFingerprinter;
use Nextus\ErrorLogMonitor\Services\LogIndexer;
use Nextus\ErrorLogMonitor\Services\NormalizationRuleSuggester;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class NormalizationRulesTest extends TestCase
{
    public function test_it_suggests_a_rule_for_a_varying_bracket_identifier(): void
    {
        $suggestion = app(NormalizationRuleSuggester::class)->suggest([
            '[code:A532555GO][rowNb:12] missing stock data',
            '[code:A2405793KU][rowNb:19] missing stock data',
        ]);

        $this->assertSame('/\\[code:[A-Z0-9]+\\]/i', $suggestion['pattern']);
        $this->assertSame('[code:{code}]', $suggestion['replacement']);
    }

    public function test_active_rules_are_applied_before_builtin_normalization(): void
    {
        NormalizationRule::query()->create([
            'name' => 'Stock code',
            'pattern' => '/\\[code:[A-Z0-9]+\\]/i',
            'replacement' => '[code:{code}]',
            'priority' => 10,
            'enabled' => true,
        ]);

        $normalized = app(LogEntryFingerprinter::class)->normalizeMessage(
            '[code:A532555GO][rowNb:193] missing stock data'
        );

        $this->assertSame('[code:{code}][rowNb:{number}] missing stock data', $normalized);
    }

    public function test_regrouping_merges_issues_and_preserves_occurrences(): void
    {
        $file = LogFile::query()->create([
            'path' => '/tmp/laravel.log',
            'relative_path' => 'laravel.log',
            'filename' => 'laravel.log',
        ]);
        $first = $this->createIssue('first', '[code:A532555GO][rowNb:12] missing stock data');
        $second = $this->createIssue('second', '[code:A2405793KU][rowNb:19] missing stock data');

        foreach ([$first, $second] as $index => $issue) {
            LogOccurrence::query()->create([
                'issue_id' => $issue->id,
                'file_id' => $file->id,
                'level' => 'error',
                'file_path_snapshot' => 'laravel.log',
                'message' => $issue->last_message,
                'occurred_at' => now()->addSecond($index),
            ]);
        }

        NormalizationRule::query()->create([
            'name' => 'Stock code',
            'pattern' => '/\\[code:[A-Z0-9]+\\]/i',
            'replacement' => '[code:{code}]',
            'priority' => 10,
            'enabled' => true,
        ]);

        $result = app(IssueRegrouper::class)->regroup();

        $this->assertSame(['before' => 2, 'after' => 1, 'occurrences' => 2], $result);
        $this->assertSame(1, LogIssue::query()->count());
        $this->assertSame(2, LogOccurrence::query()->count());
        $this->assertSame(2, LogIssue::query()->firstOrFail()->occurrences_count);
        $this->assertSame(1, LogOccurrence::query()->distinct()->count('issue_id'));
    }

    public function test_rule_store_validates_the_regular_expression(): void
    {
        $this->post(route('error-log-monitor.normalization-rules.store'), [
            'name' => 'Invalid',
            'type' => 'regex',
            'pattern' => '/[invalid/',
            'replacement' => '{value}',
            'priority' => 100,
            'enabled' => 1,
        ])->assertSessionHasErrors('pattern');

        $this->assertSame(0, NormalizationRule::query()->count());
    }

    public function test_selected_issues_open_an_editable_rule_proposal(): void
    {
        $first = $this->createIssue('proposal-first', '[code:A532555GO][rowNb:12] missing stock data');
        $second = $this->createIssue('proposal-second', '[code:A2405793KU][rowNb:19] missing stock data');

        $this->post(route('error-log-monitor.normalization-rules.suggest'), [
            'issue_ids' => [$first->id, $second->id],
        ])->assertOk()
            ->assertSee('Code identifier')
            ->assertSee('/\\[code:[A-Z0-9]+\\]/i', false)
            ->assertSee('[code:{code}][rowNb:{number}] missing stock data');
    }

    public function test_dashboard_allows_multiple_rules_to_be_added_manually(): void
    {
        NormalizationRule::query()->create([
            'name' => 'Account identifier',
            'pattern' => '/\\[accountId:[^\\]]+\\]/i',
            'replacement' => '[accountId:{accountId}]',
            'priority' => 100,
            'enabled' => true,
        ]);

        $this->get(route('error-log-monitor.dashboard'))
            ->assertOk()
            ->assertSee('Add rule')
            ->assertSee(route('error-log-monitor.normalization-rules.store'), false)
            ->assertSee('Account identifier');

        $this->post(route('error-log-monitor.normalization-rules.store'), [
            'name' => 'Stock code',
            'type' => 'regex',
            'pattern' => '/\\[code:[A-Z0-9]+\\]/i',
            'replacement' => '[code:{code}]',
            'priority' => 200,
            'enabled' => 1,
        ])->assertRedirect(route('error-log-monitor.dashboard'));

        $this->assertSame(2, NormalizationRule::query()->count());
        $this->assertDatabaseHas('error_log_monitor_normalization_rules', ['name' => 'Account identifier']);
        $this->assertDatabaseHas('error_log_monitor_normalization_rules', ['name' => 'Stock code']);
    }

    public function test_message_template_groups_numbers_after_word_characters_without_regex(): void
    {
        NormalizationRule::query()->create([
            'name' => 'Inactive action token',
            'type' => 'template',
            'pattern' => '[id:{number}][act_{number}] no active token found',
            'replacement' => '',
            'priority' => 100,
            'enabled' => true,
        ]);

        $fingerprinter = app(LogEntryFingerprinter::class);
        $first = '[id:142][act_991] no active token found';
        $second = '[id:875][act_12004] no active token found';

        $this->assertSame(
            '[id:{number}][act_{number}] no active token found',
            $fingerprinter->normalizeMessage($first),
        );
        $this->assertSame(
            $fingerprinter->normalizeMessage($first),
            $fingerprinter->normalizeMessage($second),
        );
    }

    public function test_rule_changes_are_regrouped_at_the_start_of_the_next_indexing_run(): void
    {
        $file = LogFile::query()->create([
            'path' => '/tmp/deferred.log',
            'relative_path' => 'deferred.log',
            'filename' => 'deferred.log',
        ]);
        $first = $this->createIssue('deferred-first', '[code:A532555GO] missing stock data');
        $second = $this->createIssue('deferred-second', '[code:A2405793KU] missing stock data');

        foreach ([$first, $second] as $issue) {
            LogOccurrence::query()->create([
                'issue_id' => $issue->id,
                'file_id' => $file->id,
                'level' => 'error',
                'file_path_snapshot' => 'deferred.log',
                'message' => $issue->last_message,
                'occurred_at' => now(),
            ]);
        }

        $this->post(route('error-log-monitor.normalization-rules.store'), [
            'name' => 'Stock code',
            'type' => 'regex',
            'pattern' => '/\\[code:[A-Z0-9]+\\]/i',
            'replacement' => '[code:{code}]',
            'priority' => 100,
            'enabled' => 1,
        ])->assertRedirect(route('error-log-monitor.dashboard'));

        $this->assertSame(2, LogIssue::query()->count());
        $state = GroupingState::query()->findOrFail(1);
        $this->assertNotSame($state->rules_version, $state->grouped_rules_version);

        $stats = app(LogIndexer::class)->run();

        $this->assertTrue($stats['regrouped']);
        $this->assertSame(1, LogIssue::query()->count());
        $state->refresh();
        $this->assertSame($state->rules_version, $state->grouped_rules_version);
    }

    public function test_indexing_skips_regrouping_when_rule_versions_match(): void
    {
        $stats = app(LogIndexer::class)->run();

        $this->assertFalse($stats['regrouped']);
        $this->assertNull($stats['regrouping']);
        $this->assertSame(0, GroupingState::query()->count());
    }

    public function test_regrouping_processes_occurrences_across_multiple_chunks(): void
    {
        $file = LogFile::query()->create([
            'path' => '/tmp/chunked.log',
            'relative_path' => 'chunked.log',
            'filename' => 'chunked.log',
        ]);
        $issue = $this->createIssue('chunked', '[id:1][act_1] no active token found');
        $issue->update(['occurrences_count' => 1201]);
        $timestamp = now();

        foreach (array_chunk(range(1, 1201), 200) as $numbers) {
            LogOccurrence::query()->insert(array_map(static fn (int $number): array => [
                'issue_id' => $issue->id,
                'file_id' => $file->id,
                'level' => 'error',
                'file_path_snapshot' => 'chunked.log',
                'message' => "[id:{$number}][act_{$number}] no active token found",
                'occurred_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $numbers));
        }

        NormalizationRule::query()->create([
            'name' => 'Inactive action token',
            'type' => 'template',
            'pattern' => '[id:{number}][act_{number}] no active token found',
            'replacement' => '',
            'priority' => 100,
            'enabled' => true,
        ]);

        $result = app(IssueRegrouper::class)->regroup();

        $this->assertSame(1201, $result['occurrences']);
        $this->assertSame(1, $result['after']);
        $this->assertSame(1201, LogIssue::query()->firstOrFail()->occurrences_count);
        $this->assertSame(1201, LogOccurrence::query()->count());
    }

    public function test_suggestion_ignores_differences_already_covered_by_active_rules(): void
    {
        NormalizationRule::query()->create([
            'name' => 'Account identifier',
            'pattern' => '/\\[accountId:[^\\]]+\\]/i',
            'replacement' => '[accountId:{accountId}]',
            'priority' => 100,
            'enabled' => true,
        ]);
        $first = $this->createIssue(
            'existing-rule-first',
            '[accountId:1001][code:A532555GO][rowNb:12] missing stock data',
        );
        $second = $this->createIssue(
            'existing-rule-second',
            '[accountId:2002][code:A2405793KU][rowNb:19] missing stock data',
        );

        $this->post(route('error-log-monitor.normalization-rules.suggest'), [
            'issue_ids' => [$first->id, $second->id],
        ])->assertOk()
            ->assertSee('Code identifier')
            ->assertSee('/\\[code:[A-Z0-9]+\\]/i', false)
            ->assertDontSee('AccountId identifier');
    }

    public function test_failed_suggestion_is_visible_on_the_dashboard(): void
    {
        $first = $this->createIssue('unsafe-first', 'First unrelated message');
        $second = $this->createIssue('unsafe-second', 'Second unrelated message');

        $this->from(route('error-log-monitor.dashboard'))
            ->post(route('error-log-monitor.normalization-rules.suggest'), [
                'issue_ids' => [$first->id, $second->id],
            ])->assertRedirect(route('error-log-monitor.dashboard'))
            ->assertSessionHasErrors('issue_ids');

        $this->get(route('error-log-monitor.dashboard'))
            ->assertSee('A safe grouping rule could not be inferred from the selected issues.');
    }

    private function createIssue(string $key, string $message): LogIssue
    {
        return LogIssue::query()->create([
            'fingerprint' => hash('sha256', $key),
            'level' => 'error',
            'normalized_message' => $message,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'occurrences_count' => 1,
            'last_file_path' => 'laravel.log',
            'last_message' => $message,
        ]);
    }
}
