<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Tests\Unit;

use Illuminate\Foundation\Auth\User as Authenticatable;
use InvalidArgumentException;
use Nextus\ErrorLogMonitor\Models\Setting;
use Nextus\ErrorLogMonitor\Models\SettingChange;
use Nextus\ErrorLogMonitor\Services\SettingStore;
use Nextus\ErrorLogMonitor\Tests\TestCase;

class SettingStoreTest extends TestCase
{
    public function test_database_value_overrides_its_config_fallback(): void
    {
        config()->set('error-log-monitor.indexing.max_files_per_run', 50);
        config()->set('error-log-monitor.settings.indexing.max_files_per_run', [
            'type' => 'integer',
            'config' => 'error-log-monitor.indexing.max_files_per_run',
        ]);
        $settings = app(SettingStore::class);

        $this->assertSame(50, $settings->get('indexing', 'max_files_per_run'));

        $settings->put('indexing', 'max_files_per_run', 25);

        $this->assertSame(25, $settings->get('indexing', 'max_files_per_run'));
    }

    public function test_change_is_audited_with_an_actor_snapshot(): void
    {
        $actor = new class extends Authenticatable {};
        $actor->forceFill(['id' => 42, 'name' => 'Test Admin']);

        app(SettingStore::class)->put('general', 'monitoring_enabled', false, $actor);

        $setting = Setting::query()->firstOrFail();
        $change = SettingChange::query()->firstOrFail();

        $this->assertFalse($setting->value);
        $this->assertSame('42', $setting->updated_by_id);
        $this->assertSame('Test Admin', $setting->updated_by_name);
        $this->assertFalse($change->new_value);
        $this->assertSame('Test Admin', $change->changed_by_name);
    }

    public function test_change_can_be_audited_without_a_user(): void
    {
        app(SettingStore::class)->put('general', 'monitoring_enabled', false);

        $change = SettingChange::query()->firstOrFail();

        $this->assertNull($change->changed_by_id);
        $this->assertNull($change->changed_by_name);
    }

    public function test_unknown_setting_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(SettingStore::class)->put('unknown', 'setting', true);
    }

    public function test_invalid_typed_value_is_rejected(): void
    {
        config()->set('error-log-monitor.settings.indexing.limit', [
            'type' => 'integer',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an integer');

        app(SettingStore::class)->put('indexing', 'limit', 'invalid');
    }
}
