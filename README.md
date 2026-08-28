# Nextus Error Log Monitor

Nextus Error Log Monitor is a Laravel package for indexing, grouping, reviewing, and managing application log errors.

It is designed for Laravel applications that need more than a simple log viewer. Instead of only reading log files on demand, the package indexes relevant entries into the database, groups repeated errors into issues, and gives each issue a lifecycle.

## Features

- Standalone Laravel dashboard
- Database-backed log indexing
- Error grouping by fingerprint
- Configurable grouping rules using readable message templates or regular expressions
- Rule suggestions generated from two or more selected issues
- Deferred, memory-bounded regrouping before the next indexing run
- Issue statuses: `open`, `resolved`, `ignored`
- Regression detection, statistics, and filtering
- Filtering by level, interval, query, file, directory, and status
- Configurable bulk resolve and ignore actions
- Runtime monitoring pause/resume controls
- Resume from the previous cursor or skip directly to new log entries
- English and Romanian dashboard translations
- Configurable dashboard language
- Email notifications for new issues, regressions, database size, incomplete indexing, and recovery
- Priority log files processed first on every run
- Fair circular continuation for non-priority files between runs
- Indexing run history, backlog detection, and health statistics
- Runtime overrides for indexing, retention, notifications, and dashboard preferences
- Config defaults with audited dashboard overrides
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
"illuminate/notifications": "^10.0|^11.0|^12.0|^13.0",
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
- individual log occurrences;
- dashboard setting overrides;
- setting change history;
- notification delivery state and deduplication;
- normalization rules and grouping-version state.

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

### Global monitoring switch

```php
'enabled' => env('ERROR_LOG_MONITOR_ENABLED', true),
```

This is the hard application-level switch. When it is `false`, monitoring cannot be enabled from the dashboard.

When the hard switch allows monitoring, an authorized dashboard user can suspend or resume indexing. On resume, the user can either:

- continue from the previously stored log cursors and catch up with available entries;
- move all cursors to the current end of each log file and monitor only future entries.

Pausing indexing does not remove existing issues and does not disable the dashboard.

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
    'default_locale' => 'en',
    'locales' => [
        'en' => 'English',
        'ro' => 'Română',
    ],
    'per_page' => 50,
    'default_interval' => '24h',
    'date_format' => 'd.m.Y H:i:s',
    'statistics_collapsed_by_default' => false,
    'default_theme' => 'light',

    'levels' => [
        'warning',
        'error',
        'critical',
        'alert',
        'emergency',
    ],

    'intervals' => ['1h', '24h', '7d', '14d', 'all'],
],
```

Notes:

- `default_theme` accepts `light` or `dark`;
- the user-selected theme is saved in `localStorage`;
- `date_format` controls dashboard date formatting;
- `statistics_collapsed_by_default` controls the initial statistics panel state;
- `default_interval` controls the default selected interval.
- `default_locale` is used until a language is selected in the dashboard;
- `locales` defines the languages available in the settings interface;
- dashboard language overrides are stored in the package settings table;
- notification language is independent of the dashboard selection and uses the host application's `app.locale` when the notification is sent.

The package ships with English and Romanian translations. Published translations can be customized with:

```bash
php artisan vendor:publish --tag=error-log-monitor-translations
```

### Feature configuration

```php
'features' => [
    'bulk_actions_enabled' => true,
],
```

Bulk actions allow up to 500 selected open issues to be resolved or ignored in one request. They can be disabled by default in config and overridden from the dashboard settings.

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
```

These values limit how much work the indexer performs in one run. `max_runtime_seconds`, `max_files_per_run`, `max_lines_per_file`, the indexing health notification options, and run history retention can be overridden from the Indexing tab in the dashboard.

`priority_files` is intentionally configurable only in the published config. Priority files are processed first on every run, while the remaining files continue from a separate circular cursor. Each file also keeps its own byte offset, so existing content is not re-indexed.

Priority entries are relative to `logs.base_path` and support `fnmatch` patterns:

```php
'priority_files' => [
    'laravel.log',
    'jobs/*.log',
    'critical.log',
],
```

If a run reaches a configured limit, its status is stored as `partial`. The next scheduled run scans the priority files again and then resumes the normal queue after the last non-priority file processed.

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

