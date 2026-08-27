<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndexingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'max_runtime_seconds' => ['required', 'integer', 'min:5', 'max:3600'],
            'max_files_per_run' => ['required', 'integer', 'min:1', 'max:10000'],
            'max_lines_per_file' => ['required', 'integer', 'min:100', 'max:1000000'],
            'incomplete_notification_enabled' => ['required', 'boolean'],
            'incomplete_notification_mode' => ['required', Rule::in(['immediate', 'stale'])],
            'stale_after_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'notification_cooldown_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'recovery_notification_enabled' => ['required', 'boolean'],
            'run_history_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ];
    }
}
