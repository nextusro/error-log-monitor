@php
    $dateFormat = $dateFormat ?? config('error-log-monitor.dashboard.date_format', 'Y-m-d H:i:s');
    $statistics = $statistics ?? null;
    $statisticsCards = $statistics['cards'] ?? [];
    $topIssues = $statistics['top_issues'] ?? [];
    $topSources = $statistics['top_sources'] ?? [];
    $databaseStats = $statistics['database'] ?? [];
    $statisticsCollapsed = (bool) config('error-log-monitor.dashboard.statistics_collapsed_by_default', false);
@endphp
<div class="header header-row">
    <div>
        <h1>Error Log Monitor</h1>
        <p>{{ __('error-log-monitor::messages.dashboard.description') }}</p>
    </div>

    <div class="header-actions">
        <button type="button" class="settings-button" data-settings-open title="{{ __('error-log-monitor::messages.dashboard.settings') }}" aria-label="{{ __('error-log-monitor::messages.dashboard.open_settings') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.12.37.34.7.65.94.3.24.68.38 1.07.4H21v4h-.09a1.7 1.7 0 0 0-1.51.66Z"></path>
            </svg>
        </button>

        <div class="theme-switcher" role="group" aria-label="{{ __('error-log-monitor::messages.dashboard.change_theme') }}">
            <button type="button" class="theme-option" data-theme-value="light">Light</button>
            <button type="button" class="theme-option" data-theme-value="dark">Dark</button>
        </div>
    </div>
</div>

@if(session('error-log-monitor.success'))
    <div class="card" style="margin-bottom: 18px; border-color: #bfead8; background: #f0fdf4;">
        <strong style="color: #0f9f6e;">
            {{ session('error-log-monitor.success') }}
        </strong>
    </div>
@endif

@if($errors->any())
    <div class="monitoring-banner monitoring-banner-danger">
        <div>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif

@if(!($monitoring['enabled'] ?? true))
    <div class="monitoring-banner monitoring-banner-warning">
        <div>
            <strong>{{ __('error-log-monitor::messages.monitoring.paused_title') }}</strong>
            {{ __('error-log-monitor::messages.monitoring.paused_description') }}

            @if(($monitoring['setting'] ?? null)?->updated_at)
                <span class="monitoring-meta">
                    Din {{ $monitoring['setting']->updated_at->format($dateFormat) }}
                    @if($monitoring['setting']->updated_by_name)
                        de {{ $monitoring['setting']->updated_by_name }}
                    @endif
                </span>
            @endif

        </div>

        @if($monitoring['allowed_by_configuration'] ?? false)
            <button type="button" class="btn btn-primary" data-settings-open>{{ __('error-log-monitor::messages.monitoring.reactivate') }}</button>
        @else
            <span class="configuration-lock">{{ __('error-log-monitor::messages.monitoring.disabled_by_config') }}</span>
        @endif
    </div>
@endif