Use `0` for unlimited retention when saving values from the Retention tab. The prune command also removes indexing run history older than `indexing.run_history_days`.

### Notifications

```php
'notifications' => [
    'enabled' => false,
    'recipients' => [
        'alerts@example.com',
    ],
    'regressions_enabled' => true,
    'database_size_enabled' => true,
    'database_size_threshold_mb' => 500,
    'levels' => ['critical', 'alert', 'emergency'],
    'cooldown_minutes' => 60,
],
```

The values above are defaults. The Notifications tab in the dashboard can override:

- whether notifications are enabled;
- one or more email recipients;
- regression notifications;
- database size notifications and their threshold in MB;
- the levels that trigger notifications for newly discovered issues.
- the general notification cooldown.

Recipient addresses can be separated by commas, spaces, semicolons, or new lines. All addresses are validated and duplicates are removed before saving.

Notification behavior:

- a selected log level sends one notification when a new grouped issue is created, not for every occurrence;
- a resolved issue sends one regression notification when it occurs again;
- resolving the same issue again starts a new regression cycle;
- the database alert measures the package tables and indexes, not the entire host database;
- while the database remains above the threshold, reminders respect `cooldown_minutes`;
- after usage drops below the threshold, the alert is rearmed for the next threshold crossing.
- incomplete indexing can notify immediately or only after the backlog has remained unresolved for the configured number of minutes;
- repeated indexing health notifications respect their own cooldown;
- an optional recovery notification is sent after indexing completes all available work again.

Notifications use Laravel's configured mailer and are sent synchronously. A queue worker is not required. Configure mail in the host application before enabling notifications.

The notification language is the host application's default locale at send time:

```php
config('app.locale')
```

Custom notification templates are not currently configurable.

### Dashboard setting overrides

The dashboard stores runtime overrides in `error_log_monitor_settings`. If no override exists, the corresponding config value is used.

The settings dialog exposes:

- **General:** monitoring, bulk actions, locale, pagination, default interval, date format, default theme, and the initial statistics state;
- **Indexing:** runtime, file and line limits, backlog notification mode and thresholds, recovery notification, and run history retention;
- **Notifications:** recipients, issue levels, regressions, database size threshold, and general cooldown;
- **Retention:** occurrences and open, resolved, or ignored issue retention.
- **Grouping rules:** message templates and regular expressions used to normalize dynamic values before fingerprinting.

Indexing, retention, and dashboard preference overrides can be reset to their published config values from the same dialog. `logs.base_path`, include/exclude patterns, priority files, routes, middleware, authorization, and storage length limits remain config-only settings.

Changes made through the dashboard are recorded in `error_log_monitor_setting_changes`, including the authenticated actor when one is available. Protect settings routes with authentication and/or `route.authorization_gate` in production.

### Custom grouping rules

The default fingerprint contains the log level, exception class, normalized message, and top stack frame. Built-in normalization replaces dates, standalone numbers, and hexadecimal values. The **Grouping rules** tab can normalize additional application-specific identifiers.

For common cases, use a message template instead of a regular expression:

```text
[id:{number}][act_{number}] no active token found
```

Supported template placeholders are:

- `{number}` for one or more digits;
- `{uuid}` for a standard UUID;
- `{hex}` for hexadecimal values, optionally prefixed by `0x`;
- `{value}` for any non-empty value.

The template above groups messages such as:

```text
[id:142][act_991] no active token found
[id:875][act_12004] no active token found
```

The replacement field is ignored for message-template rules. For advanced cases, choose **Regular expression** and provide both a PHP-compatible pattern and its replacement:

```text
Pattern:     /\[code:[A-Z0-9]+\]/i
Replacement: [code:{code}]
```

Multiple rules can be active at the same time. They run by ascending priority and then by creation order. Existing rules are applied before the dashboard suggests another rule, so selecting two or more issues can identify the next uncovered variable segment. Generated patterns are always presented for review and editing before they are saved.

Creating, updating, enabling, disabling, reprioritizing, or deleting a rule does not regroup issues during the HTTP request. It increments the grouping-rules version and marks regrouping as pending. The next `error-log-monitor:index` run performs regrouping before reading log files.

Rebuilding uses stored occurrence messages as its source and processes issues and occurrences in bounded chunks. This avoids loading the complete monitor database into PHP memory. Data already removed by retention cannot be reconstructed; historical counts without stored occurrences are assigned using the issue's last stored message. Keep `indexing.store_occurrences` enabled when reversible regrouping is important.

