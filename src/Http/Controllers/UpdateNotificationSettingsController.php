<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Http\Requests\UpdateNotificationSettingsRequest;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class UpdateNotificationSettingsController extends Controller
{
    public function __invoke(UpdateNotificationSettingsRequest $request, SettingStore $settings): RedirectResponse
    {
        $values = [
            'enabled' => $request->boolean('enabled'),
            'recipients' => $request->recipients(),
            'regressions_enabled' => $request->boolean('regressions_enabled'),
            'database_size_enabled' => $request->boolean('database_size_enabled'),
            'database_size_threshold_mb' => (int) $request->validated('database_size_threshold_mb'),
            'levels' => array_values($request->validated('levels', [])),
        ];

        if ($request->has('cooldown_minutes')) {
            $values['cooldown_minutes'] = (int) $request->validated('cooldown_minutes');
        }

        foreach ($values as $key => $value) {
            $settings->put('notifications', $key, $value, $request->user());
        }

        return back()->with('error-log-monitor.success', trans('error-log-monitor::messages.notifications.settings_saved'));
    }
}
