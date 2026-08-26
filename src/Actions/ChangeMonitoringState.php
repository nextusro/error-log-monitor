<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Nextus\ErrorLogMonitor\Services\MonitoringState;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class ChangeMonitoringState
{
    public function __construct(
        private readonly MonitoringState $monitoringState,
        private readonly SettingStore $settings,
        private readonly MoveLogCursorsToCurrentPosition $moveLogCursors,
    ) {}

    public function handle(bool $enabled, ?string $resumeMode, ?Authenticatable $actor): int
    {
        if ($enabled && ! $this->monitoringState->isAllowedByConfiguration()) {
            throw new InvalidArgumentException('Monitoring is disabled by application configuration.');
        }

        if ($enabled && ! in_array($resumeMode, ['catch_up', 'from_now'], true)) {
            throw new InvalidArgumentException('A valid resume mode is required when enabling monitoring.');
        }

        return DB::transaction(function () use ($enabled, $resumeMode, $actor): int {
            $movedCursors = 0;

            if ($enabled && $resumeMode === 'from_now') {
                $movedCursors = $this->moveLogCursors->handle();
            }

            $this->settings->put('general', 'monitoring_enabled', $enabled, $actor);

            return $movedCursors;
        });
    }
}
