<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuggestNormalizationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'issue_ids' => ['required', 'array', 'min:2', 'max:50'],
            'issue_ids.*' => ['required', 'integer', 'distinct', 'exists:error_log_monitor_issues,id'],
        ];
    }
}
