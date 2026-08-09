<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogOccurrence extends Model
{
    protected $table = 'error_log_monitor_occurrences';

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(LogIssue::class, 'issue_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(LogFile::class, 'file_id');
    }
}
