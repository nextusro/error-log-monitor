<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingChange extends Model
{
    protected $table = 'error_log_monitor_setting_changes';

    protected $guarded = [];

    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'setting_id');
    }
}
