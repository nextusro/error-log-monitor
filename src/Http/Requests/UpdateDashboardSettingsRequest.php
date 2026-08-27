<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDashboardSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'per_page' => ['required', 'integer', 'min:1', 'max:200'],
            'default_interval' => ['required', Rule::in(collect(config('error-log-monitor.dashboard.intervals', []))
                ->map(static fn (mixed $value, int|string $key): mixed => is_string($key) ? $key : $value)
                ->values()
                ->all())],
            'date_format' => ['required', Rule::in(['Y-m-d H:i:s', 'd.m.Y H:i:s', 'd/m/Y H:i:s'])],
            'statistics_collapsed_by_default' => ['required', 'boolean'],
            'default_theme' => ['required', Rule::in(['light', 'dark'])],
        ];
    }
}
