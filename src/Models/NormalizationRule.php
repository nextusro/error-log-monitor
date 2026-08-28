<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Models;

use Illuminate\Database\Eloquent\Model;

class NormalizationRule extends Model
{
    protected $table = 'error_log_monitor_normalization_rules';

    protected $guarded = [];

    protected $casts = [
        'priority' => 'integer',
        'enabled' => 'boolean',
    ];
}