## Artisan commands

### Index logs

```bash
php artisan error-log-monitor:index
```

This command scans the configured log files, parses relevant entries, groups them into issues, and stores occurrences.

Before scanning files, the command compares the current grouping-rules version with the last grouped version. In the usual case they match and this is a single inexpensive database lookup. When regrouping is pending, the command rebuilds issue groups first, prints the before/after group counts, and then starts the normal indexing runtime budget. A failed regroup remains pending and log files are not processed with partially rebuilt groups.

Every normal run stores its duration, status, stop reason, processed and pending file counts, partial files, failed files, parsed entries, indexed issues, and start/end cursors. Manual runs using `--file` do not advance the global circular cursor.

Available options:

```bash
php artisan error-log-monitor:index --file=laravel.log
php artisan error-log-monitor:index --file=laravel.log --fresh
```

`--fresh` resets reading to the beginning for files processed by that invocation. Without it, each file resumes from its saved byte offset.

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
7. Mark issues as resolved, ignored, or reopen them, individually or in bulk.
8. Filter regressions and monitor statistics and database size.
9. Configure monitoring, indexing, retention, grouping rules, language, bulk actions, dashboard preferences, and notifications from the settings dialog.
10. Configure the scheduler.

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
- test monitoring pause and both resume modes;
- test bulk resolve and ignore actions;
- test English and Romanian dashboard languages;
- test regression filtering;
- test notification delivery with the host application's mailer.
- test priority files and circular continuation;
- test incomplete indexing and recovery notifications;
- test indexing health statistics and setting resets.
- create multiple template and regex grouping rules;
- generate a grouping-rule proposal from selected issues;
- verify that a rule change is marked pending and applied by the next index run;
- test regrouping with a production-like occurrence volume.

Manual checks:

- dashboard loads;
- config publishing works;
- migrations run;
- index command works;
- prune command works;
- filters work;
- issue actions work: resolve, ignore, reopen;
- bulk resolve and ignore actions work;
- monitoring can be suspended and resumed in both modes;
- dashboard language can be changed;
- regression cards and filters show the same issue set;
- notification recipients and triggering levels can be saved;
- new issue, regression, and database threshold emails are delivered;
- stack trace/context expansion works;
- light/dark switch works;
- selected theme persists;
- custom date format works;
- statistics collapsed/default state works;
- files in subdirectories are indexed;
- excluded files are not indexed.
- priority files are indexed first on consecutive runs;
- the non-priority cursor advances between partial runs;
- indexing health shows the latest run, backlog, duration, and stop reason;
- indexing and retention overrides can be saved and reset.
- template and regex grouping rules can be created, edited, disabled, and deleted;
- pending regrouping runs before new log files are processed.

## Known limitations for the first version

- compressed `.gz` logs are not indexed;
- notification templates are not configurable;
- notification delivery is synchronous;
- charts are not included;
- advanced ignore rules are not included;
- export is not included;
- log rotation behavior should be tested in production-like environments.

## Roadmap ideas

### v0.2

- parser tests;
- dashboard stats tests;
- status/health command;
- full reindex command;
- screenshots in documentation.

### v0.3

- configurable ignore rules;
- spike detection;
- optional notification channels;
- configurable notification templates;
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

### Indexing remains partial

Check the **Indexing health** area for the stop reason and backlog. Common reasons are:

- `runtime_limit`: increase `max_runtime_seconds` from the Indexing settings tab;
- `file_limit`: increase `max_files_per_run`;
- `line_limit`: the file has more unread lines than `max_lines_per_file` allows in one run;
- `read_error`: inspect the stored error for the affected file.

Partial runs are expected when processing a backlog. Priority files are scanned first on every run, and the regular file queue continues circularly from its last cursor.

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

### Notification emails are not sent

Check:

- notifications are enabled in the dashboard or config;
- at least one valid recipient is configured;
- the issue level is selected, or the relevant regression/database option is enabled;
- the host application's Laravel mailer is configured and can send mail;
- the database size threshold is expressed in MB;
- the notification is not currently deduplicated or inside its cooldown period.

No queue worker is required because package notifications are sent synchronously.

## License

This package is open-sourced software licensed under the MIT license.
