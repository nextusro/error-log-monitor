<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Nextus\ErrorLogMonitor\Models\Setting;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class LocaleSettingsControllerTest extends TestCase
{
    public function test_english_is_the_default_dashboard_locale(): void
    {
        $this->get(route('error-log-monitor.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('Dashboard for application errors')
            ->assertSee('Save language');
    }

    public function test_dashboard_locale_can_be_changed_to_romanian(): void
    {
        $response = $this->put(route('error-log-monitor.settings.locale.update'), [
            'locale' => 'ro',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error-log-monitor.success', 'Limba dashboardului a fost actualizată.');

        $setting = Setting::query()
            ->where('group', 'dashboard')
            ->where('key', 'locale')
            ->firstOrFail();

        $this->assertSame('ro', $setting->value);

        $this->get(route('error-log-monitor.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ro"', false)
            ->assertSee('Dashboard pentru erori')
            ->assertSee('Salvează limba');
    }

    public function test_unsupported_dashboard_locale_is_rejected(): void
    {
        $this->from(route('error-log-monitor.dashboard'))
            ->put(route('error-log-monitor.settings.locale.update'), ['locale' => 'de'])
            ->assertRedirect(route('error-log-monitor.dashboard'))
            ->assertSessionHasErrors('locale');

        $this->assertDatabaseMissing('error_log_monitor_settings', [
            'group' => 'dashboard',
            'key' => 'locale',
        ]);
    }

    public function test_dashboard_locale_does_not_leak_after_the_request(): void
    {
        Setting::query()->create([
            'group' => 'dashboard',
            'key' => 'locale',
            'value' => 'ro',
            'type' => 'string',
        ]);

        app()->setLocale('en');

        $this->get(route('error-log-monitor.dashboard'))->assertOk();

        $this->assertSame('en', app()->getLocale());
    }
}
