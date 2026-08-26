<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Symfony\Component\HttpFoundation\Response;

class SetDashboardLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $originalLocale = app()->getLocale();
        $supportedLocales = array_keys(config('error-log-monitor.dashboard.locales', []));
        $defaultLocale = (string) config('error-log-monitor.dashboard.default_locale', 'en');
        $locale = app(SettingStore::class)->get('dashboard', 'locale');

        app()->setLocale(is_string($locale) && in_array($locale, $supportedLocales, true)
            ? $locale
            : $defaultLocale);

        try {
            return $next($request);
        } finally {
            app()->setLocale($originalLocale);
        }
    }
}
