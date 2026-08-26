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
            'issue_ids.required' => 'Selectează cel puțin o eroare.',
            'issue_ids.min' => 'Selectează cel puțin o eroare.',
            'issue_ids.max' => 'Poți modifica maximum 500 de erori într-o singură operație.',
            'issue_ids.*.exists' => 'Una dintre erorile selectate nu mai există.',
        ];
    }
}
