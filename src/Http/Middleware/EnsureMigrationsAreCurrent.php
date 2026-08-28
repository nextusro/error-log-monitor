<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nextus\ErrorLogMonitor\Services\MigrationStatus;
use Symfony\Component\HttpFoundation\Response;

class EnsureMigrationsAreCurrent
{
    public function __construct(private readonly MigrationStatus $migrationStatus) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->migrationStatus->isCurrent()) {
            return $next($request);
        }

        return response()->view('error-log-monitor::migrations-required', [
            'missingRequirements' => $this->migrationStatus->missingRequirements(),
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
