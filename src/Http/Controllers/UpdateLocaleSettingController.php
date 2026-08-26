<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Http\Requests\UpdateLocaleSettingRequest;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class UpdateLocaleSettingController extends Controller
{
    public function __invoke(UpdateLocaleSettingRequest $request, SettingStore $settings): RedirectResponse
    {
        $locale = (string) $request->validated('locale');
        $settings->put('dashboard', 'locale', $locale, $request->user());
        app()->setLocale($locale);

        return redirect()
            ->back()
            ->with('error-log-monitor.success', trans('error-log-monitor::messages.settings.language_saved'));
    }
}
