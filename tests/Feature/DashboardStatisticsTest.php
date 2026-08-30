<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Illuminate\Http\Request;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\LogOccurrence;
use Nextus\ErrorLogMonitor\Queries\DashboardStatsQuery;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class DashboardStatisticsTest extends TestCase
{
    public function test_rankings_include_only_active_issues_by_default(): void
    {
        $openIssue = $this->createIssue('open issue');
        $resolvedIssue = $this->createIssue('resolved issue', ['resolved_at' => now()]);
        $ignoredIssue = $this->createIssue('ignored issue', ['ignored_at' => now()]);

        $this->createOccurrence($openIssue, 'open.log');
        $this->createOccurrence($resolvedIssue, 'resolved.log');
        $this->createOccurrence($ignoredIssue, 'ignored.log');

        $statistics = app(DashboardStatsQuery::class)->build(Request::create('/', 'GET', ['interval' => '24h']));

        $this->assertSame('active', $statistics['scope']);
        $this->assertSame(['open issue'], collect($statistics['top_issues'])->pluck('issue.normalized_message')->all());
        $this->assertSame(['open.log'], collect($statistics['top_sources'])->pluck('source')->all());
    }

    public function test_all_scope_includes_resolved_and_ignored_issues_in_rankings(): void
    {
        $openIssue = $this->createIssue('open issue');
        $resolvedIssue = $this->createIssue('resolved issue', ['resolved_at' => now()]);
        $ignoredIssue = $this->createIssue('ignored issue', ['ignored_at' => now()]);

        $this->createOccurrence($openIssue, 'open.log');
        $this->createOccurrence($resolvedIssue, 'resolved.log');
        $this->createOccurrence($ignoredIssue, 'ignored.log');

        $statistics = app(DashboardStatsQuery::class)->build(Request::create('/', 'GET', [
            'interval' => '24h',
            'statistics_scope' => 'all',
        ]));

        $this->assertSame('all', $statistics['scope']);
        $this->assertEqualsCanonicalizing(
            ['open issue', 'resolved issue', 'ignored issue'],
            collect($statistics['top_issues'])->pluck('issue.normalized_message')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['open.log', 'resolved.log', 'ignored.log'],
            collect($statistics['top_sources'])->pluck('source')->all(),
        );
    }

    public function test_dashboard_displays_the_independent_ranking_scope_selector(): void
    {
        $response = $this->get(route('error-log-monitor.dashboard', [
            'interval' => '7d',
            'status' => 'ignored',
            'statistics_scope' => 'all',
        ]));

        $response->assertOk();
        $response->assertSee('Ranking scope');
        $response->assertSee('statistics-scope-link is-active', false);
        $response->assertSee('statistics_scope=active', false);
        $response->assertSee('status=ignored', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createIssue(string $message, array $attributes = []): LogIssue
    {
        return LogIssue::query()->create(array_merge([
            'fingerprint' => hash('sha256', $message),
            'level' => 'error',
            'normalized_message' => $message,
            'occurrences_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ], $attributes));
    }

    private function createOccurrence(LogIssue $issue, string $source): LogOccurrence
    {
        return LogOccurrence::query()->create([
            'issue_id' => $issue->id,
            'level' => 'error',
            'file_path_snapshot' => $source,
            'message' => $issue->normalized_message,
            'occurred_at' => now(),
        ]);
    }
}
