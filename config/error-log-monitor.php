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
    ],

    'logs' => [
        'base_path' => storage_path('logs'),
        'include_files' => ['*.log', '**/*.log', '*.log.1', '**/*.log.1'],
        'exclude_files' => ['*.gz', '**/*.gz'],
    ],

    'indexing' => [
        'max_files_per_run' => 50,
        'max_lines_per_file' => 5000,
        'max_runtime_seconds' => 30,
        'store_occurrences' => true,
        'max_message_length' => 65535,
        'max_context_length' => 65535,
        'max_stack_trace_length' => 262144,
    ],

    'retention' => [
        'occurrences_days' => 30,
        'resolved_issues_days' => 60,
        'ignored_issues_days' => 60,
        'open_issues_days' => null,
    ],

    'notifications' => [
        'enabled' => false,
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
        ],
    ],
];
