<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Nextus\ErrorLogMonitor\Http\Requests\SaveNormalizationRuleRequest;
use Nextus\ErrorLogMonitor\Models\NormalizationRule;
use Nextus\ErrorLogMonitor\Services\GroupingStateManager;

class UpdateNormalizationRuleController extends Controller
{
    public function __invoke(
        SaveNormalizationRuleRequest $request,
        NormalizationRule $normalizationRule,
        GroupingStateManager $groupingStateManager,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $normalizationRule, $groupingStateManager): void {
            $normalizationRule->update($request->validated());
            $groupingStateManager->markPending();
        });

        return back()->with('error-log-monitor.success', trans('error-log-monitor::messages.normalization.updated'));
    }
}
