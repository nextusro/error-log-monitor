<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Carbon;
use Nextus\ErrorLogMonitor\Models\LogFile;
use SplFileObject;

class LogIndexer
{
    public function __construct(
        private readonly LogFileDiscovery $discovery,
        private readonly LaravelLogParser $parser,
        private readonly LogIssueIndexer $issueIndexer,
        private readonly MonitoringState $monitoringState,
        private readonly NotificationManager $notifications,
    ) {}

    /**
     * @return array{files:int,entries:int,issues:int,skipped:int}
     */
    public function run(?string $onlyFile = null, bool $fresh = false): array
    {
        if (! $this->monitoringState->isEnabled()) {
            return ['files' => 0, 'entries' => 0, 'issues' => 0, 'skipped' => 0];
        }

        $startedAt = microtime(true);
        $maxRuntime = (int) config('error-log-monitor.indexing.max_runtime_seconds', 30);
        $maxFiles = (int) config('error-log-monitor.indexing.max_files_per_run', 50);
        $maxLines = (int) config('error-log-monitor.indexing.max_lines_per_file', 5000);
        $basePath = rtrim((string) config('error-log-monitor.logs.base_path', storage_path('logs')), DIRECTORY_SEPARATOR);

        $paths = $this->discovery->discover();
        $seenPaths = [];
        $stats = ['files' => 0, 'entries' => 0, 'issues' => 0, 'skipped' => 0];

        foreach ($paths as $path) {
            if (! $this->monitoringState->isEnabled()) {
                break;
            }

            if ($stats['files'] >= $maxFiles || (microtime(true) - $startedAt) >= $maxRuntime) {
                break;
            }

            $relativePath = $this->discovery->relativePath($basePath, $path);
            $seenPaths[] = $path;

            if ($onlyFile !== null && $onlyFile !== $relativePath && $onlyFile !== $path) {
                $stats['skipped']++;

                continue;
            }

            $result = $this->indexFile($path, $relativePath, $fresh, $maxLines);
            $stats['files']++;
            $stats['entries'] += $result['entries'];
            $stats['issues'] += $result['issues'];
        }

        $this->markMissingFiles($seenPaths);
        $this->notifications->checkDatabaseSize();

        return $stats;
    }

    /**
     * @return array{entries:int,issues:int}
     */
    private function indexFile(string $path, string $relativePath, bool $fresh, int $maxLines): array
    {
        $directory = dirname($relativePath) !== '.' ? dirname($relativePath) : null;
        $filename = basename($relativePath);
        $size = filesize($path) ?: 0;
        $inode = @fileinode($path) ?: null;
        $modifiedAt = Carbon::createFromTimestamp(filemtime($path) ?: time());

        /** @var LogFile $file */
        $file = LogFile::query()->firstOrNew(['path' => $path]);
        $previousOffset = (int) ($file->last_offset ?? 0);
        $offset = $fresh ? 0 : $previousOffset;

        $wasReplaced = $file->exists
            && $file->inode !== null
            && $inode !== null
            && (int) $file->inode !== $inode;

        if (! $fresh && $file->exists && ($size < $previousOffset || $wasReplaced)) {
            $offset = 0;
            $file->was_truncated_at = now();
        }

        $lines = $this->readLines($path, $offset, $maxLines);
        $entries = $this->parser->parseLines($lines['lines']);

        $file->fill([
            'relative_path' => $relativePath,
            'directory' => $directory,
            'filename' => $filename,
            'size' => $size,
            'last_offset' => $lines['offset'],
            'inode' => $inode,
            'last_modified_at' => $modifiedAt,
            'last_scanned_at' => now(),
            'is_missing' => false,
            'missing_since' => null,
        ]);
        $file->save();

        $allowedLevels = config('error-log-monitor.dashboard.levels', []);
        $indexed = 0;

        foreach ($entries as $entry) {
            if (! in_array($entry->level, $allowedLevels, true)) {
                continue;
            }

            $this->issueIndexer->index($file, $entry);
            $indexed++;
        }

        return ['entries' => count($entries), 'issues' => $indexed];
    }

    /**
     * @return array{lines:list<string>,offset:int}
     */
    private function readLines(string $path, int $offset, int $maxLines): array
    {
        $file = new SplFileObject($path, 'rb');

        if ($offset > 0) {
            $file->fseek($offset);
        }

        $lines = [];
        $count = 0;

        while (! $file->eof() && $count < $maxLines) {
            $line = $file->fgets();

            if ($line === false) {
                break;
            }

            $lines[] = $line;
            $count++;
        }

        return [
            'lines' => $lines,
            'offset' => $file->ftell(),
        ];
    }

    /**
     * @param  list<string>  $seenPaths
     */
    private function markMissingFiles(array $seenPaths): void
    {
        LogFile::query()
            ->whereNotIn('path', $seenPaths)
            ->where('is_missing', false)
            ->update([
                'is_missing' => true,
                'missing_since' => now(),
            ]);
    }
}
