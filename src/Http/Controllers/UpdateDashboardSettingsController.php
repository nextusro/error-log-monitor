<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Http\Requests\UpdateDashboardSettingsRequest;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class UpdateDashboardSettingsController extends Controller
{
    public function __invoke(UpdateDashboardSettingsRequest $request, SettingStore $settings): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            $settings->put('dashboard', $key, $value, $request->user());
        }

        return back()->with('error-log-monitor.success', trans('error-log-monitor::messages.settings.dashboard_saved'));
    }
}
