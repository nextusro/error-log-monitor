<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateNotificationSettingsRequest extends FormRequest
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
            'recipients' => ['nullable', 'string', 'max:10000'],
            'regressions_enabled' => ['required', 'boolean'],
            'database_size_enabled' => ['required', 'boolean'],
            'database_size_threshold_mb' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'levels' => ['present', 'array'],
            'levels.*' => ['string', Rule::in(config('error-log-monitor.dashboard.levels', []))],
        ];
    }

    /**
     * @return list<string>
     */
    public function recipients(): array
    {
        $raw = (string) $this->validated('recipients', '');
        $recipients = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_map('strtolower', $recipients)));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->recipients() as $recipient) {
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                    $validator->errors()->add('recipients', trans('error-log-monitor::messages.validation.notification_email_invalid', [
                        'email' => $recipient,
                    ]));
                }
            }

            if ($this->boolean('enabled') && $this->recipients() === []) {
                $validator->errors()->add('recipients', trans('error-log-monitor::messages.validation.notification_recipient_required'));
            }
        });
    }
}
