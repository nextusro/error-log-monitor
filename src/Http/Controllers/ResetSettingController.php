<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Nextus\ErrorLogMonitor\Services\SettingStore;

class ResetSettingController extends Controller
{
    public function __invoke(Request $request, SettingStore $settings): RedirectResponse
    {
        $validated = $request->validate([
            'group' => ['required', Rule::in(['dashboard', 'indexing', 'retention'])],
            'key' => ['required_without:keys', 'string'],
            'keys' => ['required_without:key', 'array'],
            'keys.*' => ['string'],
        ]);

        $definitions = config("error-log-monitor.settings.{$validated['group']}", []);
        $requestedKeys = isset($validated['keys']) ? $validated['keys'] : [$validated['key']];
        $keys = $requestedKeys === ['*'] ? array_keys($definitions) : $requestedKeys;
        abort_unless(collect($keys)->every(static fn (string $key): bool => array_key_exists($key, $definitions)), 404);

        foreach ($keys as $key) {
            $settings->forget($validated['group'], (string) $key, $request->user());
        }

        return back()->with('error-log-monitor.success', trans('error-log-monitor::messages.settings.reset_saved'));
    }
}
