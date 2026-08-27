<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class DashboardIssuesQuery
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        $query = LogIssue::query()->latest('last_seen_at');

        $this->applyFocusedIssueFilter($query, $request);
        $this->applyLevelFilter($query, $request);
        $this->applyIntervalFilter($query, $request);
        $this->applySearchFilter($query, $request);
        $this->applyFileFilter($query, $request);
        $this->applyDirectoryFilter($query, $request);
        $this->applyStatusFilter($query, $request);

        return $query->paginate($this->perPage($request))->withQueryString();
    }

    private function applyFocusedIssueFilter(Builder $query, Request $request): void
    {
        $issueId = $request->query('issue_id');

        if ($issueId === null || $issueId === '') {
            return;
        }

        if (! is_numeric($issueId)) {
            return;
        }

        $query->whereKey((int) $issueId);
    }

    private function applyLevelFilter(Builder $query, Request $request): void
    {
        $level = $request->query('level');

        if (! is_string($level) || $level === '') {
            return;
        }

        if (! in_array($level, config('error-log-monitor.dashboard.levels', []), true)) {
            return;
        }

        $query->where('level', $level);
    }

    private function applyIntervalFilter(Builder $query, Request $request): void
    {
        $interval = $request->query('interval', app(SettingStore::class)->get('dashboard', 'default_interval'));

        if (! is_string($interval) || $interval === '' || $interval === 'all') {
            return;
        }

        $from = $this->resolveIntervalStart($interval);

        if ($from !== null) {
            $query->where('last_seen_at', '>=', $from);
        }
    }

    private function applySearchFilter(Builder $query, Request $request): void
    {
        $search = $request->query('query');

        if (! is_string($search) || trim($search) === '') {
            return;
        }

        $search = trim($search);

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('normalized_message', 'like', '%' . $search . '%')
                ->orWhere('exception_class', 'like', '%' . $search . '%')
                ->orWhere('last_message', 'like', '%' . $search . '%')
                ->orWhere('last_stack_trace', 'like', '%' . $search . '%')
                ->orWhere('last_file_path', 'like', '%' . $search . '%');
        });
    }

    private function applyFileFilter(Builder $query, Request $request): void
    {
        $file = $request->query('file');

        if (! is_string($file) || $file === '') {
            return;
        }

        $query->whereHas('occurrences', function (Builder $query) use ($file): void {
            $query->where('file_path_snapshot', $file);
        });
    }

    private function applyDirectoryFilter(Builder $query, Request $request): void
    {
        $directory = $request->query('directory');

        if (! is_string($directory) || $directory === '') {
            return;
        }

        $query->whereHas('occurrences', function (Builder $query) use ($directory): void {
            $query->where('file_path_snapshot', 'like', $directory . '/%');
        });
    }

    private function applyStatusFilter(Builder $query, Request $request): void
    {
        $status = $request->query('status', 'open');

        if (! is_string($status) || $status === '' || $status === 'all') {
            return;
        }

        match ($status) {
            'open' => $query->open(),
            'resolved' => $query->resolved(),
            'ignored' => $query->ignored(),
            'regressions' => $query
                ->regressions()
                ->whereIn('level', $this->levelsWithoutWarnings()),
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function levelsWithoutWarnings(): array
    {
        return array_values(array_filter(
            config('error-log-monitor.dashboard.levels', []),
            static fn (mixed $level): bool => is_string($level) && $level !== 'warning',
        ));
    }

    private function resolveIntervalStart(string $interval): ?Carbon
    {
        return match ($interval) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '14d' => now()->subDays(14),
            default => null,
        };
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', app(SettingStore::class)->get('dashboard', 'per_page'));

        return min(max($perPage, 1), 200);
    }
}
