<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogFile extends Model
{
    protected $table = 'error_log_monitor_files';

    protected $guarded = [];

    protected $casts = [
        'size' => 'integer',
        'last_offset' => 'integer',
        'inode' => 'integer',
        'last_modified_at' => 'datetime',
        'last_scanned_at' => 'datetime',
        'is_missing' => 'boolean',
        'missing_since' => 'datetime',
        'was_truncated_at' => 'datetime',
    ];

    public function occurrences(): HasMany
    {
        return $this->hasMany(LogOccurrence::class, 'file_id');
    }
}
