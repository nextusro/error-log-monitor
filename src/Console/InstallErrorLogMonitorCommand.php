<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Console;

use Illuminate\Console\Command;
use Nextus\ErrorLogMonitor\ErrorLogMonitorServiceProvider;

class InstallErrorLogMonitorCommand extends Command
{
    protected $signature = 'error-log-monitor:install
        {--migrate : Run the application database migrations}
        {--force : Overwrite the published config and force migrations in production}';

    protected $description = 'Install Error Log Monitor configuration and database tables.';

    public function handle(): int
    {
        $publishArguments = [
            '--provider' => ErrorLogMonitorServiceProvider::class,
            '--tag' => 'error-log-monitor-config',
        ];

        if ((bool) $this->option('force')) {
            $publishArguments['--force'] = true;
        }

        $this->call('vendor:publish', $publishArguments);

        $shouldMigrate = (bool) $this->option('migrate')
            || $this->confirm('Run the database migrations now?', true);

        if ($shouldMigrate) {
            $migrationArguments = [];

            if ((bool) $this->option('force')) {
                $migrationArguments['--force'] = true;
            }

            $exitCode = $this->call('migrate', $migrationArguments);

            if ($exitCode !== self::SUCCESS) {
                return $exitCode;
            }
        } else {
            $this->warn('Database migrations were not run. Run [php artisan migrate] before opening the dashboard.');
        }

        $this->components->info('Error Log Monitor is ready.');

        return self::SUCCESS;
    }
}
