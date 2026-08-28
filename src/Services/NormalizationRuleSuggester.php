<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use InvalidArgumentException;

class NormalizationRuleSuggester
{
    /**
     * @param list<string> $messages
     * @return array{name: string, pattern: string, replacement: string}
     */
    public function suggest(array $messages): array
    {
        if (count($messages) < 2) {
            throw new InvalidArgumentException('At least two messages are required.');
        }

        $tokensByMessage = array_map(fn (string $message): array => $this->bracketTokens($message), $messages);
        $commonKeys = array_keys($tokensByMessage[0]);

        foreach ($commonKeys as $key) {
            $values = array_map(static fn (array $tokens): ?string => $tokens[$key] ?? null, $tokensByMessage);

            if (in_array(null, $values, true) || count(array_unique($values)) < 2 || $this->onlyNumbers($values)) {
                continue;
            }

            $valuePattern = $this->valuePattern($values);
            $escapedKey = preg_quote($key, '/');

            return [
                'name' => ucfirst($key).' identifier',
                'pattern' => '/\\['.$escapedKey.':'.$valuePattern.'\\]/i',
                'replacement' => '['.$key.':{'.$key.'}]',
            ];
        }

        throw new InvalidArgumentException('A safe variable bracket value could not be inferred from the selected issues.');
    }

    /** @return array<string, string> */
    private function bracketTokens(string $message): array
    {
        preg_match_all('/\[([A-Za-z][A-Za-z0-9_-]*):([^\]]+)\]/', $message, $matches, PREG_SET_ORDER);
        $tokens = [];

        foreach ($matches as $match) {
            $tokens[$match[1]] = $match[2];
        }

        return $tokens;
    }

    /** @param list<?string> $values */
    private function onlyNumbers(array $values): bool
    {
        return collect($values)->every(static fn (?string $value): bool => $value !== null && ctype_digit($value));
    }

    /** @param list<?string> $values */
    private function valuePattern(array $values): string
    {
        if (collect($values)->every(static fn (?string $value): bool => $value !== null && preg_match('/^[A-Z0-9]+$/i', $value) === 1)) {
            return '[A-Z0-9]+';
        }

        if (collect($values)->every(static fn (?string $value): bool => $value !== null && preg_match('/^[0-9a-f-]{36}$/i', $value) === 1)) {
            return '[0-9a-f-]{36}';
        }

        return '[^\\]]+';
    }
}
