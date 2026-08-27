<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Http\Requests\UpdateRetentionSettingsRequest;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class UpdateRetentionSettingsController extends Controller
{
    public function __invoke(UpdateRetentionSettingsRequest $request, SettingStore $settings): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            $settings->put('retention', $key, $value, $request->user());
        }

        return back()->with('error-log-monitor.success', trans('error-log-monitor::messages.settings.retention_saved'));
    }
}
