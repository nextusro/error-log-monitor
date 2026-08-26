<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Nextus\ErrorLogMonitor\Actions\ChangeMonitoringState;
use Nextus\ErrorLogMonitor\Http\Requests\UpdateMonitoringStateRequest;

class UpdateMonitoringStateController extends Controller
{
    public function __invoke(
        UpdateMonitoringStateRequest $request,
        ChangeMonitoringState $action,
    ): RedirectResponse {
        try {
            $movedCursors = $action->handle(
                enabled: $request->boolean('enabled'),
                resumeMode: $request->validated('resume_mode'),
                actor: $request->user(),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->back()
                ->withErrors(['monitoring' => $exception->getMessage()]);
        }

        if (! $request->boolean('enabled')) {
            return redirect()
                ->back()
                ->with('error-log-monitor.success', trans('error-log-monitor::messages.monitoring.suspended'));
        }

        $message = $request->validated('resume_mode') === 'from_now'
            ? trans('error-log-monitor::messages.monitoring.enabled_from_now', ['count' => $movedCursors])
            : trans('error-log-monitor::messages.monitoring.enabled_catch_up');

        return redirect()
            ->back()
            ->with('error-log-monitor.success', $message);
    }
}
