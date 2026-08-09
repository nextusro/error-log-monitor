<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogIssue extends Model
{
    protected $table = 'error_log_monitor_issues';

    protected $guarded = [];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'occurrences_count' => 'integer',
        'resolved_at' => 'datetime',
        'ignored_at' => 'datetime',
        'last_notified_at' => 'datetime',
        'notification_count' => 'integer',
    ];

    protected $appends = [
        'status',
        'is_regression',
    ];

    public function occurrences(): HasMany
    {
        return $this->hasMany(LogOccurrence::class, 'issue_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->whereNull('resolved_at')
            ->whereNull('ignored_at');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeIgnored(Builder $query): Builder
    {
        return $query->whereNotNull('ignored_at');
    }

    public function getStatusAttribute(): string
    {
        if ($this->ignored_at !== null) {
            return 'ignored';
        }

        if ($this->resolved_at !== null) {
            return 'resolved';
        }

        return 'open';
    }

    public function getIsRegressionAttribute(): bool
    {
        if ($this->resolved_at === null || $this->last_seen_at === null) {
            return false;
        }

        return $this->last_seen_at->greaterThan($this->resolved_at);
    }
}
