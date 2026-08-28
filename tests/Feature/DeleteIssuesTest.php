<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Models\LogOccurrence;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class DeleteIssuesTest extends TestCase
{
    public function test_issue_and_its_occurrences_are_permanently_deleted(): void
    {
        $issue = $this->createIssue('single-delete');
        $occurrence = $this->createOccurrence($issue);
        app(SettingStore::class)->put('dashboard', 'deletion_enabled', true);

        $response = $this->delete(route('error-log-monitor.issues.destroy', $issue));

        $response->assertRedirect();
        $response->assertSessionHas('error-log-monitor.success', 'The issue and its occurrences were permanently deleted.');
        $this->assertDatabaseMissing('error_log_monitor_issues', ['id' => $issue->id]);
        $this->assertDatabaseMissing('error_log_monitor_occurrences', ['id' => $occurrence->id]);
    }

    public function test_issue_deletion_is_forbidden_by_default(): void
    {
        $issue = $this->createIssue('disabled-delete');

        $this->delete(route('error-log-monitor.issues.destroy', $issue))->assertForbidden();

        $this->assertDatabaseHas('error_log_monitor_issues', ['id' => $issue->id]);
    }

    public function test_selected_issues_and_occurrences_are_deleted_in_bulk(): void
    {
        $openIssue = $this->createIssue('bulk-open');
        $resolvedIssue = $this->createIssue('bulk-resolved', ['resolved_at' => now()]);
        $untouchedIssue = $this->createIssue('bulk-untouched');
        $openOccurrence = $this->createOccurrence($openIssue);
        $resolvedOccurrence = $this->createOccurrence($resolvedIssue);
        app(SettingStore::class)->put('dashboard', 'deletion_enabled', true);

        $response = $this->post(route('error-log-monitor.issues.destroy-bulk'), [
            'issue_ids' => [$openIssue->id, $resolvedIssue->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error-log-monitor.success', '2 issues were permanently deleted.');
        $this->assertDatabaseMissing('error_log_monitor_issues', ['id' => $openIssue->id]);
        $this->assertDatabaseMissing('error_log_monitor_issues', ['id' => $resolvedIssue->id]);
        $this->assertDatabaseMissing('error_log_monitor_occurrences', ['id' => $openOccurrence->id]);
        $this->assertDatabaseMissing('error_log_monitor_occurrences', ['id' => $resolvedOccurrence->id]);
        $this->assertDatabaseHas('error_log_monitor_issues', ['id' => $untouchedIssue->id]);
    }

    public function test_bulk_deletion_requires_bulk_actions_to_be_enabled(): void
    {
        $issue = $this->createIssue('bulk-disabled');
        $settings = app(SettingStore::class);
        $settings->put('dashboard', 'deletion_enabled', true);
        $settings->put('dashboard', 'bulk_actions_enabled', false);

        $this->post(route('error-log-monitor.issues.destroy-bulk'), [
            'issue_ids' => [$issue->id],
        ])->assertForbidden();

        $this->assertDatabaseHas('error_log_monitor_issues', ['id' => $issue->id]);
    }

    public function test_deletion_can_be_enabled_from_settings(): void
    {
        $response = $this->put(route('error-log-monitor.settings.deletion.update'), ['enabled' => true]);

        $response->assertRedirect();
        $response->assertSessionHas('error-log-monitor.success', 'Permanent deletion was enabled.');
        $this->assertTrue(app(SettingStore::class)->get('dashboard', 'deletion_enabled'));
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

    private function createOccurrence(LogIssue $issue): LogOccurrence
    {
        return LogOccurrence::query()->create([
            'issue_id' => $issue->id,
            'level' => 'error',
            'message' => $issue->normalized_message,
            'occurred_at' => now(),
        ]);
    }
}
