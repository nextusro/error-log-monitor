<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Actions;

use Illuminate\Support\Carbon;
use Nextus\ErrorLogMonitor\Models\LogFile;
use Nextus\ErrorLogMonitor\Services\LogFileDiscovery;

class MoveLogCursorsToCurrentPosition
{
    public function __construct(private readonly LogFileDiscovery $discovery) {}

    public function handle(): int
    {
        $basePath = rtrim((string) config('error-log-monitor.logs.base_path', storage_path('logs')), DIRECTORY_SEPARATOR);
        $processedFiles = 0;

        foreach ($this->discovery->discover() as $path) {
            $relativePath = $this->discovery->relativePath($basePath, $path);
            $size = filesize($path) ?: 0;

            LogFile::query()->updateOrCreate(
                ['path' => $path],
                [
                    'relative_path' => $relativePath,
                    'directory' => dirname($relativePath) !== '.' ? dirname($relativePath) : null,
                    'filename' => basename($relativePath),
                    'size' => $size,
                    'last_offset' => $size,
                    'inode' => @fileinode($path) ?: null,
                    'last_modified_at' => Carbon::createFromTimestamp(filemtime($path) ?: time()),
                    'last_scanned_at' => now(),
                    'is_missing' => false,
                    'missing_since' => null,
                ]
            );

            $processedFiles++;
        }

        return $processedFiles;
    }
}
