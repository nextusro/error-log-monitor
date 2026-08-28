<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Models;

use Illuminate\Database\Eloquent\Model;

class GroupingState extends Model
{
    protected $table = 'error_log_monitor_grouping_states';

    protected $guarded = [];

    public $incrementing = false;

    protected $casts = [
        'rules_version' => 'integer',
        'grouped_rules_version' => 'integer',
        'regroup_requested_at' => 'datetime',
        'last_regrouped_at' => 'datetime',
    ];
}
