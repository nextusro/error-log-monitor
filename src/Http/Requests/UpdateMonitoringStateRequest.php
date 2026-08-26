<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMonitoringStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'resume_mode' => [
                Rule::requiredIf($this->boolean('enabled')),
                'nullable',
                Rule::in(['catch_up', 'from_now']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'enabled.required' => 'Starea monitorizării este obligatorie.',
            'resume_mode.required' => 'Selectează cum dorești să reiei monitorizarea.',
            'resume_mode.in' => 'Modul de reluare selectat nu este valid.',
        ];
    }
}
