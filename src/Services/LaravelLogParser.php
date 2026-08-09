<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Carbon;
use Nextus\ErrorLogMonitor\Data\ParsedLogEntry;

class LaravelLogParser
{
    private const ENTRY_PATTERN = '/^\[(?<date>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(?<env>[\w.-]+)\.(?<level>[A-Z]+):\s*(?<message>.*)$/';

    /**
     * @param list<string> $lines
     * @return list<ParsedLogEntry>
     */
    public function parseLines(array $lines): array
    {
        $entries = [];
        $current = null;
        $buffer = [];

        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");

            if (preg_match(self::ENTRY_PATTERN, $line, $matches) === 1) {
                if ($current !== null) {
                    $entries[] = $this->makeEntry($current, $buffer);
                }

                $current = $matches;
                $buffer = [];
                continue;
            }

            if ($current !== null) {
                $buffer[] = $line;
            }
        }

        if ($current !== null) {
            $entries[] = $this->makeEntry($current, $buffer);
        }

        return $entries;
    }

    /**
     * @param array<string, string> $header
     * @param list<string> $continuationLines
     */
    private function makeEntry(array $header, array $continuationLines): ParsedLogEntry
    {
        $level = strtolower($header['level']);
        $message = trim($header['message']);
        $context = null;

        [$messageWithoutContext, $jsonContext] = $this->splitTrailingJsonContext($message);

        if ($jsonContext !== null) {
            $message = $messageWithoutContext;
            $context = $jsonContext;
        }

        $stackTrace = trim(implode("\n", $continuationLines));
        $stackTrace = $stackTrace !== '' ? $stackTrace : null;

        return new ParsedLogEntry(
            level: $level,
            message: $message,
            context: $context,
            stackTrace: $stackTrace,
            occurredAt: $this->parseDate($header['date']),
            exceptionClass: $this->extractExceptionClass($message, $stackTrace),
        );
    }

    /**
     * @return array{0:string,1:?string}
     */
    private function splitTrailingJsonContext(string $message): array
    {
        $message = trim($message);

        $position = strrpos($message, ' {');

        if ($position === false) {
            return [$message, null];
        }

        $possibleJson = substr($message, $position + 1);
        json_decode($possibleJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [$message, null];
        }

        return [trim(substr($message, 0, $position)), $possibleJson];
    }

    private function parseDate(string $date): ?Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractExceptionClass(string $message, ?string $stackTrace): ?string
    {
        $text = $message . "\n" . ($stackTrace ?? '');

        if (preg_match('/(?:^|\s)([A-Za-z_][A-Za-z0-9_\\\\]+(?:Exception|Error|Throwable))(?::|\s)/', $text, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
