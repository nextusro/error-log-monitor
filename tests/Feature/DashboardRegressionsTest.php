<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class DashboardRegressionsTest extends TestCase
{
    public function test_regressions_filter_lists_only_regressions_included_by_statistics(): void
    {
        $regression = $this->createIssue('regression', [
            'resolved_at' => now()->subHours(2),
            'last_seen_at' => now()->subHour(),
        ]);
        $this->createIssue('ordinary-resolved-issue', [
            'resolved_at' => now(),
            'last_seen_at' => now()->subHour(),
        ]);
        $this->createIssue('ordinary-unresolved-issue');
        $this->createIssue('warning-regression', [
            'level' => 'warning',
            'resolved_at' => now()->subHours(2),
            'last_seen_at' => now()->subHour(),
        ]);

        $response = $this->get(route('error-log-monitor.dashboard', [
            'interval' => '24h',
            'status' => 'regressions',
        ]));

        $response->assertOk();
        $response->assertSee($regression->normalized_message);
        $response->assertDontSee('ordinary-resolved-issue');
        $response->assertDontSee('ordinary-unresolved-issue');
        $response->assertDontSee('warning-regression');
    }

    public function test_regressions_card_links_to_the_matching_interval_and_filter(): void
    {
        $response = $this->get(route('error-log-monitor.dashboard', ['interval' => '7d']));

        $response->assertOk();
        $response->assertSee(
            route('error-log-monitor.dashboard', ['interval' => '7d', 'status' => 'regressions']),
        );
        $response->assertSee('class="stat-value-link"', false);
        $response->assertDontSee('stat-card-link', false);
        $response->assertSee('value="regressions"', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createIssue(string $fingerprint, array $attributes = []): LogIssue
    {
        return LogIssue::query()->create(array_merge([
            'fingerprint' => hash('sha256', $fingerprint),
            'level' => 'error',
            'normalized_message' => $fingerprint,
            'occurrences_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ], $attributes));
    }
}
