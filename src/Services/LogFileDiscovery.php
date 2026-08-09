<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Nextus\ErrorLogMonitor\Support\PathMatcher;

class LogFileDiscovery
{
    public function __construct(private readonly PathMatcher $pathMatcher)
    {
    }

    /**
     * @return list<string>
     */
    public function discover(): array
    {
        $basePath = rtrim((string) config('error-log-monitor.logs.base_path', storage_path('logs')), DIRECTORY_SEPARATOR);

        if (! is_dir($basePath)) {
            return [];
        }

        $includePatterns = config('error-log-monitor.logs.include_files', ['*.log', '**/*.log']);
        $excludePatterns = config('error-log-monitor.logs.exclude_files', ['*.gz', '**/*.gz']);

        $paths = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            $relativePath = $this->relativePath($basePath, $path);

            if (! $this->pathMatcher->matchesAny($relativePath, $includePatterns)) {
                continue;
            }

            if ($this->pathMatcher->matchesAny($relativePath, $excludePatterns)) {
                continue;
            }

            $paths[] = $path;
        }

        sort($paths);

        return $paths;
    }

    public function relativePath(string $basePath, string $path): string
    {
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        $path = str_replace('\\', '/', $path);

        return ltrim(substr($path, strlen($basePath)), '/');
    }
}
