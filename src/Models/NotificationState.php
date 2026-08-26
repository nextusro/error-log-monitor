<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationState extends Model
{
    protected $table = 'error_log_monitor_notification_states';

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'last_notified_at' => 'datetime',
        'notification_count' => 'integer',
        'metadata' => 'array',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(LogIssue::class, 'issue_id');
    }
}
