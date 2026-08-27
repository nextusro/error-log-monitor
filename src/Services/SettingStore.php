<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Nextus\ErrorLogMonitor\Models\Setting;
use Nextus\ErrorLogMonitor\Models\SettingChange;

class SettingStore
{
    private ?bool $settingsTableExists = null;

    public function get(string $group, string $key): mixed
    {
        $definition = $this->definition($group, $key);

        if ($this->settingsTableExists()) {
            $setting = Setting::query()
                ->where('group', $group)
                ->where('key', $key)
                ->first();

            if ($setting !== null) {
                return $setting->value;
            }
        }

        if (isset($definition['config'])) {
            return config((string) $definition['config'], $definition['default'] ?? null);
        }

        return $definition['default'] ?? null;
    }

    private function settingsTableExists(): bool
    {
        return $this->settingsTableExists ??= Schema::hasTable('error_log_monitor_settings');
    }

    public function put(string $group, string $key, mixed $value, ?Authenticatable $actor = null): Setting
    {
        $definition = $this->definition($group, $key);
        $normalizedValue = $this->normalize($value, (string) $definition['type']);
        $actorDetails = $this->actorDetails($actor);

        return DB::transaction(function () use ($group, $key, $normalizedValue, $definition, $actorDetails): Setting {
            $setting = Setting::query()
                ->where('group', $group)
                ->where('key', $key)
                ->lockForUpdate()
                ->first();
            $oldValue = $setting?->value;

            if ($setting === null) {
                $setting = new Setting([
                    'group' => $group,
                    'key' => $key,
                ]);
            }

            $setting->fill([
                'value' => $normalizedValue,
                'type' => $definition['type'],
                'updated_by_id' => $actorDetails['id'],
                'updated_by_name' => $actorDetails['name'],
            ]);
            $setting->save();

            SettingChange::query()->create([
                'setting_id' => $setting->id,
                'group' => $group,
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $normalizedValue,
                'changed_by_id' => $actorDetails['id'],
                'changed_by_name' => $actorDetails['name'],
            ]);

            return $setting;
        });
    }

    public function forget(string $group, string $key, ?Authenticatable $actor = null): void
    {
        $this->definition($group, $key);
        $actorDetails = $this->actorDetails($actor);

        DB::transaction(function () use ($group, $key, $actorDetails): void {
            $setting = Setting::query()->where('group', $group)->where('key', $key)->lockForUpdate()->first();

            if ($setting === null) {
                return;
            }

            SettingChange::query()->create([
                'setting_id' => null,
                'group' => $group,
                'key' => $key,
                'old_value' => $setting->value,
                'new_value' => $this->configuredValue($group, $key),
                'changed_by_id' => $actorDetails['id'],
                'changed_by_name' => $actorDetails['name'],
            ]);

            $setting->delete();
        });
    }

    public function hasOverride(string $group, string $key): bool
    {
        return $this->settingsTableExists() && Setting::query()->where('group', $group)->where('key', $key)->exists();
    }

    public function configuredValue(string $group, string $key): mixed
    {
        $definition = $this->definition($group, $key);

        return isset($definition['config'])
            ? config((string) $definition['config'], $definition['default'] ?? null)
            : ($definition['default'] ?? null);
    }

    /**
     * @return array{type:string,default?:mixed,config?:string}
     */
    private function definition(string $group, string $key): array
    {
        $definition = config("error-log-monitor.settings.{$group}.{$key}");

        if (! is_array($definition) || ! isset($definition['type'])) {
            throw new InvalidArgumentException("Unknown Error Log Monitor setting [{$group}.{$key}].");
        }

        return $definition;
    }

    private function normalize(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                ?? throw new InvalidArgumentException('The setting value must be boolean.'),
            'integer' => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE)
                ?? throw new InvalidArgumentException('The setting value must be an integer.'),
            'string' => is_string($value)
                ? $value
                : throw new InvalidArgumentException('The setting value must be a string.'),
            'array' => is_array($value)
                ? $value
                : throw new InvalidArgumentException('The setting value must be an array.'),
            default => throw new InvalidArgumentException("Unsupported setting type [{$type}]."),
        };
    }

    /**
     * @return array{id:?string,name:?string}
     */
    private function actorDetails(?Authenticatable $actor): array
    {
        if ($actor === null) {
            return ['id' => null, 'name' => null];
        }

        $name = data_get($actor, 'name') ?? data_get($actor, 'email');

        return [
            'id' => (string) $actor->getAuthIdentifier(),
            'name' => is_scalar($name) ? (string) $name : null,
        ];
    }
}
