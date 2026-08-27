<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Models;

use Illuminate\Database\Eloquent\Model;

class IndexRun extends Model
{
    protected $table = 'error_log_monitor_index_runs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'errors' => 'array',
        ];
    }
}
