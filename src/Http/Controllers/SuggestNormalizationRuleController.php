<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use Nextus\ErrorLogMonitor\Http\Requests\SuggestNormalizationRuleRequest;
use Nextus\ErrorLogMonitor\Models\LogIssue;
use Nextus\ErrorLogMonitor\Services\MessageNormalizer;
use Nextus\ErrorLogMonitor\Services\NormalizationRuleSuggester;

class SuggestNormalizationRuleController extends Controller
{
    public function __invoke(
        SuggestNormalizationRuleRequest $request,
        NormalizationRuleSuggester $suggester,
        MessageNormalizer $normalizer,
    ): View|RedirectResponse {
        $issues = LogIssue::query()
            ->whereIn('id', $request->validated('issue_ids'))
            ->orderBy('id')
            ->get();
        $normalizedMessages = $issues->pluck('last_message')
            ->map(static fn (?string $message): string => $normalizer->normalize((string) $message))
            ->all();

        try {
            $suggestion = $suggester->suggest($normalizedMessages);
        } catch (InvalidArgumentException) {
            return back()->withErrors([
                'issue_ids' => trans('error-log-monitor::messages.normalization.suggestion_failed'),
            ]);
        }

        return view('error-log-monitor::normalization-rule', [
            'suggestion' => $suggestion,
            'issues' => $issues,
            'previews' => $issues->mapWithKeys(static fn (LogIssue $issue): array => [
                $issue->id => $normalizer->normalizeWithRule(
                    (string) $issue->last_message,
                    $suggestion['pattern'],
                    $suggestion['replacement'],
                ),
            ]),
        ]);
    }
}
