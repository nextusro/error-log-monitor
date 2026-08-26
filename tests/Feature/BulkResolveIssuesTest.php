<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class BulkResolveIssuesTest extends TestCase
{
    public function test_selected_open_issues_are_resolved_in_bulk(): void
    {
        $firstOpenIssue = $this->createIssue('first-open');
        $secondOpenIssue = $this->createIssue('second-open');
        $resolvedIssue = $this->createIssue('already-resolved', ['resolved_at' => now()->subDay()]);

        $response = $this->post(route('error-log-monitor.issues.resolve-bulk'), [
            'issue_ids' => [$firstOpenIssue->id, $secondOpenIssue->id, $resolvedIssue->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error-log-monitor.success', '2 issue-uri au fost marcate ca rezolvate.');
        $this->assertNotNull($firstOpenIssue->fresh()->resolved_at);
        $this->assertNotNull($secondOpenIssue->fresh()->resolved_at);
        $this->assertTrue($resolvedIssue->resolved_at->equalTo($resolvedIssue->fresh()->resolved_at));
    }

    public function test_at_least_one_issue_must_be_selected(): void
    {
        $response = $this->from(route('error-log-monitor.dashboard'))->post(
            route('error-log-monitor.issues.resolve-bulk'),
            ['issue_ids' => []],
        );

        $response->assertRedirect(route('error-log-monitor.dashboard'));
        $response->assertSessionHasErrors('issue_ids');
    }

    public function test_bulk_endpoint_is_forbidden_when_feature_is_disabled(): void
    {
        $issue = $this->createIssue('still-open');
        app(SettingStore::class)->put('dashboard', 'bulk_actions_enabled', false);

        $response = $this->post(route('error-log-monitor.issues.resolve-bulk'), [
            'issue_ids' => [$issue->id],
        ]);

        $response->assertForbidden();
        $this->assertNull($issue->fresh()->resolved_at);
    }

    public function test_bulk_controls_follow_the_config_default(): void
    {
        config()->set('error-log-monitor.features.bulk_actions_enabled', false);

        $response = $this->get(route('error-log-monitor.dashboard'));

        $response->assertOk();
        $response->assertDontSee('id="bulk-actions-form"', false);
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
