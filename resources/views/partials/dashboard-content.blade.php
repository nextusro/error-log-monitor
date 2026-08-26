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
        <p>Dashboard pentru erori, warning-uri și mesaje critice din logurile aplicației.</p>
    </div>

    <div class="header-actions">
        <button type="button" class="settings-button" data-settings-open title="Setări" aria-label="Deschide setările">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.12.37.34.7.65.94.3.24.68.38 1.07.4H21v4h-.09a1.7 1.7 0 0 0-1.51.66Z"></path>
            </svg>
        </button>

        <div class="theme-switcher" role="group" aria-label="Schimbă tema">
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

@if($errors->has('monitoring'))
    <div class="monitoring-banner monitoring-banner-danger">
        {{ $errors->first('monitoring') }}
    </div>
@endif

@if(!($monitoring['enabled'] ?? true))
    <div class="monitoring-banner monitoring-banner-warning">
        <div>
            <strong>Monitorizarea este suspendată.</strong>
            Erorile continuă să fie scrise în logurile aplicației, dar nu sunt indexate în dashboard.

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
            <button type="button" class="btn btn-primary" data-settings-open>Reactivează</button>
        @else
            <span class="configuration-lock">Dezactivată din configurația aplicației</span>
        @endif
    </div>
@endif

<dialog class="settings-dialog" data-settings-dialog>
    <div class="settings-dialog-header">
        <div>
            <h2>Setări Error Log Monitor</h2>
            <p>Configurează comportamentul monitorului.</p>
        </div>
        <button type="button" class="dialog-close" data-settings-close aria-label="Închide">&times;</button>
    </div>

    <div class="settings-layout">
        <div class="settings-tabs" role="tablist" aria-label="Grupuri de setări">
            <button type="button" class="settings-tab is-active" role="tab" aria-selected="true">General</button>
            <button type="button" class="settings-tab" role="tab" aria-selected="false" disabled>Indexare</button>
            <button type="button" class="settings-tab" role="tab" aria-selected="false" disabled>Notificări</button>
            <button type="button" class="settings-tab" role="tab" aria-selected="false" disabled>Retenție</button>
        </div>

        <div class="settings-panel">
            <div class="setting-row">
                <div>
                    <h3>Monitorizare loguri</h3>
                    <p>Controlează indexarea erorilor noi. Dashboardul și erorile existente rămân disponibile.</p>
                </div>
                <span class="monitoring-status {{ ($monitoring['enabled'] ?? true) ? 'is-enabled' : 'is-disabled' }}">
                    {{ ($monitoring['enabled'] ?? true) ? 'Activă' : 'Suspendată' }}
                </span>
            </div>

            @if(!($monitoring['allowed_by_configuration'] ?? true))
                <div class="settings-notice">
                    Monitorizarea este dezactivată prin <code>ERROR_LOG_MONITOR_ENABLED</code> și nu poate fi activată din dashboard.
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
                        După suspendare nu se vor mai adăuga erori în monitor până la reactivare.
                    </div>

                    <button type="submit" class="btn btn-danger">Suspendă monitorizarea</button>
                </form>
            @else
                <form method="POST" action="{{ route('error-log-monitor.settings.monitoring.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="enabled" value="1">

                    <fieldset class="resume-options">
                        <legend>Cum dorești să reiei monitorizarea?</legend>

                        <label class="resume-option">
                            <input type="radio" name="resume_mode" value="catch_up" checked>
                            <span>
                                <strong>Recuperează erorile disponibile</strong>
                                <small>Continuă de la ultimul cursor. Unele erori pot lipsi dacă logurile au fost rotite, comprimate sau șterse.</small>
                            </span>
                        </label>

                        <label class="resume-option">
                            <input type="radio" name="resume_mode" value="from_now">
                            <span>
                                <strong>Monitorizează doar erorile viitoare</strong>
                                <small>Ignoră conținutul actual și începe de la finalul fișierelor existente.</small>
                            </span>
                        </label>
                    </fieldset>

                    <button type="submit" class="btn btn-primary">Activează monitorizarea</button>
                </form>
            @endif

            <div class="setting-row setting-row-spaced">
                <div>
                    <h3>Acțiuni bulk</h3>
                    <p>Permite marcarea simultană ca rezolvate sau ignorate a issue-urilor deschise.</p>
                </div>
                <span class="monitoring-status {{ ($bulkActionsEnabled ?? true) ? 'is-enabled' : 'is-disabled' }}">
                    {{ ($bulkActionsEnabled ?? true) ? 'Active' : 'Dezactivate' }}
                </span>
            </div>

            <form method="POST" action="{{ route('error-log-monitor.settings.bulk-actions.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="enabled" value="{{ ($bulkActionsEnabled ?? true) ? '0' : '1' }}">

                <button type="submit" class="btn {{ ($bulkActionsEnabled ?? true) ? 'btn-danger' : 'btn-primary' }}">
                    {{ ($bulkActionsEnabled ?? true) ? 'Dezactivează acțiunile bulk' : 'Activează acțiunile bulk' }}
                </button>
            </form>
        </div>
    </div>
