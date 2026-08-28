<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Database\Eloquent\Builder;
use Nextus\ErrorLogMonitor\Models\LogOccurrence;

class OccurrenceRetention
{
    public function trimIssue(int $issueId, int $maximum): int
    {
        if ($maximum <= 0) {
            return 0;
        }

        $firstOccurrenceId = LogOccurrence::query()
            ->where('issue_id', $issueId)
            ->min('id');

        if ($firstOccurrenceId === null) {
            return 0;
        }

        $retainedRecentIds = LogOccurrence::query()
            ->where('issue_id', $issueId)
            ->whereKeyNot($firstOccurrenceId)
            ->latest('id')
            ->limit(max(0, $maximum - 1))
            ->pluck('id');

        $retainedIds = $retainedRecentIds->prepend((int) $firstOccurrenceId);

        return $this->deleteInChunks(
            LogOccurrence::query()
                ->where('issue_id', $issueId)
                ->whereNotIn('id', $retainedIds),
        );
    }

    public function trimAll(int $maximum): int
    {
        if ($maximum <= 0) {
            return 0;
        }

        $deleted = 0;

        LogOccurrence::query()
            ->select('issue_id')
            ->groupBy('issue_id')
            ->havingRaw('COUNT(*) > ?', [$maximum])
            ->orderBy('issue_id')
            ->pluck('issue_id')
            ->each(function (int $issueId) use ($maximum, &$deleted): void {
                $deleted += $this->trimIssue($issueId, $maximum);
            });

        return $deleted;
    }

    public function deleteInChunks(Builder $query, int $chunkSize = 5000): int
    {
        $deleted = 0;

        do {
            $ids = (clone $query)->orderBy('id')->limit($chunkSize)->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += LogOccurrence::query()->whereKey($ids)->delete();
        } while ($ids->count() === $chunkSize);

        return $deleted;
    }
}
