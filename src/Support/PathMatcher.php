<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Support;

class PathMatcher
{
    public function matchesAny(string $relativePath, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            if ($this->matches($relativePath, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function matches(string $relativePath, string $pattern): bool
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $pattern = str_replace('\\', '/', $pattern);

        if (fnmatch($pattern, $relativePath)) {
            return true;
        }

        if (str_starts_with($pattern, '**/')) {
            return fnmatch(substr($pattern, 3), basename($relativePath)) || fnmatch($pattern, $relativePath);
        }

        return false;
    }
}
