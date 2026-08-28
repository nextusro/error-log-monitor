<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Facades\Schema;
use Nextus\ErrorLogMonitor\Models\NormalizationRule;

class MessageNormalizer
{
    /** @var list<NormalizationRule>|null */
    private ?array $activeRules = null;

    public function __construct(private readonly NormalizationTemplateCompiler $templateCompiler) {}

    public function normalize(string $message): string
    {
        $message = trim($message);

        foreach ($this->rules() as $rule) {
            $message = $this->applyRule($message, $rule);
        }

        return $this->normalizeBuiltInPatterns($message);
    }

    public function normalizeWithRule(string $message, string $pattern, string $replacement): string
    {
        $normalized = trim($message);

        foreach ($this->rules() as $rule) {
            $normalized = $this->applyRule($normalized, $rule);
        }

        $normalized = preg_replace($pattern, $replacement, $normalized);

        return $this->normalizeBuiltInPatterns($normalized ?? trim($message));
    }

    private function normalizeBuiltInPatterns(string $message): string
    {
        $message = preg_replace('/\b\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}\b/', '{datetime}', $message) ?? $message;
        $message = preg_replace('/\b\d+\b/', '{number}', $message) ?? $message;
        $message = preg_replace('/0x[0-9a-f]+/i', '{hex}', $message) ?? $message;
        $message = preg_replace('/\s+/', ' ', $message) ?? $message;

        return trim($message);
    }

    private function applyRule(string $message, NormalizationRule $rule): string
    {
        if ($rule->type === 'template') {
            return $this->templateCompiler->normalize($message, $rule->pattern);
        }

        return preg_replace($rule->pattern, $rule->replacement, $message) ?? $message;
    }

    /**
     * @return iterable<NormalizationRule>
     */
    private function rules(): iterable
    {
        if ($this->activeRules !== null) {
            return $this->activeRules;
        }

        if (! Schema::hasTable('error_log_monitor_normalization_rules')) {
            return $this->activeRules = [];
        }

        return $this->activeRules = NormalizationRule::query()
            ->where('enabled', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->all();
    }
}
