<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocaleSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(array_keys(config('error-log-monitor.dashboard.locales', [])))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'locale.required' => trans('error-log-monitor::messages.validation.locale_required'),
            'locale.in' => trans('error-log-monitor::messages.validation.locale_invalid'),
        ];
    }
}
