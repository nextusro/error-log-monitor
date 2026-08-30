<?php

declare(strict_types=1);

return [
    /*
     * Hard switch controlled by the application administrator. When disabled,
     * monitoring cannot be enabled from the dashboard.
     */
    'enabled' => env('ERROR_LOG_MONITOR_ENABLED', true),

    'route' => [
        'enabled' => true,
        'prefix' => 'admin/error-log-monitor',
        'name' => 'error-log-monitor.',
        'middleware' => ['web'],
        'authorization_gate' => null,
    ],

    'dashboard' => [
        'default_locale' => 'en',
        'locales' => [
            'en' => 'English',
            'ro' => 'Română',
        ],
        'per_page' => 50,
        'default_interval' => '24h',

        /*
         * Date format used in the dashboard.
         * Examples:
         * 'Y-m-d H:i:s'
         * 'd.m.Y H:i:s'
         */
        'date_format' => 'd.m.Y H:i:s',

        /*
         * Initial visibility of the statistics area.
         * Set to true if you want the statistics area collapsed by default.
         */
        'statistics_collapsed_by_default' => false,

        /*
         * Default dashboard theme.
         * Supported values: light, dark.
         */
        'default_theme' => 'light',

        'levels' => [
            'warning',
            'error',
            'critical',
            'alert',
            'emergency',
        ],

        'intervals' => [
            '1h',
            '24h',
            '7d',
            '14d',
            'all',
        ],
    ],

    'views' => [
        'layout' => null,
    ],

    'features' => [
        'bulk_actions_enabled' => true,
        'deletion_enabled' => false,
    ],

    'logs' => [
        'base_path' => storage_path('logs'),
        'include_files' => ['*.log', '**/*.log', '*.log.1', '**/*.log.1'],
        'exclude_files' => [
            '*.gz',
            '**/*.gz',
            'schedule-*.log',
            '**/schedule-*.log',
        ],
    ],

    'indexing' => [
        'priority_files' => ['laravel.log'],
        'max_files_per_run' => 50,
        'max_lines_per_file' => 5000,
        'max_runtime_seconds' => 30,
        'store_occurrences' => true,
        'max_message_length' => 65535,
        'max_context_length' => 65535,
        'max_stack_trace_length' => 262144,
        'incomplete_notification_enabled' => true,
        'incomplete_notification_mode' => 'stale',
        'stale_after_minutes' => 15,
        'notification_cooldown_minutes' => 60,
        'recovery_notification_enabled' => true,
        'run_history_days' => 30,
    ],

    'retention' => [
        'occurrences_days' => 30,
        'max_occurrences_per_issue' => 100,
        'optimize_tables_after_prune' => true,
        'resolved_issues_days' => 0,
        'ignored_issues_days' => 0,
        'open_issues_days' => null,
    ],

    'notifications' => [
        'enabled' => false,
        'recipients' => [],
        'regressions_enabled' => true,
        'database_size_enabled' => true,
        'database_size_threshold_mb' => 500,
        'levels' => ['critical', 'alert', 'emergency'],
        'cooldown_minutes' => 60,
    ],

    'settings' => [
        'general' => [
            'monitoring_enabled' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ],
        'dashboard' => [
            'locale' => [
                'type' => 'string',
                'config' => 'error-log-monitor.dashboard.default_locale',
                'default' => 'en',
            ],
            'bulk_actions_enabled' => [
                'type' => 'boolean',
                'config' => 'error-log-monitor.features.bulk_actions_enabled',
                'default' => true,
            ],
            'deletion_enabled' => [
                'type' => 'boolean',
                'config' => 'error-log-monitor.features.deletion_enabled',
                'default' => false,
            ],
            'per_page' => ['type' => 'integer', 'config' => 'error-log-monitor.dashboard.per_page', 'default' => 50],
            'default_interval' => ['type' => 'string', 'config' => 'error-log-monitor.dashboard.default_interval', 'default' => '24h'],
            'date_format' => ['type' => 'string', 'config' => 'error-log-monitor.dashboard.date_format', 'default' => 'd.m.Y H:i:s'],
            'statistics_collapsed_by_default' => ['type' => 'boolean', 'config' => 'error-log-monitor.dashboard.statistics_collapsed_by_default', 'default' => false],
            'default_theme' => ['type' => 'string', 'config' => 'error-log-monitor.dashboard.default_theme', 'default' => 'light'],
        ],
        'indexing' => [
            'max_runtime_seconds' => ['type' => 'integer', 'config' => 'error-log-monitor.indexing.max_runtime_seconds', 'default' => 30],
            'max_files_per_run' => ['type' => 'integer', 'config' => 'error-log-monitor.indexing.max_files_per_run', 'default' => 50],
            'max_lines_per_file' => ['type' => 'integer', 'config' => 'error-log-monitor.indexing.max_lines_per_file', 'default' => 5000],
            'incomplete_notification_enabled' => ['type' => 'boolean', 'config' => 'error-log-monitor.indexing.incomplete_notification_enabled', 'default' => true],
            'incomplete_notification_mode' => ['type' => 'string', 'config' => 'error-log-monitor.indexing.incomplete_notification_mode', 'default' => 'stale'],
            'stale_after_minutes' => ['type' => 'integer', 'config' => 'error-log-monitor.indexing.stale_after_minutes', 'default' => 15],
            'notification_cooldown_minutes' => ['type' => 'integer', 'config' => 'error-log-monitor.indexing.notification_cooldown_minutes', 'default' => 60],
            'recovery_notification_enabled' => ['type' => 'boolean', 'config' => 'error-log-monitor.indexing.recovery_notification_enabled', 'default' => true],
            'run_history_days' => ['type' => 'integer', 'config' => 'error-log-monitor.indexing.run_history_days', 'default' => 30],
        ],
        'retention' => [
            'occurrences_days' => ['type' => 'integer', 'config' => 'error-log-monitor.retention.occurrences_days', 'default' => 30],
            'max_occurrences_per_issue' => ['type' => 'integer', 'config' => 'error-log-monitor.retention.max_occurrences_per_issue', 'default' => 100],
            'optimize_tables_after_prune' => ['type' => 'boolean', 'config' => 'error-log-monitor.retention.optimize_tables_after_prune', 'default' => true],
            'resolved_issues_days' => ['type' => 'integer', 'config' => 'error-log-monitor.retention.resolved_issues_days', 'default' => 0],
            'ignored_issues_days' => ['type' => 'integer', 'config' => 'error-log-monitor.retention.ignored_issues_days', 'default' => 0],
            'open_issues_days' => ['type' => 'integer', 'config' => 'error-log-monitor.retention.open_issues_days', 'default' => 0],
        ],
        'notifications' => [
            'enabled' => [
                'type' => 'boolean',
                'config' => 'error-log-monitor.notifications.enabled',
                'default' => false,
            ],
            'recipients' => [
                'type' => 'array',
                'config' => 'error-log-monitor.notifications.recipients',
                'default' => [],
            ],
            'regressions_enabled' => [
                'type' => 'boolean',
                'config' => 'error-log-monitor.notifications.regressions_enabled',
                'default' => true,
            ],
            'database_size_enabled' => [
                'type' => 'boolean',
                'config' => 'error-log-monitor.notifications.database_size_enabled',
                'default' => true,
            ],
            'database_size_threshold_mb' => [
                'type' => 'integer',
                'config' => 'error-log-monitor.notifications.database_size_threshold_mb',
                'default' => 500,
            ],
            'levels' => [
                'type' => 'array',
                'config' => 'error-log-monitor.notifications.levels',
                'default' => ['critical', 'alert', 'emergency'],
            ],
            'cooldown_minutes' => [
                'type' => 'integer',
                'config' => 'error-log-monitor.notifications.cooldown_minutes',
                'default' => 60,
            ],
        ],
    ],
];
