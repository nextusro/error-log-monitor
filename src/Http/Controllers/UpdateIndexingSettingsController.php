<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Http\Requests\UpdateIndexingSettingsRequest;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class UpdateIndexingSettingsController extends Controller
{
    public function __invoke(UpdateIndexingSettingsRequest $request, SettingStore $settings): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            $settings->put('indexing', $key, $value, $request->user());
        }

        return back()->with('error-log-monitor.success', trans('error-log-monitor::messages.settings.indexing_saved'));
    }
}