</dialog>

<div class="card">
    <form method="GET" action="{{ route('error-log-monitor.dashboard') }}">
        <div class="filters">
            <div class="field">
                <label for="level">Level</label>
                <select id="level" name="level">
                    <option value="">Toate</option>

                    @foreach($levels as $level)
                        <option value="{{ $level }}" @selected(($filters['level'] ?? null) === $level)>
                            {{ strtoupper($level) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="interval">Interval</label>
                <select id="interval" name="interval">
                    @foreach($intervals as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['interval'] ?? null) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="query">Query</label>
                <input
                    id="query"
                    type="text"
                    name="query"
                    value="{{ $filters['query'] ?? '' }}"
                    placeholder="Caută în mesaj, excepție, stack trace..."
                >
            </div>

            <div class="field">
                <label for="file">Fișier</label>
                <select id="file" name="file">
                    <option value="">Toate fișierele</option>

                    @foreach($files as $file)
                        <option value="{{ $file }}" @selected(($filters['file'] ?? null) === $file)>
                            {{ $file }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="directory">Subdirector</label>
                <select id="directory" name="directory">
                    <option value="">Toate subdirectoarele</option>

                    @foreach($directories as $directory)
                        <option value="{{ $directory }}" @selected(($filters['directory'] ?? null) === $directory)>
                            {{ $directory }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    @foreach(['open' => 'Open', 'resolved' => 'Resolved', 'ignored' => 'Ignored', 'all' => 'All'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? 'open') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="toolbar">
            <button type="submit" class="btn btn-primary">
                Filtrează
            </button>

            <a href="{{ route('error-log-monitor.dashboard') }}" class="btn btn-link">
                Resetează
            </a>
        </div>
    </form>
</div>

@if($statistics)
    <div class="card statistics-card @if($statisticsCollapsed) is-collapsed @endif">
        <div class="statistics-header">
            <div>
                <h2 class="section-title">Statistics</h2>
                <p class="section-subtitle">
                    Calculat doar pentru intervalul selectat: {{ $statistics['interval_label'] ?? '-' }}. Warning-urile nu sunt incluse.
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
                <span data-statistics-toggle-label>{{ $statisticsCollapsed ? 'Expand' : 'Collapse' }}</span>
            </button>
        </div>

        <div class="statistics-body">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Open issues</div>
                    <div class="stat-value">{{ number_format($statisticsCards['open_issues'] ?? 0) }}</div>
                    <div class="stat-hint">active în interval</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">New issues</div>
                    <div class="stat-value">{{ number_format($statisticsCards['new_issues'] ?? 0) }}</div>
                    <div class="stat-hint">first seen în interval</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Occurrences</div>
                    <div class="stat-value">{{ number_format($statisticsCards['occurrences'] ?? 0) }}</div>
                    <div class="stat-hint">apariții în interval</div>
                </div>

                <div class="stat-card stat-danger">
                    <div class="stat-label">Critical open</div>
                    <div class="stat-value">{{ number_format($statisticsCards['critical_open'] ?? 0) }}</div>
                    <div class="stat-hint">critical / alert / emergency</div>
                </div>

                <div class="stat-card stat-danger">
                    <div class="stat-label">Regressions</div>
                    <div class="stat-value">{{ number_format($statisticsCards['regressions'] ?? 0) }}</div>
                    <div class="stat-hint">resolved, dar reapărute</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Last indexed</div>
                    <div class="stat-value stat-value-small">
                        {{ optional($statisticsCards['last_indexed_at'] ?? null)->format($dateFormat) ?? '-' }}
                    </div>
                    <div class="stat-hint">ultima scanare</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">DB records</div>
                    <div class="stat-value">{{ number_format($databaseStats['records'] ?? 0) }}</div>
                    <div class="stat-hint">
                        {{ number_format($databaseStats['issues'] ?? 0) }} issues ·
                        {{ number_format($databaseStats['occurrences'] ?? 0) }} occurrences ·
                        {{ number_format($databaseStats['files'] ?? 0) }} files
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">DB size</div>
                    <div class="stat-value stat-value-small">{{ $databaseStats['size_label'] ?? 'n/a' }}</div>
                    <div class="stat-hint">date + indexuri pentru tabelele monitorului</div>
                </div>
            </div>

            <div class="stats-panels">
                <div class="stats-panel">
                    <h3>Top recurring issues</h3>

                    @if(empty($topIssues))
                        <div class="stats-empty">Nu există date în intervalul selectat.</div>
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
                                    <a class="stats-row-link" href="{{ $topIssueUrl }}" title="Vezi eroarea în listă">
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
                    <h3>Top sources</h3>

                    @if(empty($topSources))
                        <div class="stats-empty">Nu există date în intervalul selectat.</div>
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
        <h2 class="section-title">Issues</h2>

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
                    class="btn btn-warning"
                    formaction="{{ route('error-log-monitor.issues.ignore-bulk') }}"
                    data-bulk-action-button
                    data-bulk-action-label="ignorate"
                >
                    Ignoră selectate (<span data-bulk-selected-count>0</span>)
                </button>
                <button type="submit" class="btn btn-primary" data-bulk-action-button data-bulk-action-label="rezolvate">
                    Rezolvă selectate (<span data-bulk-selected-count>0</span>)
                </button>
            </form>
        @endif
    </div>

    @if($issues->isEmpty())
        <p class="section-subtitle">Nu există erori pentru filtrele selectate.</p>
    @else
        <p class="section-subtitle">
            Afișare {{ $issues->firstItem() }}-{{ $issues->lastItem() }} din {{ $issues->total() }} issue-uri.
        </p>

        {{-- Desktop / tablet --}}
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        @if($bulkActionsEnabled ?? true)
                            <th class="col-select">
                                <input type="checkbox" data-bulk-select-all aria-label="Selectează toate issue-urile deschise de pe pagină">
                            </th>
                        @endif
                        <th class="col-level">Level</th>
                        <th class="col-message">Message</th>
                        <th class="col-count">Occurrences</th>
                        <th class="col-date">First seen</th>
                        <th class="col-date">Last seen</th>
                        <th class="col-source">Last source</th>
                        <th class="col-status">Status</th>
                        <th class="col-actions">Actions</th>
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
                                    @if($issue->status === 'open')
                                        <input
                                            type="checkbox"
                                            value="{{ $issue->id }}"
                                            data-bulk-issue
                                            aria-label="Selectează issue-ul {{ $issue->id }}"
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
                                                    REGRESSION
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
                                    @if($issue->status === 'open')
                                        <form method="POST" action="{{ route('error-log-monitor.issues.resolve', $issue->id) }}" class="inline-form">
                                            @csrf
                                            <button type="submit" class="icon-action icon-action-success" title="Marchează ca rezolvat" aria-label="Marchează ca rezolvat">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M20 6L9 17l-5-5"></path>
                                                </svg>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('error-log-monitor.issues.ignore', $issue->id) }}" class="inline-form">
                                            @csrf
                                            <button type="submit" class="icon-action icon-action-warning" title="Ignoră" aria-label="Ignoră">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 3l18 18"></path>
                                                    <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                                    <path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c5 0 9.27 3.11 11 7-1 2.18-2.71 3.96-4.86 5.14"></path>
                                                    <path d="M6.71 6.72C3.8 8.18 1.73 10.88 1 12c.91 1.97 2.37 3.6 4.18 4.73"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('error-log-monitor.issues.reopen', $issue->id) }}" class="inline-form">
                                            @csrf
                                            <button type="submit" class="icon-action icon-action-neutral" title="Reopen" aria-label="Reopen">
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
                                            <h4>Context:</h4>
                                            @php
                                                $decodedContext = json_decode($issue->last_context, true);
                                                $prettyContext = json_last_error() === JSON_ERROR_NONE
                                                    ? json_encode($decodedContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                                    : $issue->last_context;
                                            @endphp
                                            <pre class="stack-pre">{{ $prettyContext }}</pre>
                                        @endif

                                        @if(!empty($issue->last_stack_trace))
                                            <h4>Stack trace:</h4>
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
                        @if(($bulkActionsEnabled ?? true) && $issue->status === 'open')
                            <input
                                type="checkbox"
                                class="mobile-bulk-checkbox"
                                value="{{ $issue->id }}"
                                data-bulk-issue
                                aria-label="Selectează issue-ul {{ $issue->id }}"
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
                                REGRESSION
                            </span>
                        @endif
                    </div>

                    <div class="issue-grid">
                        <div class="issue-grid-item">
                            <div class="issue-grid-label">Occurrences</div>
                            <div class="issue-grid-value">{{ $issue->occurrences_count }}</div>
                        </div>

                        <div class="issue-grid-item">
                            <div class="issue-grid-label">Last source</div>
                            <div class="issue-grid-value">
                                {{ $issue->last_file_path ?? '-' }}
                            </div>
                        </div>

                        <div class="issue-grid-item">
                            <div class="issue-grid-label">First seen</div>
                            <div class="issue-grid-value">{{ optional($issue->first_seen_at)->format($dateFormat) }}</div>
                        </div>

                        <div class="issue-grid-item">
                            <div class="issue-grid-label">Last seen</div>
                            <div class="issue-grid-value">{{ optional($issue->last_seen_at)->format($dateFormat) }}</div>
                        </div>
                    </div>

                    @if($hasDetails)
                        <div id="{{ $mobileStackId }}" class="mobile-stack-panel">
                            <pre class="logviewer-message">{{ $issue->last_message ?: $issue->normalized_message }}</pre>

                            @if(!empty($issue->last_context))
                                <h4>Context:</h4>
                                @php
                                    $decodedContext = json_decode($issue->last_context, true);
                                    $prettyContext = json_last_error() === JSON_ERROR_NONE
                                        ? json_encode($decodedContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                        : $issue->last_context;
                                @endphp
                                <pre class="stack-pre">{{ $prettyContext }}</pre>
                            @endif

                            @if(!empty($issue->last_stack_trace))
                                <h4>Stack trace:</h4>
                                <pre class="stack-pre">{{ $issue->last_stack_trace }}</pre>
                            @endif
                        </div>
                    @endif

                    <div class="issue-card-actions">
                        <div class="icon-actions">
                            @if($issue->status === 'open')
                                <form method="POST" action="{{ route('error-log-monitor.issues.resolve', $issue->id) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="icon-action icon-action-success" title="Marchează ca rezolvat" aria-label="Marchează ca rezolvat">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 6L9 17l-5-5"></path>
                                        </svg>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('error-log-monitor.issues.ignore', $issue->id) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="icon-action icon-action-warning" title="Ignoră" aria-label="Ignoră">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 3l18 18"></path>
                                            <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                            <path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c5 0 9.27 3.11 11 7-1 2.18-2.71 3.96-4.86 5.14"></path>
                                            <path d="M6.71 6.72C3.8 8.18 1.73 10.88 1 12c.91 1.97 2.37 3.6 4.18 4.73"></path>
                                        </svg>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('error-log-monitor.issues.reopen', $issue->id) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="icon-action icon-action-neutral" title="Reopen" aria-label="Reopen">
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
                        <a class="elm-page-link elm-page-arrow" href="{{ $issues->previousPageUrl() }}" rel="prev" aria-label="Pagina anterioară">‹</a>
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
                        <a class="elm-page-link elm-page-arrow" href="{{ $issues->nextPageUrl() }}" rel="next" aria-label="Pagina următoare">›</a>
                    @else
                        <span class="elm-page-disabled elm-page-arrow" aria-hidden="true">›</span>
                    @endif
                </nav>
            </div>
        @endif
    @endif
</div>
