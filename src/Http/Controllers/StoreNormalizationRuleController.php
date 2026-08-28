<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Nextus\ErrorLogMonitor\Http\Requests\SaveNormalizationRuleRequest;
use Nextus\ErrorLogMonitor\Models\NormalizationRule;
use Nextus\ErrorLogMonitor\Services\GroupingStateManager;

class StoreNormalizationRuleController extends Controller
{
    public function __invoke(SaveNormalizationRuleRequest $request, GroupingStateManager $groupingStateManager): RedirectResponse
    {
        DB::transaction(function () use ($request, $groupingStateManager): void {
            NormalizationRule::query()->create($request->validated());
            $groupingStateManager->markPending();
        });

        return redirect()->route('error-log-monitor.dashboard')->with(
            'error-log-monitor.success',
            trans('error-log-monitor::messages.normalization.saved'),
        );
    }
}
