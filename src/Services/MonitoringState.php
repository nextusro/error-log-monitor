<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Facades\Schema;
use Nextus\ErrorLogMonitor\Models\Setting;

class MonitoringState
{
    public function __construct(private readonly SettingStore $settings) {}

    public function isEnabled(): bool
    {
        return $this->isAllowedByConfiguration()
            && (bool) $this->settings->get('general', 'monitoring_enabled');
    }

    public function isAllowedByConfiguration(): bool
    {
        return (bool) config('error-log-monitor.enabled', true);
    }

    public function setting(): ?Setting
    {
        if (! Schema::hasTable('error_log_monitor_settings')) {
            return null;
        }

        return Setting::query()
            ->where('group', 'general')
            ->where('key', 'monitoring_enabled')
            ->first();
    }
}
