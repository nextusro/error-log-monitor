<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class BulkIssuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) app(SettingStore::class)->get('dashboard', 'bulk_actions_enabled');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'issue_ids' => ['required', 'array', 'min:1', 'max:500'],
            'issue_ids.*' => ['required', 'integer', 'distinct', 'exists:error_log_monitor_issues,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'issue_ids.required' => trans('error-log-monitor::messages.validation.bulk_required'),
            'issue_ids.min' => trans('error-log-monitor::messages.validation.bulk_required'),
            'issue_ids.max' => trans('error-log-monitor::messages.validation.bulk_max'),
            'issue_ids.*.exists' => trans('error-log-monitor::messages.validation.bulk_exists'),
        ];
    }
}