<dialog class="settings-dialog" data-settings-dialog>
    <div class="settings-dialog-header">
        <div>
            <h2>{{ __('error-log-monitor::messages.settings.title') }}</h2>
            <p>{{ __('error-log-monitor::messages.settings.description') }}</p>
        </div>
        <button type="button" class="dialog-close" data-settings-close aria-label="{{ __('error-log-monitor::messages.settings.close') }}">&times;</button>
    </div>

    <div class="settings-layout">
        <div class="settings-tabs" role="tablist" aria-label="{{ __('error-log-monitor::messages.settings.groups') }}">
            <button type="button" class="settings-tab is-active" role="tab" aria-selected="true" data-settings-tab="general">{{ __('error-log-monitor::messages.settings.general') }}</button>
            <button type="button" class="settings-tab" role="tab" aria-selected="false" data-settings-tab="indexing">{{ __('error-log-monitor::messages.settings.indexing') }}</button>
            <button type="button" class="settings-tab" role="tab" aria-selected="false" data-settings-tab="notifications">{{ __('error-log-monitor::messages.settings.notifications') }}</button>
            <button type="button" class="settings-tab" role="tab" aria-selected="false" data-settings-tab="retention">{{ __('error-log-monitor::messages.settings.retention') }}</button>
            <button type="button" class="settings-tab" role="tab" aria-selected="false" data-settings-tab="normalization">{{ __('error-log-monitor::messages.normalization.title') }}</button>
        </div>

        <div class="settings-panel" data-settings-panel="indexing" hidden>
            <form method="POST" action="{{ route('error-log-monitor.settings.indexing.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="incomplete_notification_enabled" value="0">
                <input type="hidden" name="recovery_notification_enabled" value="0">

                @foreach([
                    'max_runtime_seconds' => [5, 3600],
                    'max_files_per_run' => [1, 10000],
                    'max_lines_per_file' => [100, 1000000],
                    'stale_after_minutes' => [1, 1440],
                    'notification_cooldown_minutes' => [0, 10080],
                    'run_history_days' => [1, 3650],
                ] as $key => [$min, $max])
                    <div class="field" style="margin-bottom: 14px;">
                        <label for="indexing-{{ $key }}">{{ __('error-log-monitor::messages.settings.'.$key) }}</label>
                        <input id="indexing-{{ $key }}" type="number" name="{{ $key }}" min="{{ $min }}" max="{{ $max }}" value="{{ old($key, $indexingSettings[$key]['value']) }}">
                        <small>{{ __('error-log-monitor::messages.settings.configured_value', ['value' => $indexingSettings[$key]['configured']]) }} @if($indexingSettings[$key]['overridden']) · {{ __('error-log-monitor::messages.settings.overridden') }} @endif</small>
                        @error($key)<div class="settings-warning">{{ $message }}</div>@enderror
                    </div>
                @endforeach

                <div class="field" style="margin-bottom: 14px;">
                    <label for="incomplete-notification-mode">{{ __('error-log-monitor::messages.settings.incomplete_notification_mode') }}</label>
                    <select id="incomplete-notification-mode" name="incomplete_notification_mode">
                        <option value="immediate" @selected(old('incomplete_notification_mode', $indexingSettings['incomplete_notification_mode']['value']) === 'immediate')>{{ __('error-log-monitor::messages.settings.notification_immediate') }}</option>
                        <option value="stale" @selected(old('incomplete_notification_mode', $indexingSettings['incomplete_notification_mode']['value']) === 'stale')>{{ __('error-log-monitor::messages.settings.notification_stale') }}</option>
                    </select>
                </div>

                <label class="checkbox-label" style="margin-bottom: 14px;"><input type="checkbox" name="incomplete_notification_enabled" value="1" @checked(old('incomplete_notification_enabled', $indexingSettings['incomplete_notification_enabled']['value']))> {{ __('error-log-monitor::messages.settings.incomplete_notification_enabled') }}</label>
                <label class="checkbox-label" style="margin-bottom: 18px;"><input type="checkbox" name="recovery_notification_enabled" value="1" @checked(old('recovery_notification_enabled', $indexingSettings['recovery_notification_enabled']['value']))> {{ __('error-log-monitor::messages.settings.recovery_notification_enabled') }}</label>
                <button type="submit" class="btn btn-primary">{{ __('error-log-monitor::messages.settings.save_indexing') }}</button>
            </form>

            <form method="POST" action="{{ route('error-log-monitor.settings.override.destroy') }}" style="margin-top: 12px;">
                @csrf
                @method('DELETE')
                <input type="hidden" name="group" value="indexing"><input type="hidden" name="key" value="*">
                <button type="submit" class="btn">{{ __('error-log-monitor::messages.settings.reset_to_config') }}</button>
            </form>
        </div>

        <div class="settings-panel" data-settings-panel="general">
            <div class="setting-row">
                <div>
                    <h3>{{ __('error-log-monitor::messages.settings.monitoring') }}</h3>
                    <p>{{ __('error-log-monitor::messages.settings.monitoring_description') }}</p>
                </div>
                <span class="monitoring-status {{ ($monitoring['enabled'] ?? true) ? 'is-enabled' : 'is-disabled' }}">
                    {{ ($monitoring['enabled'] ?? true) ? __('error-log-monitor::messages.settings.active') : __('error-log-monitor::messages.settings.suspended') }}
                </span>
            </div>

            @if(!($monitoring['allowed_by_configuration'] ?? true))
                <div class="settings-notice">
                    {{ __('error-log-monitor::messages.settings.monitoring_config_disabled') }}
                </div>
            @elseif($monitoring['enabled'] ?? true)
                <form
                    method="POST"
                    action="{{ route('error-log-monitor.settings.monitoring.update') }}"
                    data-disable-monitoring-form
                >
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="enabled" value="0">

                    <div class="settings-warning">
                        {{ __('error-log-monitor::messages.settings.suspend_warning') }}
                    </div>

                    <button type="submit" class="btn btn-danger">{{ __('error-log-monitor::messages.settings.suspend') }}</button>
                </form>
            @else
                <form method="POST" action="{{ route('error-log-monitor.settings.monitoring.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="enabled" value="1">

                    <fieldset class="resume-options">
                        <legend>{{ __('error-log-monitor::messages.settings.resume_question') }}</legend>

                        <label class="resume-option">
                            <input type="radio" name="resume_mode" value="catch_up" checked>
                            <span>
                                <strong>{{ __('error-log-monitor::messages.settings.catch_up') }}</strong>
                                <small>{{ __('error-log-monitor::messages.settings.catch_up_description') }}</small>
                            </span>
                        </label>

                        <label class="resume-option">
                            <input type="radio" name="resume_mode" value="from_now">
                            <span>
                                <strong>{{ __('error-log-monitor::messages.settings.from_now') }}</strong>
                                <small>{{ __('error-log-monitor::messages.settings.from_now_description') }}</small>
                            </span>
                        </label>
                    </fieldset>

                    <button type="submit" class="btn btn-primary">{{ __('error-log-monitor::messages.settings.enable') }}</button>
                </form>
            @endif

            <div class="setting-row setting-row-spaced">
                <div>
                    <h3>{{ __('error-log-monitor::messages.settings.bulk_actions') }}</h3>
                    <p>{{ __('error-log-monitor::messages.settings.bulk_actions_description') }}</p>
                </div>
                <span class="monitoring-status {{ ($bulkActionsEnabled ?? true) ? 'is-enabled' : 'is-disabled' }}">
                    {{ ($bulkActionsEnabled ?? true) ? __('error-log-monitor::messages.settings.active') : __('error-log-monitor::messages.settings.disabled') }}
                </span>
            </div>

            <form method="POST" action="{{ route('error-log-monitor.settings.bulk-actions.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="enabled" value="{{ ($bulkActionsEnabled ?? true) ? '0' : '1' }}">

                <button type="submit" class="btn {{ ($bulkActionsEnabled ?? true) ? 'btn-danger' : 'btn-primary' }}">
                    {{ ($bulkActionsEnabled ?? true) ? __('error-log-monitor::messages.settings.disable_bulk') : __('error-log-monitor::messages.settings.enable_bulk') }}
                </button>
            </form>

            <div class="setting-row setting-row-spaced">
                <div>
                    <h3>{{ __('error-log-monitor::messages.settings.deletion') }}</h3>
                    <p>{{ __('error-log-monitor::messages.settings.deletion_description') }}</p>
                </div>
                <span class="monitoring-status {{ ($deletionEnabled ?? false) ? 'is-enabled' : 'is-disabled' }}">
                    {{ ($deletionEnabled ?? false) ? __('error-log-monitor::messages.settings.active') : __('error-log-monitor::messages.settings.disabled') }}
                </span>
            </div>

            <form method="POST" action="{{ route('error-log-monitor.settings.deletion.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="enabled" value="{{ ($deletionEnabled ?? false) ? '0' : '1' }}">

                <button type="submit" class="btn {{ ($deletionEnabled ?? false) ? 'btn-danger' : 'btn-primary' }}">
                    {{ ($deletionEnabled ?? false) ? __('error-log-monitor::messages.settings.disable_deletion') : __('error-log-monitor::messages.settings.enable_deletion') }}
                </button>
            </form>

            <div class="setting-row setting-row-spaced">
                <div>
                    <h3>{{ __('error-log-monitor::messages.settings.language') }}</h3>
                    <p>{{ __('error-log-monitor::messages.settings.language_description') }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('error-log-monitor.settings.locale.update') }}">
                @csrf
                @method('PUT')
                <div class="field" style="max-width: 280px; margin-bottom: 14px;">
                    <label for="dashboard-locale">{{ __('error-log-monitor::messages.settings.language') }}</label>
                    <select id="dashboard-locale" name="locale">
                        @foreach($dashboardLocales as $locale => $label)
                            <option value="{{ $locale }}" @selected($dashboardLocale === $locale)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('error-log-monitor::messages.settings.save_language') }}</button>
            </form>

            <div class="setting-row setting-row-spaced"><div><h3>{{ __('error-log-monitor::messages.settings.dashboard_preferences') }}</h3><p>{{ __('error-log-monitor::messages.settings.dashboard_preferences_description') }}</p></div></div>
            <form method="POST" action="{{ route('error-log-monitor.settings.dashboard.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="statistics_collapsed_by_default" value="0">
                <div class="field" style="margin-bottom: 14px;"><label for="dashboard-per-page">{{ __('error-log-monitor::messages.settings.per_page') }}</label><input id="dashboard-per-page" type="number" min="1" max="200" name="per_page" value="{{ old('per_page', $dashboardSettings['per_page']['value']) }}"></div>
                <div class="field" style="margin-bottom: 14px;"><label for="dashboard-default-interval">{{ __('error-log-monitor::messages.settings.default_interval') }}</label><select id="dashboard-default-interval" name="default_interval">@foreach($intervals as $value => $label)<option value="{{ $value }}" @selected(old('default_interval', $dashboardSettings['default_interval']['value']) === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="field" style="margin-bottom: 14px;"><label for="dashboard-date-format">{{ __('error-log-monitor::messages.settings.date_format') }}</label><select id="dashboard-date-format" name="date_format">@foreach(['d.m.Y H:i:s', 'Y-m-d H:i:s', 'd/m/Y H:i:s'] as $format)<option value="{{ $format }}" @selected(old('date_format', $dashboardSettings['date_format']['value']) === $format)>{{ now()->format($format) }}</option>@endforeach</select></div>
                <div class="field" style="margin-bottom: 14px;"><label for="dashboard-theme">{{ __('error-log-monitor::messages.settings.default_theme') }}</label><select id="dashboard-theme" name="default_theme"><option value="light" @selected(old('default_theme', $dashboardSettings['default_theme']['value']) === 'light')>{{ __('error-log-monitor::messages.settings.theme_light') }}</option><option value="dark" @selected(old('default_theme', $dashboardSettings['default_theme']['value']) === 'dark')>{{ __('error-log-monitor::messages.settings.theme_dark') }}</option></select></div>
                <label class="checkbox-label" style="margin-bottom: 18px;"><input type="checkbox" name="statistics_collapsed_by_default" value="1" @checked(old('statistics_collapsed_by_default', $dashboardSettings['statistics_collapsed_by_default']['value']))> {{ __('error-log-monitor::messages.settings.statistics_collapsed_by_default') }}</label>
                <button type="submit" class="btn btn-primary">{{ __('error-log-monitor::messages.settings.save_dashboard') }}</button>
            </form>
            <form method="POST" action="{{ route('error-log-monitor.settings.override.destroy') }}" style="margin-top: 12px;">@csrf @method('DELETE')<input type="hidden" name="group" value="dashboard">@foreach(['per_page', 'default_interval', 'date_format', 'statistics_collapsed_by_default', 'default_theme'] as $key)<input type="hidden" name="keys[]" value="{{ $key }}">@endforeach<button type="submit" class="btn">{{ __('error-log-monitor::messages.settings.reset_dashboard_to_config') }}</button></form>
        </div>

        <div class="settings-panel" data-settings-panel="notifications" hidden>
            <form method="POST" action="{{ route('error-log-monitor.settings.notifications.update') }}">
                @csrf
                @method('PUT')

                <input type="hidden" name="enabled" value="0">
                <input type="hidden" name="regressions_enabled" value="0">
                <input type="hidden" name="database_size_enabled" value="0">

                <div class="field" style="margin-bottom: 18px;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $notificationSettings['enabled']))>
                        {{ __('error-log-monitor::messages.notifications.enabled') }}
                    </label>
                </div>

                <div class="field" style="margin-bottom: 18px;">
                    <label for="notification-recipients">{{ __('error-log-monitor::messages.notifications.recipients') }}</label>
                    <textarea id="notification-recipients" name="recipients" rows="4" placeholder="admin@example.com">{{ old('recipients', implode("\n", $notificationSettings['recipients'] ?? [])) }}</textarea>
                    <small>{{ __('error-log-monitor::messages.notifications.recipients_help') }}</small>
                    @error('recipients')<div class="settings-warning">{{ $message }}</div>@enderror
                </div>

                <div class="setting-row">
                    <div>
                        <h3>{{ __('error-log-monitor::messages.notifications.regressions') }}</h3>
                        <p>{{ __('error-log-monitor::messages.notifications.regressions_help') }}</p>
                    </div>
                    <input type="checkbox" name="regressions_enabled" value="1" @checked(old('regressions_enabled', $notificationSettings['regressions_enabled']))>
                </div>

                <div class="setting-row setting-row-spaced">
                    <div>
                        <h3>{{ __('error-log-monitor::messages.notifications.database_size') }}</h3>
                        <p>{{ __('error-log-monitor::messages.notifications.database_size_help') }}</p>
                    </div>
                    <input type="checkbox" name="database_size_enabled" value="1" @checked(old('database_size_enabled', $notificationSettings['database_size_enabled']))>
                </div>

                <div class="field" style="max-width: 240px; margin-bottom: 18px;">
                    <label for="database-size-threshold">{{ __('error-log-monitor::messages.notifications.threshold_mb') }}</label>
                    <input id="database-size-threshold" type="number" min="1" name="database_size_threshold_mb" value="{{ old('database_size_threshold_mb', $notificationSettings['database_size_threshold_mb']) }}">
                    @error('database_size_threshold_mb')<div class="settings-warning">{{ $message }}</div>@enderror
                </div>

                <fieldset class="resume-options" style="margin-bottom: 18px;">
                    <legend>{{ __('error-log-monitor::messages.notifications.levels') }}</legend>
                    @foreach($levels as $level)
                        <label class="resume-option">
                            <input type="checkbox" name="levels[]" value="{{ $level }}" @checked(in_array($level, old('levels', $notificationSettings['levels'] ?? []), true))>
                            <span><strong>{{ strtoupper($level) }}</strong></span>
                        </label>
                    @endforeach
                </fieldset>

                <div class="field" style="max-width: 240px; margin-bottom: 18px;"><label for="notification-cooldown">{{ __('error-log-monitor::messages.notifications.cooldown_minutes') }}</label><input id="notification-cooldown" type="number" min="0" max="10080" name="cooldown_minutes" value="{{ old('cooldown_minutes', $notificationSettings['cooldown_minutes']) }}"></div>

                @error('levels')<div class="settings-warning">{{ $message }}</div>@enderror

                <button type="submit" class="btn btn-primary">{{ __('error-log-monitor::messages.notifications.save') }}</button>
            </form>
        </div>

        <div class="settings-panel" data-settings-panel="retention" hidden>
            <div class="settings-warning">{{ __('error-log-monitor::messages.settings.retention_warning') }}</div>
            <form method="POST" action="{{ route('error-log-monitor.settings.retention.update') }}">
                @csrf
                @method('PUT')
                <div class="field" style="margin-bottom: 14px;">
                    <label for="retention-max-occurrences-per-issue">{{ __('error-log-monitor::messages.settings.max_occurrences_per_issue') }}</label>
                    <input id="retention-max-occurrences-per-issue" type="number" name="max_occurrences_per_issue" min="0" max="100000" value="{{ old('max_occurrences_per_issue', $retentionSettings['max_occurrences_per_issue']['value']) }}">
                    <small>{{ __('error-log-monitor::messages.settings.zero_unlimited_occurrences') }} · {{ __('error-log-monitor::messages.settings.configured_value', ['value' => $retentionSettings['max_occurrences_per_issue']['configured'] ?? 100]) }} @if($retentionSettings['max_occurrences_per_issue']['overridden']) · {{ __('error-log-monitor::messages.settings.overridden') }} @endif</small>
                    @error('max_occurrences_per_issue')<div class="settings-warning">{{ $message }}</div>@enderror
                </div>
                @foreach(['occurrences_days', 'resolved_issues_days', 'ignored_issues_days', 'open_issues_days'] as $key)
                    <div class="field" style="margin-bottom: 14px;">
                        <label for="retention-{{ $key }}">{{ __('error-log-monitor::messages.settings.'.$key) }}</label>
                        <input id="retention-{{ $key }}" type="number" name="{{ $key }}" min="0" max="36500" value="{{ old($key, $retentionSettings[$key]['value']) }}">
                        <small>{{ __('error-log-monitor::messages.settings.zero_unlimited') }} · {{ __('error-log-monitor::messages.settings.configured_value', ['value' => $retentionSettings[$key]['configured'] ?? 0]) }} @if($retentionSettings[$key]['overridden']) · {{ __('error-log-monitor::messages.settings.overridden') }} @endif</small>
                        @error($key)<div class="settings-warning">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <input type="hidden" name="optimize_tables_after_prune" value="0">
                <label class="resume-option" style="margin-bottom: 14px;">
                    <input type="checkbox" name="optimize_tables_after_prune" value="1" @checked((bool) old('optimize_tables_after_prune', $retentionSettings['optimize_tables_after_prune']['value']))>
                    <span><strong>{{ __('error-log-monitor::messages.settings.optimize_tables_after_prune') }}</strong><small>{{ __('error-log-monitor::messages.settings.optimize_tables_after_prune_help') }}</small></span>
                </label>
                <button type="submit" class="btn btn-primary">{{ __('error-log-monitor::messages.settings.save_retention') }}</button>
            </form>
            <form method="POST" action="{{ route('error-log-monitor.settings.override.destroy') }}" style="margin-top: 12px;">
                @csrf
                @method('DELETE')
                <input type="hidden" name="group" value="retention"><input type="hidden" name="key" value="*">
                <button type="submit" class="btn">{{ __('error-log-monitor::messages.settings.reset_to_config') }}</button>
            </form>
        </div>

        <div class="settings-panel" data-settings-panel="normalization" hidden>
            <div class="settings-warning">{{ __('error-log-monitor::messages.normalization.regroup_warning') }}</div>
            @if($normalizationRegroupPending)
                <div class="monitoring-banner monitoring-banner-warning" style="margin-top: 12px;">{{ __('error-log-monitor::messages.normalization.pending') }}</div>
            @endif

            <details style="margin: 18px 0;" @if($normalizationRules->isEmpty()) open @endif>
                <summary class="btn" style="display: inline-flex; cursor: pointer;">{{ __('error-log-monitor::messages.normalization.add') }}</summary>
                <form method="POST" action="{{ route('error-log-monitor.normalization-rules.store') }}" style="border: 1px solid #dfe3ea; border-radius: 10px; padding: 18px; margin-top: 12px;">
                    @csrf
                    <input type="hidden" name="enabled" value="0">
                    <div class="field" style="margin-bottom: 10px;"><label for="new-normalization-name">{{ __('error-log-monitor::messages.normalization.name') }}</label><input id="new-normalization-name" name="name" value="{{ old('name') }}" required maxlength="255"></div>
                    <div class="field" style="margin-bottom: 10px;"><label for="new-normalization-type">{{ __('error-log-monitor::messages.normalization.type') }}</label><select id="new-normalization-type" name="type"><option value="template" @selected(old('type', 'template') === 'template')>{{ __('error-log-monitor::messages.normalization.type_template') }}</option><option value="regex" @selected(old('type') === 'regex')>{{ __('error-log-monitor::messages.normalization.type_regex') }}</option></select></div>
                    <div class="field" style="margin-bottom: 10px;"><label for="new-normalization-pattern">{{ __('error-log-monitor::messages.normalization.pattern_or_template') }}</label><input id="new-normalization-pattern" name="pattern" value="{{ old('pattern') }}" placeholder="[id:{number}][act_{number}] no active token found" required maxlength="1000"><small>{{ __('error-log-monitor::messages.normalization.template_help') }}</small></div>
                    <div class="field" style="margin-bottom: 10px;"><label for="new-normalization-replacement">{{ __('error-log-monitor::messages.normalization.regex_replacement') }}</label><input id="new-normalization-replacement" name="replacement" value="{{ old('replacement', '') }}" placeholder="{{ __('error-log-monitor::messages.normalization.regex_replacement_help') }}" maxlength="1000"></div>
                    <div class="field" style="max-width: 180px; margin-bottom: 10px;"><label for="new-normalization-priority">{{ __('error-log-monitor::messages.normalization.priority') }}</label><input id="new-normalization-priority" type="number" name="priority" min="0" max="10000" value="{{ old('priority', 100) }}"></div>
                    <label class="checkbox-label" style="margin-bottom: 12px;"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', true))> {{ __('error-log-monitor::messages.normalization.enabled') }}</label>
                    @foreach(['name', 'pattern', 'replacement', 'priority', 'enabled'] as $field)
                        @error($field)<div class="settings-warning" style="margin-bottom: 8px;">{{ $message }}</div>@enderror
                    @endforeach
                    <button type="submit" class="btn btn-primary">{{ __('error-log-monitor::messages.normalization.create') }}</button>
                </form>
            </details>

            @forelse($normalizationRules as $normalizationRule)
                <form method="POST" action="{{ route('error-log-monitor.normalization-rules.update', $normalizationRule) }}" style="border-bottom: 1px solid #dfe3ea; padding: 0 0 18px; margin-bottom: 18px;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="enabled" value="0">
                    <div class="field" style="margin-bottom: 10px;"><label>{{ __('error-log-monitor::messages.normalization.name') }}</label><input name="name" value="{{ $normalizationRule->name }}" required maxlength="255"></div>
                    <div class="field" style="margin-bottom: 10px;"><label>{{ __('error-log-monitor::messages.normalization.type') }}</label><select name="type"><option value="template" @selected($normalizationRule->type === 'template')>{{ __('error-log-monitor::messages.normalization.type_template') }}</option><option value="regex" @selected($normalizationRule->type === 'regex')>{{ __('error-log-monitor::messages.normalization.type_regex') }}</option></select></div>
                    <div class="field" style="margin-bottom: 10px;"><label>{{ __('error-log-monitor::messages.normalization.pattern_or_template') }}</label><input name="pattern" value="{{ $normalizationRule->pattern }}" required maxlength="1000"></div>
                    <div class="field" style="margin-bottom: 10px;"><label>{{ __('error-log-monitor::messages.normalization.regex_replacement') }}</label><input name="replacement" value="{{ $normalizationRule->replacement }}" maxlength="1000"></div>
                    <div class="field" style="max-width: 180px; margin-bottom: 10px;"><label>{{ __('error-log-monitor::messages.normalization.priority') }}</label><input type="number" name="priority" min="0" max="10000" value="{{ $normalizationRule->priority }}"></div>
                    <label class="checkbox-label" style="margin-bottom: 12px;"><input type="checkbox" name="enabled" value="1" @checked($normalizationRule->enabled)> {{ __('error-log-monitor::messages.normalization.enabled') }}</label>
                    <button type="submit" class="btn btn-primary">{{ __('error-log-monitor::messages.normalization.update') }}</button>
                    <button type="submit" class="btn btn-danger" formmethod="POST" formaction="{{ route('error-log-monitor.normalization-rules.destroy', $normalizationRule) }}" name="_method" value="DELETE" onclick="return window.confirm(@js(__('error-log-monitor::messages.normalization.confirm_delete')))" style="margin-left: 6px;">{{ __('error-log-monitor::messages.normalization.delete') }}</button>
                </form>
            @empty
                <p>{{ __('error-log-monitor::messages.normalization.empty') }}</p>
            @endforelse
        </div>
    </div>
</dialog>

<div class="card" style="margin-bottom: 18px;">
    <div class="section-heading">
        <div><h2>{{ __('error-log-monitor::messages.indexing_health.title') }}</h2><p>{{ __('error-log-monitor::messages.indexing_health.description') }}</p></div>
        @if($indexingHealth['latest'])
            <span class="monitoring-status {{ $indexingHealth['latest']->status === 'completed' ? 'is-enabled' : 'is-disabled' }}">{{ strtoupper($indexingHealth['latest']->status) }}</span>
        @endif
    </div>
    @if($indexingHealth['latest'])
        <div class="stats-grid">
            <div class="stat"><span>{{ __('error-log-monitor::messages.indexing_health.last_run') }}</span> <strong>{{ $indexingHealth['latest']->finished_at?->format($dateFormat) }}</strong></div>
            <div class="stat"><span>{{ __('error-log-monitor::messages.indexing_health.duration') }}</span> <strong>{{ $indexingHealth['latest']->duration_ms }} ms</strong></div>
            <div class="stat"><span>{{ __('error-log-monitor::messages.indexing_health.files') }}</span> <strong>{{ $indexingHealth['latest']->processed_files }}/{{ $indexingHealth['latest']->discovered_files }}</strong></div>
            <div class="stat"><span>{{ __('error-log-monitor::messages.indexing_health.backlog') }}</span> <strong>{{ $indexingHealth['latest']->pending_files + $indexingHealth['latest']->partially_processed_files }}</strong></div>
            <div class="stat"><span>{{ __('error-log-monitor::messages.indexing_health.partial_24h') }}</span> <strong>{{ $indexingHealth['partial_runs_24h'] }}</strong></div>
            <div class="stat"><span>{{ __('error-log-monitor::messages.indexing_health.reason') }}</span> <strong>{{ $indexingHealth['latest']->stop_reason ?? '—' }}</strong></div>
        </div>
    @else
        <p>{{ __('error-log-monitor::messages.indexing_health.no_runs') }}</p>
    @endif
</div>

<div class="card">
    <form method="GET" action="{{ route('error-log-monitor.dashboard') }}">
        <div class="filters">
            <div class="field">
                <label for="level">{{ __('error-log-monitor::messages.filters.level') }}</label>
                <select id="level" name="level">
                    <option value="">{{ __('error-log-monitor::messages.filters.all') }}</option>

                    @foreach($levels as $level)
                        <option value="{{ $level }}" @selected(($filters['level'] ?? null) === $level)>
                            {{ strtoupper($level) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="interval">{{ __('error-log-monitor::messages.filters.interval') }}</label>
                <select id="interval" name="interval">
                    @foreach($intervals as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['interval'] ?? null) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="query">{{ __('error-log-monitor::messages.filters.query') }}</label>
                <input
                    id="query"
                    type="text"
                    name="query"
                    value="{{ $filters['query'] ?? '' }}"
                    placeholder="{{ __('error-log-monitor::messages.filters.query_placeholder') }}"
                >
            </div>

            <div class="field">
                <label for="file">{{ __('error-log-monitor::messages.filters.file') }}</label>
                <select id="file" name="file">
                    <option value="">{{ __('error-log-monitor::messages.filters.all_files') }}</option>

                    @foreach($files as $file)
                        <option value="{{ $file }}" @selected(($filters['file'] ?? null) === $file)>
                            {{ $file }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="directory">{{ __('error-log-monitor::messages.filters.directory') }}</label>
                <select id="directory" name="directory">
                    <option value="">{{ __('error-log-monitor::messages.filters.all_directories') }}</option>

                    @foreach($directories as $directory)
                        <option value="{{ $directory }}" @selected(($filters['directory'] ?? null) === $directory)>
                            {{ $directory }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="status">{{ __('error-log-monitor::messages.filters.status') }}</label>
                <select id="status" name="status">
                    @foreach(['open', 'resolved', 'ignored', 'regressions', 'all'] as $value)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? 'open') === $value)>
                            {{ __("error-log-monitor::messages.status.{$value}") }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="toolbar">
            <button type="submit" class="btn btn-primary">
                {{ __('error-log-monitor::messages.filters.filter') }}
            </button>

            <a href="{{ route('error-log-monitor.dashboard') }}" class="btn btn-link">
                {{ __('error-log-monitor::messages.filters.reset') }}
            </a>
        </div>
    </form>
</div>

@if($statistics)
    <div class="card statistics-card @if($statisticsCollapsed) is-collapsed @endif">
        <div class="statistics-header">
            <div>
                <h2 class="section-title">{{ __('error-log-monitor::messages.statistics.title') }}</h2>
                <p class="section-subtitle">
                    {{ __('error-log-monitor::messages.statistics.description', ['interval' => $statistics['interval_label'] ?? '-']) }}
                </p>
            </div>

            <button
                type="button"
                class="statistics-toggle"
                data-statistics-toggle
                aria-expanded="{{ $statisticsCollapsed ? 'false' : 'true' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 6-6"></path>
                </svg>
                <span data-statistics-toggle-label>{{ $statisticsCollapsed ? __('error-log-monitor::messages.statistics.expand') : __('error-log-monitor::messages.statistics.collapse') }}</span>
            </button>
        </div>

        <div class="statistics-body">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">{{ __('error-log-monitor::messages.statistics.open_issues') }}</div>
                    <div class="stat-value">{{ number_format($statisticsCards['open_issues'] ?? 0) }}</div>
                    <div class="stat-hint">{{ __('error-log-monitor::messages.statistics.active_in_interval') }}</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">{{ __('error-log-monitor::messages.statistics.new_issues') }}</div>
                    <div class="stat-value">{{ number_format($statisticsCards['new_issues'] ?? 0) }}</div>
                    <div class="stat-hint">{{ __('error-log-monitor::messages.statistics.first_seen_in_interval') }}</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">{{ __('error-log-monitor::messages.statistics.occurrences') }}</div>
                    <div class="stat-value">{{ number_format($statisticsCards['occurrences'] ?? 0) }}</div>
                    <div class="stat-hint">{{ __('error-log-monitor::messages.statistics.occurrences_in_interval') }}</div>
                </div>

                <div class="stat-card stat-danger">
                    <div class="stat-label">{{ __('error-log-monitor::messages.statistics.critical_open') }}</div>
                    <div class="stat-value">{{ number_format($statisticsCards['critical_open'] ?? 0) }}</div>
                    <div class="stat-hint">{{ __('error-log-monitor::messages.statistics.critical_levels') }}</div>
                </div>

                <div class="stat-card stat-danger">
                    <div class="stat-label">{{ __('error-log-monitor::messages.statistics.regressions') }}</div>
                    <div class="stat-value">
                        <a
                            class="stat-value-link"
                            href="{{ route('error-log-monitor.dashboard', [
                                'interval' => $statistics['interval'] ?? config('error-log-monitor.dashboard.default_interval', '24h'),
                                'status' => 'regressions',
                            ]) }}"
                        >{{ number_format($statisticsCards['regressions'] ?? 0) }}</a>
                    </div>
                    <div class="stat-hint">{{ __('error-log-monitor::messages.statistics.regressions_hint') }}</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">{{ __('error-log-monitor::messages.statistics.last_indexed') }}</div>
                    <div class="stat-value stat-value-small">
                        {{ optional($statisticsCards['last_indexed_at'] ?? null)->format($dateFormat) ?? '-' }}
                    </div>
                    <div class="stat-hint">{{ __('error-log-monitor::messages.statistics.last_scan') }}</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">{{ __('error-log-monitor::messages.statistics.db_records') }}</div>
                    <div class="stat-value">{{ number_format($databaseStats['records'] ?? 0) }}</div>
                    <div class="stat-hint">
                        {{ number_format($databaseStats['issues'] ?? 0) }} issues ·
                        {{ number_format($databaseStats['occurrences'] ?? 0) }} occurrences ·
                        {{ number_format($databaseStats['files'] ?? 0) }} files
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">{{ __('error-log-monitor::messages.statistics.db_size') }}</div>
                    <div class="stat-value stat-value-small">{{ $databaseStats['size_label'] ?? 'n/a' }}</div>
                    <div class="stat-hint">{{ __('error-log-monitor::messages.statistics.db_size_hint') }}</div>
                </div>
            </div>

            <div class="stats-panels">
                <div class="stats-panel">
                    <h3>{{ __('error-log-monitor::messages.statistics.top_issues') }}</h3>

                    @if(empty($topIssues))
                        <div class="stats-empty">{{ __('error-log-monitor::messages.statistics.no_data') }}</div>
                    @else
                        <div class="stats-list">
                            @foreach($topIssues as $item)
                                @php
                                    $topIssue = $item['issue'];
                                    $topIssueUrl = route('error-log-monitor.dashboard', array_merge(request()->query(), [
                                        'issue_id' => $topIssue->id,
                                        'status' => 'all',
                                    ])) . '#issue-' . $topIssue->id;
                                @endphp

                                <div class="stats-row">
                                    <a class="stats-row-link" href="{{ $topIssueUrl }}" title="{{ __('error-log-monitor::messages.statistics.view_issue') }}">
                                        <div class="stats-row-title">{{ $topIssue->normalized_message }}</div>
                                        @if($topIssue->last_file_path)
                                            <div class="stats-row-meta">{{ $topIssue->last_file_path }}</div>
                                        @endif
                                    </a>
                                    <div class="stats-row-count">{{ number_format($item['occurrences']) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="stats-panel">
                    <h3>{{ __('error-log-monitor::messages.statistics.top_sources') }}</h3>

                    @if(empty($topSources))
                        <div class="stats-empty">{{ __('error-log-monitor::messages.statistics.no_data') }}</div>
                    @else
                        <div class="stats-list">
                            @foreach($topSources as $source)
                                <div class="stats-row">
                                    <div class="stats-row-title">{{ $source['source'] }}</div>
                                    <div class="stats-row-count">{{ number_format($source['occurrences']) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card">
    <div class="issues-header">
        <h2 class="section-title">{{ __('error-log-monitor::messages.issues.title') }}</h2>

        @if($bulkActionsEnabled ?? true)
            <form
                id="bulk-actions-form"
                method="POST"
                action="{{ route('error-log-monitor.issues.resolve-bulk') }}"
                class="bulk-actions-toolbar"
                data-bulk-actions-form
                hidden
            >
                @csrf
                <span data-bulk-hidden-inputs></span>
                <button
                    type="submit"
                    class="btn"
                    formaction="{{ route('error-log-monitor.normalization-rules.suggest') }}"
                    data-bulk-action-button
                    data-normalization-suggest
                >
                    {{ __('error-log-monitor::messages.normalization.group_selected') }} (<span data-bulk-selected-count>0</span>)
                </button>
                @if($deletionEnabled ?? false)
                    <button
                        type="submit"
                        class="btn btn-danger"
                        formaction="{{ route('error-log-monitor.issues.destroy-bulk') }}"
                        data-bulk-action-button
                        data-bulk-action-label="{{ __('error-log-monitor::messages.bulk.deleted_action') }}"
                        data-confirmation="{{ __('error-log-monitor::messages.javascript.confirm_delete_bulk') }}"
                    >
                        {{ __('error-log-monitor::messages.bulk.delete_selected') }} (<span data-bulk-selected-count>0</span>)
                    </button>
                @endif
                <button
                    type="submit"
                    class="btn btn-warning"
                    formaction="{{ route('error-log-monitor.issues.ignore-bulk') }}"
                    data-bulk-action-button
                    data-bulk-action-label="{{ __('error-log-monitor::messages.status.ignored') }}"
                >
                    {{ __('error-log-monitor::messages.bulk.ignore_selected') }} (<span data-bulk-selected-count>0</span>)
                </button>
                <button type="submit" class="btn btn-primary" data-bulk-action-button data-bulk-action-label="{{ __('error-log-monitor::messages.status.resolved') }}">
                    {{ __('error-log-monitor::messages.bulk.resolve_selected') }} (<span data-bulk-selected-count>0</span>)
                </button>
            </form>
        @endif
    </div>

    @if($issues->isEmpty())
        <p class="section-subtitle">{{ __('error-log-monitor::messages.issues.empty') }}</p>
    @else
        <p class="section-subtitle">
            {{ __('error-log-monitor::messages.issues.showing', ['first' => $issues->firstItem(), 'last' => $issues->lastItem(), 'total' => $issues->total()]) }}
        </p>

        {{-- Desktop / tablet --}}
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        @if($bulkActionsEnabled ?? true)
                            <th class="col-select">
                                <input type="checkbox" data-bulk-select-all aria-label="{{ __('error-log-monitor::messages.bulk.select_all') }}">
                            </th>
                        @endif
                        <th class="col-level">{{ __('error-log-monitor::messages.filters.level') }}</th>
                        <th class="col-message">{{ __('error-log-monitor::messages.issues.message') }}</th>
                        <th class="col-count">{{ __('error-log-monitor::messages.issues.occurrences') }}</th>
                        <th class="col-date">{{ __('error-log-monitor::messages.issues.first_seen') }}</th>
                        <th class="col-date">{{ __('error-log-monitor::messages.issues.last_seen') }}</th>
                        <th class="col-source">{{ __('error-log-monitor::messages.issues.last_source') }}</th>
                        <th class="col-status">{{ __('error-log-monitor::messages.filters.status') }}</th>
                        <th class="col-actions">{{ __('error-log-monitor::messages.issues.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($issues as $issue)
                        @php
                            $levelClass = match (strtolower($issue->level)) {
                                'warning' => 'severity-warning',
                                'error' => 'severity-error',
                                'critical', 'alert', 'emergency' => 'severity-critical',
                                default => 'severity-info',
                            };

                            $statusClass = match (strtolower($issue->status)) {
                                'resolved' => 'status-resolved',
                                'ignored' => 'status-ignored',
                                default => 'status-open',
                            };

                            $stackRowId = 'elm-stack-row-' . $issue->id;
                            $hasDetails = !empty($issue->last_stack_trace) || !empty($issue->last_context);
                            $isFocusedIssue = (string) ($filters['issue_id'] ?? '') === (string) $issue->id;
                        @endphp

                        <tr id="issue-{{ $issue->id }}" @class(['issue-row-focused' => $isFocusedIssue])>
                            @if($bulkActionsEnabled ?? true)
                                <td class="col-select">
                                    @if($issue->status === 'open' || ($deletionEnabled ?? false))
                                        <input
                                            type="checkbox"
                                            value="{{ $issue->id }}"
                                            data-bulk-issue
                                            aria-label="{{ __('error-log-monitor::messages.bulk.select_issue', ['id' => $issue->id]) }}"
                                        >
                                    @endif
                                </td>
                            @endif
                            <td>
                                <span class="severity-badge {{ $levelClass }}">
                                    {{ strtoupper($issue->level) }}
                                </span>
                            </td>

                            <td>
                                <div class="message-head">
                                    <div class="message-main">
                                        <div class="message-title">
                                            {{ $issue->normalized_message }}
                                        </div>

                                        @if($issue->exception_class)
                                            <div class="message-exception">
                                                {{ $issue->exception_class }}
                                            </div>
                                        @endif

                                        <div class="meta-inline" style="margin-top: 8px;">
                                            @if($issue->is_regression)
                                                <span class="regression-badge">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M12 8v4l3 3"></path>
                                                        <path d="M3.05 11a9 9 0 1 1 .5 4m-.5 0H8"></path>
                                                    </svg>
                                                    {{ strtoupper(__('error-log-monitor::messages.issues.regression')) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($hasDetails)
                                        <button
                                            type="button"
                                            class="stack-toggle"
                                            title="Vezi detalii"
                                            aria-label="Vezi detalii"
                                            data-stack-toggle="{{ $stackRowId }}"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 6h16"></path>
                                                <path d="M7 12h10"></path>
                                                <path d="M10 18h4"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="count-strong">{{ $issue->occurrences_count }}</div>
                            </td>

                            <td>
                                <span class="small">{{ optional($issue->first_seen_at)->format($dateFormat) }}</span>
                            </td>

                            <td>
                                <span class="small">{{ optional($issue->last_seen_at)->format($dateFormat) }}</span>
                            </td>

                            <td>
                                <div class="file-path small">
                                    {{ $issue->last_file_path ?? '-' }}
                                </div>
                            </td>

                            <td>
                                <span class="status-badge {{ $statusClass }}">
                                    {{ strtoupper($issue->status) }}
                                </span>
                            </td>

                            <td>
                                <div class="icon-actions">
                                    @if($deletionEnabled ?? false)
                                        <form method="POST" action="{{ route('error-log-monitor.issues.destroy', $issue->id) }}" class="inline-form" data-delete-issue-form>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-action icon-action-danger" title="{{ __('error-log-monitor::messages.issues.delete') }}" aria-label="{{ __('error-log-monitor::messages.issues.delete') }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                    @if($issue->status === 'open')
                                        <form method="POST" action="{{ route('error-log-monitor.issues.ignore', $issue->id) }}" class="inline-form">
                                            @csrf
                                            <button type="submit" class="icon-action icon-action-warning" title="{{ __('error-log-monitor::messages.issues.ignore') }}" aria-label="{{ __('error-log-monitor::messages.issues.ignore') }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 3l18 18"></path>
                                                    <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                                    <path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c5 0 9.27 3.11 11 7-1 2.18-2.71 3.96-4.86 5.14"></path>
                                                    <path d="M6.71 6.72C3.8 8.18 1.73 10.88 1 12c.91 1.97 2.37 3.6 4.18 4.73"></path>
                                                </svg>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('error-log-monitor.issues.resolve', $issue->id) }}" class="inline-form">
                                            @csrf
                                            <button type="submit" class="icon-action icon-action-success" title="{{ __('error-log-monitor::messages.issues.resolve') }}" aria-label="{{ __('error-log-monitor::messages.issues.resolve') }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M20 6L9 17l-5-5"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('error-log-monitor.issues.reopen', $issue->id) }}" class="inline-form">
                                            @csrf
                                            <button type="submit" class="icon-action icon-action-neutral" title="{{ __('error-log-monitor::messages.issues.reopen') }}" aria-label="{{ __('error-log-monitor::messages.issues.reopen') }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 12a9 9 0 1 0 3-6.7L3 8"></path>
                                                    <path d="M3 3v5h5"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if($hasDetails)
                            <tr id="{{ $stackRowId }}" class="stack-row" hidden>
                                <td colspan="{{ ($bulkActionsEnabled ?? true) ? 9 : 8 }}">
                                    <div class="stack-panel">
                                        <pre class="logviewer-message">{{ $issue->last_message ?: $issue->normalized_message }}</pre>

                                        @if(!empty($issue->last_context))
                                            <h4>{{ __('error-log-monitor::messages.issues.context') }}</h4>
                                            @php
                                                $decodedContext = json_decode($issue->last_context, true);
                                                $prettyContext = json_last_error() === JSON_ERROR_NONE
                                                    ? json_encode($decodedContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                                    : $issue->last_context;
                                            @endphp
                                            <pre class="stack-pre">{{ $prettyContext }}</pre>
                                        @endif

                                        @if(!empty($issue->last_stack_trace))
                                            <h4>{{ __('error-log-monitor::messages.issues.stack_trace') }}</h4>
                                            <pre class="stack-pre">{{ $issue->last_stack_trace }}</pre>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="cards-mobile">
            @foreach($issues as $issue)
                @php
                    $levelClass = match (strtolower($issue->level)) {
                        'warning' => 'severity-warning',
                        'error' => 'severity-error',
                        'critical', 'alert', 'emergency' => 'severity-critical',
                        default => 'severity-info',
                    };

                    $statusClass = match (strtolower($issue->status)) {
                        'resolved' => 'status-resolved',
                        'ignored' => 'status-ignored',
                        default => 'status-open',
                    };

                    $mobileStackId = 'elm-mobile-stack-' . $issue->id;
                    $hasDetails = !empty($issue->last_stack_trace) || !empty($issue->last_context);
                    $isFocusedIssue = (string) ($filters['issue_id'] ?? '') === (string) $issue->id;
                @endphp

                <div id="issue-{{ $issue->id }}" @class(['issue-card', 'issue-row-focused' => $isFocusedIssue])>
                    <div class="issue-card-top">
                        @if(($bulkActionsEnabled ?? true) && ($issue->status === 'open' || ($deletionEnabled ?? false)))
                            <input
                                type="checkbox"
                                class="mobile-bulk-checkbox"
                                value="{{ $issue->id }}"
                                data-bulk-issue
                                aria-label="{{ __('error-log-monitor::messages.bulk.select_issue', ['id' => $issue->id]) }}"
                            >
                        @endif

                        <div style="min-width: 0; flex: 1;">
                            <div style="margin-bottom: 10px;">
                                <span class="severity-badge {{ $levelClass }}">
                                    {{ strtoupper($issue->level) }}
                                </span>
                            </div>

                            <h3 class="issue-card-title">
                                {{ $issue->normalized_message }}
                            </h3>

                            @if($issue->exception_class)
                                <div class="message-exception" style="margin-top: 4px;">
                                    {{ $issue->exception_class }}
                                </div>
                            @endif
                        </div>

                        @if($hasDetails)
                            <button
                                type="button"
                                class="stack-toggle"
                                title="Vezi detalii"
                                aria-label="Vezi detalii"
                                data-stack-toggle="{{ $mobileStackId }}"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 6h16"></path>
                                    <path d="M7 12h10"></path>
                                    <path d="M10 18h4"></path>
                                </svg>
                            </button>
                        @endif
                    </div>

                    <div class="meta-inline" style="margin-bottom: 10px;">
                        <span class="status-badge {{ $statusClass }}">
                            {{ strtoupper($issue->status) }}
                        </span>

                        @if($issue->is_regression)
                            <span class="regression-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 8v4l3 3"></path>
                                    <path d="M3.05 11a9 9 0 1 1 .5 4m-.5 0H8"></path>
                                </svg>
                                        {{ strtoupper(__('error-log-monitor::messages.issues.regression')) }}
                            </span>
                        @endif
                    </div>

                    <div class="issue-grid">
                        <div class="issue-grid-item">
                            <div class="issue-grid-label">{{ __('error-log-monitor::messages.issues.occurrences') }}</div>
                            <div class="issue-grid-value">{{ $issue->occurrences_count }}</div>
                        </div>

                        <div class="issue-grid-item">
                            <div class="issue-grid-label">{{ __('error-log-monitor::messages.issues.last_source') }}</div>
                            <div class="issue-grid-value">
                                {{ $issue->last_file_path ?? '-' }}
                            </div>
                        </div>

                        <div class="issue-grid-item">
                            <div class="issue-grid-label">{{ __('error-log-monitor::messages.issues.first_seen') }}</div>
                            <div class="issue-grid-value">{{ optional($issue->first_seen_at)->format($dateFormat) }}</div>
                        </div>

                        <div class="issue-grid-item">
                            <div class="issue-grid-label">{{ __('error-log-monitor::messages.issues.last_seen') }}</div>
                            <div class="issue-grid-value">{{ optional($issue->last_seen_at)->format($dateFormat) }}</div>
                        </div>
                    </div>

                    @if($hasDetails)
                        <div id="{{ $mobileStackId }}" class="mobile-stack-panel">
                            <pre class="logviewer-message">{{ $issue->last_message ?: $issue->normalized_message }}</pre>

                            @if(!empty($issue->last_context))
                                <h4>{{ __('error-log-monitor::messages.issues.context') }}</h4>
                                @php
                                    $decodedContext = json_decode($issue->last_context, true);
                                    $prettyContext = json_last_error() === JSON_ERROR_NONE
                                        ? json_encode($decodedContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                        : $issue->last_context;
                                @endphp
                                <pre class="stack-pre">{{ $prettyContext }}</pre>
                            @endif

                            @if(!empty($issue->last_stack_trace))
                                <h4>{{ __('error-log-monitor::messages.issues.stack_trace') }}</h4>
                                <pre class="stack-pre">{{ $issue->last_stack_trace }}</pre>
                            @endif
                        </div>
                    @endif

                    <div class="issue-card-actions">
                        <div class="icon-actions">
                            @if($deletionEnabled ?? false)
                                <form method="POST" action="{{ route('error-log-monitor.issues.destroy', $issue->id) }}" class="inline-form" data-delete-issue-form>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-action icon-action-danger" title="{{ __('error-log-monitor::messages.issues.delete') }}" aria-label="{{ __('error-log-monitor::messages.issues.delete') }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                            @if($issue->status === 'open')
                                <form method="POST" action="{{ route('error-log-monitor.issues.ignore', $issue->id) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="icon-action icon-action-warning" title="{{ __('error-log-monitor::messages.issues.ignore') }}" aria-label="{{ __('error-log-monitor::messages.issues.ignore') }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 3l18 18"></path>
                                            <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                            <path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c5 0 9.27 3.11 11 7-1 2.18-2.71 3.96-4.86 5.14"></path>
                                            <path d="M6.71 6.72C3.8 8.18 1.73 10.88 1 12c.91 1.97 2.37 3.6 4.18 4.73"></path>
                                        </svg>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('error-log-monitor.issues.resolve', $issue->id) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="icon-action icon-action-success" title="{{ __('error-log-monitor::messages.issues.resolve') }}" aria-label="{{ __('error-log-monitor::messages.issues.resolve') }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 6L9 17l-5-5"></path>
                                        </svg>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('error-log-monitor.issues.reopen', $issue->id) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="icon-action icon-action-neutral" title="{{ __('error-log-monitor::messages.issues.reopen') }}" aria-label="{{ __('error-log-monitor::messages.issues.reopen') }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 12a9 9 0 1 0 3-6.7L3 8"></path>
                                            <path d="M3 3v5h5"></path>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($issues->hasPages())
            @php
                $currentPage = $issues->currentPage();
                $lastPage = $issues->lastPage();

                if ($lastPage <= 7) {
                    $paginationItems = range(1, $lastPage);
                } elseif ($currentPage <= 4) {
                    $paginationItems = [1, 2, 3, 4, 5, 'ellipsis-right', $lastPage];
                } elseif ($currentPage >= $lastPage - 3) {
                    $paginationItems = [1, 'ellipsis-left', $lastPage - 4, $lastPage - 3, $lastPage - 2, $lastPage - 1, $lastPage];
                } else {
                    $paginationItems = [1, 'ellipsis-left', $currentPage - 1, $currentPage, $currentPage + 1, 'ellipsis-right', $lastPage];
                }
            @endphp

            <div class="elm-pagination">
                <nav class="elm-pagination-controls" aria-label="Pagination">
                    @if($issues->onFirstPage())
                        <span class="elm-page-disabled elm-page-arrow" aria-hidden="true">‹</span>
                    @else
                        <a class="elm-page-link elm-page-arrow" href="{{ $issues->previousPageUrl() }}" rel="prev" aria-label="{{ __('error-log-monitor::messages.issues.previous_page') }}">‹</a>
                    @endif

                    @foreach($paginationItems as $paginationItem)
                        @if(is_string($paginationItem))
                            <span class="elm-page-ellipsis" aria-hidden="true">…</span>
                        @elseif($paginationItem === $currentPage)
                            <span class="elm-page-active" aria-current="page">{{ $paginationItem }}</span>
                        @else
                            <a class="elm-page-link" href="{{ $issues->url($paginationItem) }}" aria-label="Pagina {{ $paginationItem }}">{{ $paginationItem }}</a>
                        @endif
                    @endforeach

                    @if($issues->hasMorePages())
                        <a class="elm-page-link elm-page-arrow" href="{{ $issues->nextPageUrl() }}" rel="next" aria-label="{{ __('error-log-monitor::messages.issues.next_page') }}">›</a>
                    @else
                        <span class="elm-page-disabled elm-page-arrow" aria-hidden="true">›</span>
                    @endif
                </nav>
            </div>
        @endif
    @endif
</div>
