<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Illuminate\Support\Facades\File;
use Nextus\ErrorLogMonitor\Models\IndexRun;
use Nextus\ErrorLogMonitor\Models\LogFile;
use Nextus\ErrorLogMonitor\Services\LogIndexer;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class LogIndexerProgressTest extends TestCase
{
    public function test_next_run_continues_after_the_last_processed_file(): void
    {
        File::put($this->logDirectory.'/a.log', "[2026-08-27 10:00:00] production.ERROR: A\n");
        File::put($this->logDirectory.'/b.log', "[2026-08-27 10:00:01] production.ERROR: B\n");
        app(SettingStore::class)->put('indexing', 'max_files_per_run', 1);

        $first = app(LogIndexer::class)->run();
        $second = app(LogIndexer::class)->run();

        $this->assertFalse($first['completed']);
        $this->assertSame('file_limit', $first['stop_reason']);
        $this->assertSame('a.log', IndexRun::query()->oldest('id')->firstOrFail()->end_cursor);
        $this->assertSame('b.log', IndexRun::query()->latest('id')->firstOrFail()->end_cursor);
        $this->assertSame(2, LogFile::query()->count());
        $this->assertFalse($second['completed']);
    }

    public function test_line_limit_is_recorded_as_partial_and_cursor_is_preserved(): void
    {
        $lines = [];

        for ($index = 0; $index < 101; $index++) {
            $lines[] = sprintf('[2026-08-27 10:00:%02d] production.ERROR: Failure %d', $index % 60, $index);
        }

        File::put($this->logDirectory.'/laravel.log', implode("\n", $lines)."\n");
        app(SettingStore::class)->put('indexing', 'max_lines_per_file', 100);

        $stats = app(LogIndexer::class)->run();

        $this->assertFalse($stats['completed']);
        $this->assertSame('line_limit', $stats['stop_reason']);
        $this->assertSame(1, $stats['partially_processed_files']);
        $this->assertGreaterThan(0, LogFile::query()->firstOrFail()->last_offset);
        $this->assertLessThan(filesize($this->logDirectory.'/laravel.log'), LogFile::query()->firstOrFail()->last_offset);
    }

    public function test_unvisited_discovered_files_are_not_marked_missing(): void
    {
        foreach (['a.log', 'b.log'] as $filename) {
            $path = $this->logDirectory.'/'.$filename;
            File::put($path, "[2026-08-27 10:00:00] production.ERROR: Failure\n");
            LogFile::query()->create(['path' => $path, 'relative_path' => $filename, 'filename' => $filename]);
        }

        app(SettingStore::class)->put('indexing', 'max_files_per_run', 1);
        app(LogIndexer::class)->run();

        $this->assertFalse(LogFile::query()->where('filename', 'b.log')->firstOrFail()->is_missing);
    }

    public function test_priority_files_are_processed_first_on_every_run_without_resetting_the_regular_cursor(): void
    {
        foreach (['a.log', 'b.log', 'laravel.log'] as $filename) {
            File::put(
                $this->logDirectory.'/'.$filename,
                "[2026-08-27 10:00:00] production.ERROR: {$filename}\n",
            );
        }

        app(SettingStore::class)->put('indexing', 'max_files_per_run', 2);

        app(LogIndexer::class)->run();
        $firstRun = IndexRun::query()->latest('id')->firstOrFail();
        $laravelFile = LogFile::query()->where('filename', 'laravel.log')->firstOrFail();
        $firstLaravelScan = $laravelFile->last_scanned_at;

        $this->assertSame('laravel.log', $firstRun->start_cursor);
        $this->assertSame('a.log', $firstRun->end_cursor);
        $this->assertDatabaseMissing('error_log_monitor_files', ['filename' => 'b.log']);

        $this->travel(1)->second();
        app(LogIndexer::class)->run();
        $secondRun = IndexRun::query()->latest('id')->firstOrFail();

        $this->assertSame('laravel.log', $secondRun->start_cursor);
        $this->assertSame('b.log', $secondRun->end_cursor);
        $this->assertTrue(LogFile::query()->where('filename', 'laravel.log')->firstOrFail()->last_scanned_at->gt($firstLaravelScan));
        $this->assertDatabaseHas('error_log_monitor_files', ['filename' => 'b.log']);
    }
}
