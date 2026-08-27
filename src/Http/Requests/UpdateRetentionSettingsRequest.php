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
            'resolved_issues_days' => ['required', 'integer', 'min:0', 'max:36500'],
            'ignored_issues_days' => ['required', 'integer', 'min:0', 'max:36500'],
            'open_issues_days' => ['required', 'integer', 'min:0', 'max:36500'],
        ];
    }
}
