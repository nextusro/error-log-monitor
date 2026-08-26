<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Nextus\ErrorLogMonitor\Models\Setting;
use Nextus\ErrorLogMonitor\Models\SettingChange;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class MonitoringSettingsControllerTest extends TestCase
{
    public function test_monitoring_can_be_suspended_without_an_authenticated_user(): void
    {
        $response = $this->put(route('error-log-monitor.settings.monitoring.update'), [
            'enabled' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error-log-monitor.success');
        $this->assertFalse(Setting::query()->firstOrFail()->value);
        $this->assertNull(SettingChange::query()->firstOrFail()->changed_by_id);
    }

    public function test_resume_mode_is_required_when_monitoring_is_enabled(): void
    {
        $response = $this->from(route('error-log-monitor.dashboard'))->put(
            route('error-log-monitor.settings.monitoring.update'),
            ['enabled' => true],
        );

        $response->assertRedirect(route('error-log-monitor.dashboard'));
        $response->assertSessionHasErrors('resume_mode');
    }

    public function test_dashboard_renders_the_grouped_settings_interface(): void
    {
        $response = $this->get(route('error-log-monitor.dashboard'));

        $response->assertOk();
        $response->assertSee('data-settings-dialog', false);
        $response->assertSee('Monitorizare loguri');
        $response->assertSee('data-bulk-actions-form', false);
        $response->assertSee('class="bulk-actions-toolbar"', false);
        $response->assertSee('hidden', false);
        $response->assertSeeInOrder(['Ignoră selectate', 'Rezolvă selectate']);
        $this->assertSame(2, substr_count($response->getContent(), '<span data-bulk-selected-count>'));
    }

    public function test_settings_blade_templates_compile_independently(): void
    {
        $dashboard = File::get(__DIR__.'/../../resources/views/dashboard.blade.php');
        $content = File::get(__DIR__.'/../../resources/views/partials/dashboard-content.blade.php');

        $this->assertNotEmpty(Blade::compileString($dashboard));
        $this->assertNotEmpty(Blade::compileString($content));
    }

    public function test_bulk_actions_setting_can_override_the_config_value_without_a_user(): void
    {
        config()->set('error-log-monitor.features.bulk_actions_enabled', false);

        $response = $this->put(route('error-log-monitor.settings.bulk-actions.update'), [
            'enabled' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error-log-monitor.success', 'Acțiunile bulk au fost activate.');

        $setting = Setting::query()
            ->where('group', 'dashboard')
            ->where('key', 'bulk_actions_enabled')
            ->firstOrFail();

        $this->assertTrue($setting->value);
        $this->assertNull($setting->updated_by_id);

        $this->get(route('error-log-monitor.dashboard'))
            ->assertOk()
            ->assertSee('data-bulk-actions-form', false);
    }
}
