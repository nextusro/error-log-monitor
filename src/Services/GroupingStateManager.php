<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Facades\DB;
use Nextus\ErrorLogMonitor\Models\GroupingState;

class GroupingStateManager
{
    public function __construct(private readonly IssueRegrouper $regrouper) {}

    public function markPending(): GroupingState
    {
        $state = GroupingState::query()->lockForUpdate()->find(1);

        if ($state === null) {
            return GroupingState::query()->create([
                'id' => 1,
                'rules_version' => 1,
                'grouped_rules_version' => 0,
                'regroup_requested_at' => now(),
            ]);
        }

        $state->update([
            'rules_version' => $state->rules_version + 1,
            'regroup_requested_at' => now(),
        ]);

        return $state->refresh();
    }

    public function isPending(): bool
    {
        $state = GroupingState::query()->find(1);

        return $state !== null && $state->rules_version !== $state->grouped_rules_version;
    }

    /** @return array{before: int, after: int, occurrences: int}|null */
    public function regroupIfPending(): ?array
    {
        return DB::transaction(function (): ?array {
            $state = GroupingState::query()->lockForUpdate()->find(1);

            if ($state === null || $state->rules_version === $state->grouped_rules_version) {
                return null;
            }

            $rulesVersion = $state->rules_version;
            $result = $this->regrouper->regroup();
            $state->update([
                'grouped_rules_version' => $rulesVersion,
                'last_regrouped_at' => now(),
            ]);

            return $result;
        }, 3);
    }
}
