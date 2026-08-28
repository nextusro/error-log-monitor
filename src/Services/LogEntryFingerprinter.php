<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Nextus\ErrorLogMonitor\Data\ParsedLogEntry;

class LogEntryFingerprinter
{
    public function __construct(private readonly MessageNormalizer $normalizer) {}

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
        return $this->normalizer->normalize($message);
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
