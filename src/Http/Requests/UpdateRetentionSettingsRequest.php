<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRetentionSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'occurrences_days' => ['required', 'integer', 'min:0', 'max:36500'],
            'max_occurrences_per_issue' => ['required', 'integer', 'min:0', 'max:100000'],
            'optimize_tables_after_prune' => ['required', 'boolean'],
            'resolved_issues_days' => ['required', 'integer', 'min:0', 'max:36500'],
            'ignored_issues_days' => ['required', 'integer', 'min:0', 'max:36500'],
            'open_issues_days' => ['required', 'integer', 'min:0', 'max:36500'],
        ];
    }
}
