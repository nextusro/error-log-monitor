<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setting extends Model
{
    protected $table = 'error_log_monitor_settings';

    protected $guarded = [];

    protected $casts = [
        'value' => 'json',
    ];

    public function changes(): HasMany
    {
        return $this->hasMany(SettingChange::class, 'setting_id');
    }
}
