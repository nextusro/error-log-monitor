<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Nextus\ErrorLogMonitor\Data\ParsedLogEntry;

class LogEntryFingerprinter
{
    public function fingerprint(ParsedLogEntry $entry): string
    {
        return hash('sha256', implode('|', [
            $entry->level,
            $entry->exceptionClass ?? '',
            $this->normalizeMessage($entry->message),
            $this->topStackFrame($entry->stackTrace),
        ]));
    }

    public function normalizeMessage(string $message): string
    {
        $message = trim($message);
        $message = preg_replace('/\b\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}\b/', '{datetime}', $message) ?? $message;
        $message = preg_replace('/\b\d+\b/', '{number}', $message) ?? $message;
        $message = preg_replace('/0x[0-9a-f]+/i', '{hex}', $message) ?? $message;
        $message = preg_replace('/\s+/', ' ', $message) ?? $message;

        return trim($message);
    }

    private function topStackFrame(?string $stackTrace): string
    {
        if ($stackTrace === null || trim($stackTrace) === '') {
            return '';
        }

        foreach (explode("\n", $stackTrace) as $line) {
            $line = trim($line);

            if ($line !== '' && str_starts_with($line, '#0')) {
                return $line;
            }
        }

        return trim(strtok($stackTrace, "\n") ?: '');
    }
}
