# Nextus Error Log Monitor

Nextus Error Log Monitor is a Laravel package for indexing, grouping, reviewing, and managing application log errors.

It is designed for Laravel applications that need more than a simple log viewer. Instead of only reading log files on demand, the package indexes relevant entries into the database, groups repeated errors into issues, and gives each issue a lifecycle.

## Features

- Standalone Laravel dashboard
- Database-backed log indexing
- Error grouping by fingerprint
- Issue statuses: `open`, `resolved`, `ignored`
- Regression detection
- Filtering by level, interval, query, file, directory, and status
- Log files and nested log directories support
- Statistics for the selected interval
- Top recurring issues
- Top log sources
- Estimated database usage for monitor tables
- Light and dark themes
- Configurable default theme
- Configurable date format
- Collapsible statistics area
- Artisan commands for indexing and pruning
- No dependency on Laravel Nova, Laravel Modules, or application-specific code

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, 12.x or 13.x, with compatible Illuminate components declared in `composer.json`
- A database supported by Laravel
- Read access to the configured log directory

## Laravel compatibility

The package is intended to support Laravel 10, 11, 12 and 13, as long as the host application runs on PHP 8.2+ and the installed Illuminate components match the constraints declared in `composer.json`.

Recommended Composer constraints for Laravel 10+ support:

```json
"illuminate/console": "^10.0|^11.0|^12.0|^13.0",
"illuminate/contracts": "^10.0|^11.0|^12.0|^13.0",
"illuminate/database": "^10.0|^11.0|^12.0|^13.0",
"illuminate/filesystem": "^10.0|^11.0|^12.0|^13.0",
"illuminate/http": "^10.0|^11.0|^12.0|^13.0",
"illuminate/pagination": "^10.0|^11.0|^12.0|^13.0",
"illuminate/routing": "^10.0|^11.0|^12.0|^13.0",
"illuminate/support": "^10.0|^11.0|^12.0|^13.0",
"illuminate/view": "^10.0|^11.0|^12.0|^13.0"
```

Before using the package in production with Laravel 10 or 11, test installation, migrations, indexing, pruning and the dashboard in a clean application.

## Installation from GitHub during testing

Until the package is published on Packagist, install it directly from GitHub using a Composer VCS repository.

In the host Laravel application, add:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/nextusro/error-log-monitor.git"
        }
    ],
    "require": {
        "nextus/error-log-monitor": "dev-main"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Then run:

```bash
composer update nextus/error-log-monitor
php artisan optimize:clear
```

For a private repository, make sure your local machine and deployment server have access through SSH keys or GitHub tokens.

## Local development installation

For local package development, use a path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/error-log-monitor",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "nextus/error-log-monitor": "dev-main"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Then run:

```bash
composer update nextus/error-log-monitor
php artisan optimize:clear
```

## Publish the configuration

```bash
php artisan vendor:publish --tag=error-log-monitor-config
```

To overwrite an already published config file:

```bash
php artisan vendor:publish --tag=error-log-monitor-config --force
php artisan optimize:clear
```

Be careful: `--force` overwrites local changes in `config/error-log-monitor.php`.

## Run migrations

```bash
php artisan migrate
```

The package creates tables for:

- scanned log files;
- grouped log issues;
- individual log occurrences.

## Dashboard

The default dashboard URL is:

```text
/admin/error-log-monitor
```

The route can be changed from the config file.

## Configuration

The main configuration file is:

```text
config/error-log-monitor.php
```

### Route configuration

```php
'route' => [
    'enabled' => true,
    'prefix' => 'admin/error-log-monitor',
    'name' => 'error-log-monitor.',
    'middleware' => ['web'],
    'authorization_gate' => null,
],
```

To protect the dashboard with authentication:

```php
'middleware' => ['web', 'auth'],
```

To protect it with a Laravel Gate:

```php
'authorization_gate' => 'viewErrorLogMonitor',
```

Example Gate definition:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewErrorLogMonitor', function ($user) {
    return $user->is_admin;
});
```

### Dashboard configuration

```php
'dashboard' => [
    'per_page' => 50,
    'default_interval' => '24h',
    'date_format' => 'Y-m-d H:i:s',
    'statistics_collapsed_by_default' => false,
    'default_theme' => 'light',

    'levels' => [
        'warning',
        'error',
        'critical',
        'alert',
        'emergency',
    ],

    'intervals' => [
        '1h' => 'Last hour',
        '24h' => '24h',
        '7d' => '7 days',
        '14d' => '14 days',
        'all' => 'All time',
    ],
],
```

Notes:

- `default_theme` accepts `light` or `dark`;
- the user-selected theme is saved in `localStorage`;
- `date_format` controls dashboard date formatting;
- `statistics_collapsed_by_default` controls the initial statistics panel state;
- `default_interval` controls the default selected interval.

### Log configuration

```php
'logs' => [
    'base_path' => storage_path('logs'),
    'include_files' => ['*.log', '**/*.log', '*.log.1', '**/*.log.1'],
    'exclude_files' => ['*.gz', '**/*.gz'],
],
```

Examples of supported paths:

```text
storage/logs/laravel.log
storage/logs/browser.log
storage/logs/imports/orders.log
storage/logs/bank-statements/smart-fintech.log
```

Compressed `.gz` files are excluded by default.

### Indexing configuration

```php
'indexing' => [
    'max_files_per_run' => 50,
    'max_lines_per_file' => 5000,
    'max_runtime_seconds' => 30,
    'store_occurrences' => true,
    'max_message_length' => 65535,
    'max_context_length' => 65535,
    'max_stack_trace_length' => 262144,
],
```

These values limit how much work the indexer performs in one run.

### Retention configuration

```php
'retention' => [
    'occurrences_days' => 30,
    'resolved_issues_days' => 60,
    'ignored_issues_days' => 60,
    'open_issues_days' => null,
],
```

Recommended default behavior:

- prune old occurrences;
- prune old resolved issues;
- prune old ignored issues;
- keep open issues indefinitely.

### Notifications

```php
'notifications' => [
    'enabled' => false,
    'levels' => ['critical', 'alert', 'emergency'],
    'cooldown_minutes' => 60,
],
```

Notifications are reserved for a future version. In the current version, the dashboard and database indexing are the primary features.

## Artisan commands

### Index logs

```bash
php artisan error-log-monitor:index
```

This command scans the configured log files, parses relevant entries, groups them into issues, and stores occurrences.

Recommended scheduler entry:

```php
$schedule->command('error-log-monitor:index')->everyFiveMinutes();
```

For very active applications, start with `everyFiveMinutes()` or `everyTenMinutes()` and adjust based on the volume.

### Prune old data

```bash
php artisan error-log-monitor:prune
```

This command removes old monitor data based on the retention configuration.

Recommended scheduler entry:

```php
$schedule->command('error-log-monitor:prune')->daily();
```

## Suggested scheduler setup

In `routes/console.php` or your application's scheduler location:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('error-log-monitor:index')->everyFiveMinutes();
Schedule::command('error-log-monitor:prune')->daily();
```

