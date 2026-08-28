<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class SaveNormalizationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['regex', 'template'])],
            'pattern' => ['required', 'string', 'max:1000'],
            'replacement' => ['present', 'string', 'max:1000'],
            'priority' => ['required', 'integer', 'between:0,10000'],
            'enabled' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $pattern = $this->input('pattern');

                if ($this->input('type') === 'regex' && is_string($pattern) && @preg_match($pattern, '') === false) {
                    $validator->errors()->add('pattern', trans('error-log-monitor::messages.normalization.invalid_pattern'));
                }
            },
        ];
    }
}
