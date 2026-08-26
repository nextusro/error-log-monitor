<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Nextus\ErrorLogMonitor\Http\Requests\UpdateBulkActionsSettingRequest;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class UpdateBulkActionsSettingController extends Controller
{
    public function __invoke(
        UpdateBulkActionsSettingRequest $request,
        SettingStore $settings,
    ): RedirectResponse {
        $enabled = $request->boolean('enabled');
        $settings->put('dashboard', 'bulk_actions_enabled', $enabled, $request->user());

        return redirect()
            ->back()
            ->with(
                'error-log-monitor.success',
                $enabled ? 'Acțiunile bulk au fost activate.' : 'Acțiunile bulk au fost dezactivate.'
            );
    }
}
