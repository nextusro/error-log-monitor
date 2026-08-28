<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Nextus\ErrorLogMonitor\Models\NormalizationRule;
use Nextus\ErrorLogMonitor\Services\GroupingStateManager;

class DeleteNormalizationRuleController extends Controller
{
    public function __invoke(NormalizationRule $normalizationRule, GroupingStateManager $groupingStateManager): RedirectResponse
    {
        DB::transaction(function () use ($normalizationRule, $groupingStateManager): void {
            $normalizationRule->delete();
            $groupingStateManager->markPending();
        });

        return back()->with('error-log-monitor.success', trans('error-log-monitor::messages.normalization.deleted'));
    }
}
