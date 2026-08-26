<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBulkActionsSettingRequest extends FormRequest
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'enabled.required' => 'Starea acțiunilor bulk este obligatorie.',
        ];
    }
}
