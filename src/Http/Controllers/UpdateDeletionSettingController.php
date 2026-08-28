<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Http\Requests\UpdateDeletionSettingRequest;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class UpdateDeletionSettingController extends Controller
{
    public function __invoke(
        UpdateDeletionSettingRequest $request,
        SettingStore $settings,
    ): RedirectResponse {
        $enabled = $request->boolean('enabled');
        $settings->put('dashboard', 'deletion_enabled', $enabled, $request->user());

        return redirect()
            ->back()
            ->with(
                'error-log-monitor.success',
                trans($enabled ? 'error-log-monitor::messages.deletion.enabled' : 'error-log-monitor::messages.deletion.disabled')
            );
    }
}
