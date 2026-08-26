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
                ->with('error-log-monitor.success', 'Monitorizarea a fost suspendată. Erorile noi nu vor fi indexate.');
        }

        $message = $request->validated('resume_mode') === 'from_now'
            ? "Monitorizarea a fost activată. {$movedCursors} fișiere vor fi urmărite doar pentru erorile viitoare."
            : 'Monitorizarea a fost activată și va recupera erorile încă disponibile în fișierele de log.';

        return redirect()
            ->back()
            ->with('error-log-monitor.success', $message);
    }
}