## Typical workflow

1. Install the package.
2. Publish the config.
3. Run the migrations.
4. Run the indexer.
5. Open the dashboard.
6. Review issues.
7. Mark issues as resolved, ignored, or reopen them.
8. Monitor statistics and database size.
9. Configure the scheduler.

## GitHub publishing checklist

Before publishing the repository:

- remove `.DS_Store`;
- remove `__MACOSX/`;
- do not commit `vendor/`;
- do not commit `node_modules/`;
- do not commit `.env`;
- do not commit real log files;
- do not commit SQL dumps;
- do not commit temporary ZIP archives;
- add `.gitignore`;
- add `LICENSE`;
- remove `"version": "dev-main"` from `composer.json`;
- run `composer validate`;
- run syntax checks;
- test installation in a clean Laravel application.

Useful commands:

```bash
find . -name ".DS_Store" -delete
rm -rf __MACOSX
composer validate
composer dump-autoload
php -l src/ErrorLogMonitorServiceProvider.php
```

## Testing checklist

In a host Laravel app, test:

```bash
php artisan package:discover
php artisan vendor:publish --tag=error-log-monitor-config
php artisan migrate
php artisan error-log-monitor:index
php artisan error-log-monitor:prune
php artisan route:list | grep error-log-monitor
```

Compatibility checks before claiming support for a Laravel version:

- install in a clean Laravel 10 application;
- install in a clean Laravel 12 application;
- run migrations;
- run the index and prune commands;
- open the dashboard;
- test issue actions and filters.

Manual checks:

- dashboard loads;
- config publishing works;
- migrations run;
- index command works;
- prune command works;
- filters work;
- issue actions work: resolve, ignore, reopen;
- stack trace/context expansion works;
- light/dark switch works;
- selected theme persists;
- custom date format works;
- statistics collapsed/default state works;
- files in subdirectories are indexed;
- excluded files are not indexed.

## Known limitations for the first version

- compressed `.gz` logs are not indexed;
- notifications are not active yet;
- charts are not included;
- advanced ignore rules are not included;
- export is not included;
- log rotation behavior should be tested in production-like environments.

## Roadmap ideas

### v0.2

- parser tests;
- fingerprinting tests;
- dashboard stats tests;
- status/health command;
- full reindex command;
- screenshots in documentation.

### v0.3

- configurable ignore rules;
- critical/alert/emergency notifications;
- spike detection;
- CSV export;
- optional `.gz` support.

### v1.0

- stable configuration contract;
- stable database schema;
- documented upgrade path;
- Packagist release.

## Packagist release

After a few months of testing, create the first tag:

```bash
git tag v0.1.0
git push origin v0.1.0
```

Then submit the repository to Packagist.

Recommended approach:

- use `dev-main` during testing;
- use `v0.x` tags before the API/config is stable;
- use `v1.0.0` only when the package is stable.

## Troubleshooting

### The dashboard route does not appear

```bash
php artisan optimize:clear
php artisan route:list | grep error-log-monitor
```

### The config does not update

```bash
php artisan vendor:publish --tag=error-log-monitor-config --force
php artisan optimize:clear
```

### The dashboard shows no issues

Check:

- the index command was executed;
- `logs.base_path` is correct;
- `include_files` matches your files;
- `exclude_files` does not exclude your files;
- the selected interval is not too restrictive;
- the log level is included in `dashboard.levels`.

### The monitor tables grow too much

Check the Statistics area in the dashboard, then adjust retention:

```php
'retention' => [
    'occurrences_days' => 14,
    'resolved_issues_days' => 30,
    'ignored_issues_days' => 30,
    'open_issues_days' => null,
],
```

Then run:

```bash
php artisan error-log-monitor:prune
```

## License

This package is open-sourced software licensed under the MIT license.
