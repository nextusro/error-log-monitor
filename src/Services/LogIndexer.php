<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Support\Carbon;
use Nextus\ErrorLogMonitor\Models\IndexRun;
use Nextus\ErrorLogMonitor\Models\LogFile;
use SplFileObject;
use Throwable;

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
        $settings = app(SettingStore::class);
        $maxRuntime = max(5, (int) $settings->get('indexing', 'max_runtime_seconds'));
        $maxFiles = max(1, (int) $settings->get('indexing', 'max_files_per_run'));
        $maxLines = max(100, (int) $settings->get('indexing', 'max_lines_per_file'));
        $basePath = rtrim((string) config('error-log-monitor.logs.base_path', storage_path('logs')), DIRECTORY_SEPARATOR);

        $paths = $this->discovery->discover();
        $priorityPaths = $onlyFile === null ? $this->priorityPaths($paths, $basePath) : [];
        $orderedPaths = $this->orderedPaths($paths, $priorityPaths, $onlyFile, $fresh);
        $stats = [
            'files' => 0, 'entries' => 0, 'issues' => 0, 'skipped' => 0,
            'discovered_files' => count($paths), 'pending_files' => 0,
            'partially_processed_files' => 0, 'failed_files' => 0,
            'processed_lines' => 0, 'completed' => true, 'stop_reason' => null,
        ];
        $errors = [];
        $endCursor = null;
        $startCursor = isset($orderedPaths[0]) ? $this->discovery->relativePath($basePath, $orderedPaths[0]) : null;

        foreach ($orderedPaths as $path) {
            if (! $this->monitoringState->isEnabled()) {
                $stats['completed'] = false;
                $stats['stop_reason'] = 'monitoring_disabled';
                break;
            }

            if ($stats['files'] >= $maxFiles) {
                $stats['completed'] = false;
                $stats['stop_reason'] = 'file_limit';
                break;
            }

            if ((microtime(true) - $startedAt) >= $maxRuntime) {
                $stats['completed'] = false;
                $stats['stop_reason'] = 'runtime_limit';
                break;
            }

            $relativePath = $this->discovery->relativePath($basePath, $path);
            if (! in_array($path, $priorityPaths, true)) {
                $endCursor = $relativePath;
            }

            try {
                $result = $this->indexFile($path, $relativePath, $fresh, $maxLines);
                $stats['entries'] += $result['entries'];
                $stats['issues'] += $result['issues'];
                $stats['processed_lines'] += $result['lines'];

                if ($result['partial']) {
                    $stats['partially_processed_files']++;
                }
            } catch (Throwable $exception) {
                $stats['failed_files']++;
                $errors[] = ['file' => $relativePath, 'message' => $exception->getMessage()];
            }

            $stats['files']++;
        }

        $stats['pending_files'] = max(0, count($orderedPaths) - $stats['files']);

        if ($stats['failed_files'] > 0) {
            $stats['completed'] = false;
            $stats['stop_reason'] = 'read_error';
        } elseif ($stats['partially_processed_files'] > 0 && $stats['stop_reason'] === null) {
            $stats['completed'] = false;
            $stats['stop_reason'] = 'line_limit';
        }

        $this->markMissingFiles($paths);

        if ($onlyFile === null) {
            $run = IndexRun::query()->create([
                'started_at' => Carbon::createFromTimestamp($startedAt),
                'finished_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'status' => $stats['completed'] ? 'completed' : ($stats['failed_files'] > 0 ? 'failed' : 'partial'),
                'stop_reason' => $stats['stop_reason'],
                'discovered_files' => $stats['discovered_files'],
                'processed_files' => $stats['files'],
                'pending_files' => $stats['pending_files'],
                'partially_processed_files' => $stats['partially_processed_files'],
                'failed_files' => $stats['failed_files'],
                'processed_lines' => $stats['processed_lines'],
                'parsed_entries' => $stats['entries'],
                'indexed_issues' => $stats['issues'],
                'start_cursor' => $startCursor,
                'end_cursor' => $endCursor,
                'errors' => $errors !== [] ? $errors : null,
            ]);

            $this->notifications->checkIndexingHealth($run);
        }

        $this->notifications->checkDatabaseSize();

        return $stats;
    }

    /**
     * @return array{entries:int,issues:int,lines:int,partial:bool}
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

        return [
            'entries' => count($entries),
            'issues' => $indexed,
            'lines' => count($lines['lines']),
            'partial' => $lines['offset'] < $size,
        ];
    }

    /**
     * @param  list<string>  $paths
     * @param  list<string>  $priorityPaths
     * @return list<string>
     */
    private function orderedPaths(array $paths, array $priorityPaths, ?string $onlyFile, bool $fresh): array
    {
        if ($onlyFile !== null) {
            return array_values(array_filter($paths, function (string $path) use ($onlyFile): bool {
                $basePath = (string) config('error-log-monitor.logs.base_path', storage_path('logs'));

                return $path === $onlyFile || $this->discovery->relativePath($basePath, $path) === $onlyFile;
            }));
        }

        $regularPaths = array_values(array_diff($paths, $priorityPaths));

        if ($fresh || $regularPaths === []) {
            return array_values(array_merge($priorityPaths, $regularPaths));
        }

        $lastCursor = IndexRun::query()->whereNotNull('end_cursor')->latest('id')->value('end_cursor');

        if (! is_string($lastCursor)) {
            return array_values(array_merge($priorityPaths, $regularPaths));
        }

        $basePath = (string) config('error-log-monitor.logs.base_path', storage_path('logs'));
        $cursorIndex = array_search($lastCursor, array_map(
            fn (string $path): string => $this->discovery->relativePath($basePath, $path),
            $regularPaths,
        ), true);

        if ($cursorIndex === false) {
            return array_values(array_merge($priorityPaths, $regularPaths));
        }

        $nextIndex = ($cursorIndex + 1) % count($regularPaths);
        $rotatedPaths = array_merge(
            array_slice($regularPaths, $nextIndex),
            array_slice($regularPaths, 0, $nextIndex),
        );

        return array_values(array_merge($priorityPaths, $rotatedPaths));
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function priorityPaths(array $paths, string $basePath): array
    {
        $patterns = config('error-log-monitor.indexing.priority_files', ['laravel.log']);

        if (! is_array($patterns)) {
            return [];
        }

        return array_values(array_filter($paths, function (string $path) use ($basePath, $patterns): bool {
            $relativePath = $this->discovery->relativePath($basePath, $path);

            foreach ($patterns as $pattern) {
                if (is_string($pattern) && ($relativePath === $pattern || fnmatch($pattern, $relativePath))) {
                    return true;
                }
            }

            return false;
        }));
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
