<!doctype html>
<html lang="{{ app()->getLocale() }}" data-theme="{{ $defaultTheme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Error Log Monitor</title>

    <style>
        :root {
            --bg: #f7f8fb;
            --card: #ffffff;
            --card-border: #e8ebf2;
            --text: #202838;
            --muted: #6f7787;
            --muted-2: #98a0ae;
            --line: #e6e9ef;
            --primary: #26324a;
            --primary-hover: #1f2a3f;
            --success: #0f8f63;
            --success-soft: #eaf8f2;
            --warning: #c87500;
            --warning-soft: #fff7e8;
            --danger: #b33b24;
            --danger-soft: #fdf0eb;
            --critical: #a92921;
            --critical-soft: #fdecec;
            --shadow: 0 4px 14px rgba(16, 24, 40, 0.035);
            --radius: 14px;
            --input-bg: #ffffff;
            --table-head-bg: #fafbfe;
            --expanded-bg: #ffffff;
            --text-strong: #111827;
            --code-text: #111827;
            --focus-ring: rgba(99, 102, 241, 0.07);
        }

        html[data-theme="dark"] {
            --bg: #0f172a;
            --card: #111827;
            --card-border: #263244;
            --text: #d8dee9;
            --muted: #9aa4b2;
            --muted-2: #7e8897;
            --line: #263244;
            --primary: #d8dee9;
            --primary-hover: #ffffff;
            --success: #6ee7b7;
            --success-soft: rgba(16, 185, 129, 0.13);
            --warning: #fbbf24;
            --warning-soft: rgba(251, 191, 36, 0.13);
            --danger: #fb923c;
            --danger-soft: rgba(251, 146, 60, 0.14);
            --critical: #fca5a5;
            --critical-soft: rgba(248, 113, 113, 0.14);
            --shadow: 0 10px 26px rgba(0, 0, 0, 0.25);
            --input-bg: #0b1220;
            --table-head-bg: #0b1220;
            --expanded-bg: #0b1220;
            --text-strong: #f8fafc;
            --code-text: #e5e7eb;
            --focus-ring: rgba(148, 163, 184, 0.16);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .page { max-width: 1400px; margin: 0 auto; padding: 22px 24px; }
        .header { margin-bottom: 16px; }
        .header h1 { margin: 0 0 6px; font-size: 28px; line-height: 1.15; font-weight: 700; letter-spacing: -0.02em; color: var(--text-strong); }
        .header p { margin: 0; color: var(--muted); font-size: 14px; }
        .header-actions { display: flex; align-items: center; gap: 10px; }
        .settings-button { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border: 1px solid var(--line); border-radius: 50%; background: var(--card); color: var(--muted); cursor: pointer; box-shadow: var(--shadow); }
        .settings-button:hover { color: var(--text); }
        .settings-button svg { width: 18px; height: 18px; }

        .card { background: var(--card); border: 1px solid var(--card-border); border-radius: var(--radius); box-shadow: var(--shadow); padding: 18px; }
        .card + .card { margin-top: 16px; }

        .filters { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; margin-bottom: 16px; }
        .field label { display: block; font-size: 12px; font-weight: 650; margin-bottom: 6px; color: var(--muted); }
        .field input, .field select, .field textarea { width: 100%; min-width: 0; border: 1px solid #d8ddea; border-radius: 10px; background: var(--input-bg); color: var(--text); padding: 10px 11px; font-size: 14px; outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease; }
        .field input:focus, .field select:focus, .field textarea:focus { border-color: #aab6d4; box-shadow: 0 0 0 3px var(--focus-ring); }
        .field textarea { display: block; resize: vertical; }
        .field small { display: block; margin-top: 7px; color: var(--muted); font-size: 12px; line-height: 1.45; }
        .field .checkbox-label { display: inline-flex; align-items: center; gap: 9px; margin: 0; color: var(--text-strong); font-size: 14px; cursor: pointer; }
        .field .checkbox-label input[type="checkbox"] { width: 18px; height: 18px; margin: 0; padding: 0; flex: 0 0 auto; }

        .toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 10px; padding: 9px 14px; font-size: 14px; font-weight: 650; cursor: pointer; transition: background 0.15s ease, transform 0.05s ease, opacity 0.15s ease; }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); text-decoration: none; }
        .btn-link { background: transparent; color: var(--muted); padding-left: 0; padding-right: 0; font-weight: 600; }
        .btn-link:hover { color: var(--text); text-decoration: none; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { opacity: .9; }
        .btn-warning { background: var(--warning); color: #fff; }
        .btn-warning:hover { opacity: .9; }

        .monitoring-banner { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 16px; padding: 14px 16px; border: 1px solid; border-radius: 12px; font-size: 14px; line-height: 1.45; }
        .monitoring-banner-warning { color: var(--warning); border-color: color-mix(in srgb, var(--warning) 35%, transparent); background: var(--warning-soft); }
        .monitoring-banner-danger { color: var(--danger); border-color: color-mix(in srgb, var(--danger) 35%, transparent); background: var(--danger-soft); }
        .monitoring-meta { display: block; margin-top: 3px; font-size: 12px; opacity: .85; }
        .configuration-lock { font-size: 12px; font-weight: 700; white-space: nowrap; }

        .settings-dialog { width: min(760px, calc(100vw - 32px)); max-height: calc(100vh - 32px); padding: 0; border: 1px solid var(--card-border); border-radius: 16px; background: var(--card); color: var(--text); box-shadow: 0 24px 70px rgba(0, 0, 0, .25); }
        .settings-dialog::backdrop { background: rgba(15, 23, 42, .58); }
        .settings-dialog-header { display: flex; justify-content: space-between; gap: 16px; padding: 20px 22px; border-bottom: 1px solid var(--line); }
        .settings-dialog-header h2 { margin: 0 0 4px; color: var(--text-strong); font-size: 20px; }
        .settings-dialog-header p { margin: 0; color: var(--muted); font-size: 13px; }
        .dialog-close { border: 0; background: transparent; color: var(--muted); font-size: 28px; line-height: 1; cursor: pointer; }
        .settings-layout { display: grid; grid-template-columns: 170px minmax(0, 1fr); min-height: 330px; }
        .settings-tabs { padding: 16px 12px; border-right: 1px solid var(--line); background: var(--table-head-bg); }
        .settings-tab { display: block; width: 100%; padding: 10px 12px; border: 0; border-radius: 9px; background: transparent; color: var(--muted); text-align: left; font-size: 13px; font-weight: 650; }
        .settings-tab.is-active { color: var(--text-strong); background: var(--card); box-shadow: var(--shadow); }
        .settings-tab:disabled { opacity: .45; }
        .settings-panel { padding: 22px; }
        .setting-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; padding-bottom: 18px; border-bottom: 1px solid var(--line); margin-bottom: 18px; }
        .setting-row-spaced { margin-top: 22px; padding-top: 22px; border-top: 1px solid var(--line); }
        .setting-row h3 { margin: 0 0 5px; color: var(--text-strong); font-size: 16px; }
        .setting-row p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.5; }
        .monitoring-status { display: inline-flex; padding: 5px 9px; border-radius: 999px; font-size: 11px; font-weight: 750; white-space: nowrap; }
        .monitoring-status.is-enabled { color: var(--success); background: var(--success-soft); }
        .monitoring-status.is-disabled { color: var(--warning); background: var(--warning-soft); }
        .settings-warning, .settings-notice { margin-bottom: 16px; padding: 12px 14px; border-radius: 10px; font-size: 13px; line-height: 1.45; }
        .settings-warning { color: var(--warning); background: var(--warning-soft); }
        .settings-notice { color: var(--muted); background: var(--table-head-bg); border: 1px solid var(--line); }
        .resume-options { margin: 0 0 18px; padding: 0; border: 0; }
        .resume-options legend { margin-bottom: 10px; color: var(--text-strong); font-size: 13px; font-weight: 700; }
        .resume-option { display: flex; align-items: flex-start; gap: 10px; padding: 12px; border: 1px solid var(--line); border-radius: 10px; cursor: pointer; }
        .resume-option + .resume-option { margin-top: 9px; }
        .resume-option input { margin-top: 3px; }
        .resume-option strong, .resume-option small { display: block; }
        .resume-option strong { color: var(--text-strong); font-size: 13px; }
        .resume-option small { margin-top: 3px; color: var(--muted); font-size: 12px; line-height: 1.4; }

        .statistics-card { padding: 0; overflow: hidden; }
        .statistics-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 18px 18px 14px; }
        .statistics-header .section-subtitle { margin-bottom: 0; }
        .statistics-toggle { display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--line); border-radius: 999px; background: var(--input-bg); color: var(--muted); padding: 7px 11px; font-size: 13px; font-weight: 650; cursor: pointer; white-space: nowrap; }
        .statistics-toggle:hover { color: var(--text); background: var(--table-head-bg); }
        .statistics-toggle svg { width: 16px; height: 16px; transition: transform 0.15s ease; }
        .statistics-card.is-collapsed .statistics-toggle svg { transform: rotate(-90deg); }
        .statistics-body { padding: 0 18px 18px; }
        .statistics-card.is-collapsed .statistics-body { display: none; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .stat-card { border: 1px solid var(--line); border-radius: 12px; background: var(--input-bg); padding: 14px; min-height: 92px; }
        .stat-label { color: var(--muted); font-size: 12px; font-weight: 700; margin-bottom: 8px; }
        .stat-value { color: var(--text-strong); font-size: 24px; line-height: 1.15; font-weight: 650; letter-spacing: -0.02em; }
        .stat-value-link { color: inherit; text-decoration: underline; text-decoration-thickness: 1px; text-underline-offset: 3px; }
        .stat-value-link:hover { color: var(--critical); }
        .stat-value-link:focus-visible { border-radius: 3px; outline: 2px solid var(--critical); outline-offset: 2px; }
        .stat-value-small { font-size: 16px; line-height: 1.35; letter-spacing: 0; }
        .stat-hint { margin-top: 6px; color: var(--muted-2); font-size: 12px; }
        .stat-danger .stat-value { color: var(--critical); }

        .stats-panels { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 14px; }
        .stats-panel { border: 1px solid var(--line); border-radius: 12px; background: var(--input-bg); padding: 14px; }
        .stats-panel h3 { margin: 0 0 12px; font-size: 15px; font-weight: 700; color: #374151; }
        .stats-list { display: flex; flex-direction: column; gap: 10px; }
        .stats-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; align-items: start; }
        .stats-row-title { font-size: 13px; line-height: 1.35; color: #273244; word-break: break-word; }
        .stats-row-link { display: block; color: #273244; text-decoration: none; }
        .stats-row-link:hover .stats-row-title { text-decoration: underline; }
        .stats-row-link:focus { outline: 2px solid #c7d2fe; outline-offset: 3px; border-radius: 8px; }
        .stats-row-meta { margin-top: 2px; color: var(--muted); font-size: 12px; }
        .stats-row-count { font-size: 14px; font-weight: 650; color: var(--text-strong); white-space: nowrap; }
        .stats-empty { color: var(--muted); font-size: 13px; }

        .section-title { margin: 0 0 5px; font-size: 24px; font-weight: 700; letter-spacing: -0.02em; color: var(--text-strong); }
        .section-subtitle { margin: 0 0 16px; color: var(--muted); font-size: 14px; }
        .issues-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 5px; }
        .issues-header .section-title { margin-bottom: 0; }
        .issues-header form { display: flex; align-items: center; gap: 8px; }
        .bulk-actions-toolbar[hidden] { display: none; }
        .col-select { width: 42px; text-align: center; }
        .col-select input, .mobile-bulk-checkbox { width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer; }

        .table-wrapper { overflow-x: auto; border: 1px solid var(--line); border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; min-width: 1100px; background: var(--input-bg); }
        thead th { text-align: left; font-size: 12px; font-weight: 700; color: var(--muted); background: var(--table-head-bg); border-bottom: 1px solid var(--line); padding: 12px 12px; white-space: nowrap; }
        tbody td { border-bottom: 1px solid var(--line); padding: 13px 12px; vertical-align: top; font-size: 14px; }
        tbody tr:last-child td { border-bottom: 0; }

        .col-level { width: 110px; }
        .col-message { min-width: 390px; }
        .col-count { width: 115px; }
        .col-date { width: 165px; }
        .col-source { width: 220px; }
        .col-status { width: 115px; }
        .col-actions { width: 110px; }

        .severity-badge, .status-badge { display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: 5px 10px; font-size: 11px; font-weight: 750; letter-spacing: 0.02em; white-space: nowrap; }
        .severity-info { background: #eef6ff; color: #245e96; }
        .severity-warning { background: var(--warning-soft); color: var(--warning); }
        .severity-error { background: var(--danger-soft); color: var(--danger); }
        .severity-critical, .severity-alert, .severity-emergency { background: var(--critical-soft); color: var(--critical); }
        .status-open { background: #eef3ff; color: #3155a4; }
        .status-resolved { background: var(--success-soft); color: var(--success); }
        .status-ignored { background: #f2f4f7; color: #667085; }

        .message-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; min-width: 0; }
        .message-main { min-width: 0; flex: 1; }
        .message-title { font-size: 15px; font-weight: 400; line-height: 1.35; margin: 0; word-break: break-word; color: var(--text-strong); }
        .message-exception { margin-top: 2px; color: var(--muted); font-size: 13px; font-weight: 500; word-break: break-word; }
        .meta-inline { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .regression-badge { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 4px 9px; font-size: 11px; font-weight: 750; background: #fff1f3; color: #be123c; }
        .file-path { color: var(--text); word-break: break-word; }
        .muted { color: var(--muted); }
        .small { font-size: 13px; }
        .count-strong { font-size: 15px; font-weight: 400; color: var(--text-strong); }

        .icon-actions { display: inline-flex; align-items: center; gap: 10px; white-space: nowrap; }
        .inline-form { display: inline; margin: 0; padding: 0; }
        .icon-action, .stack-toggle { display: inline-flex; align-items: center; justify-content: center; width: auto; height: auto; padding: 0; margin: 0; border: 0; border-radius: 0; background: transparent; color: var(--muted); cursor: pointer; line-height: 1; appearance: none; -webkit-appearance: none; transition: color 0.15s ease, opacity 0.15s ease; }
        .icon-action:hover, .stack-toggle:hover { background: transparent; color: var(--text); }
        .icon-action svg, .stack-toggle svg { width: 18px; height: 18px; display: block; }
        .icon-action-success:hover { color: var(--success); }
        .icon-action-warning:hover { color: var(--warning); }
        .icon-action-danger:hover { color: var(--danger); }
        .icon-action-neutral:hover { color: #374151; }

        .stack-row[hidden] { display: none; }
        .stack-row td { background: var(--card); padding: 0; }
        .issue-row-focused td { background: #fbfdff; }
        .stack-panel { width: 100%; padding: 18px 22px; border-top: 1px solid var(--line); }
        .logviewer-message { margin: 0 0 14px; padding-bottom: 14px; border-bottom: 1px solid #dfe3ea; color: var(--text-strong); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 14px; line-height: 1.55; white-space: pre-wrap; word-break: break-word; }
        .stack-panel h4 { margin: 0 0 10px; font-size: 16px; line-height: 1.3; font-weight: 700; color: var(--muted); }
        .stack-pre { margin: 0; width: 100%; max-width: 100%; overflow-x: auto; white-space: pre-wrap; word-break: break-word; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; line-height: 1.6; color: var(--text-strong); background: transparent; }
        .stack-pre + h4, .logviewer-message + h4 { margin-top: 16px; }

        .cards-mobile { display: none; }
        .issue-card { border: 1px solid var(--line); border-radius: 14px; padding: 16px; background: var(--input-bg); }
        .issue-card + .issue-card { margin-top: 12px; }
        .issue-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .issue-card-title { font-size: 16px; font-weight: 400; line-height: 1.35; margin: 0; word-break: break-word; color: var(--text-strong); }
        .issue-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 16px; margin-top: 12px; }
        .issue-grid-item { min-width: 0; }
        .issue-grid-label { font-size: 12px; color: var(--muted); font-weight: 700; margin-bottom: 3px; }
        .issue-grid-value { font-size: 14px; color: var(--text); word-break: break-word; }
        .issue-card-actions { display: flex; align-items: center; justify-content: flex-start; gap: 12px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--line); }
        .mobile-stack-panel { display: none; margin-top: 12px; border: 1px solid var(--line); border-radius: 12px; background: var(--input-bg); padding: 14px; }
        .mobile-stack-panel.is-open { display: block; }

        .pagination-wrap { margin-top: 18px; }
        nav[role="navigation"] { margin-top: 4px; }
        nav[role="navigation"] > div:first-child { display: none; }
        nav[role="navigation"] span, nav[role="navigation"] a { font-size: 14px; }



        /* Responsive fixes */
        .pagination-wrap svg {
            width: 18px !important;
            height: 18px !important;
            max-width: 18px !important;
            max-height: 18px !important;
        }

        .pagination-wrap nav[role="navigation"] {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }

        .pagination-wrap nav[role="navigation"] > div:last-child {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .pagination-wrap nav[role="navigation"] > div:last-child > div:first-child,
        .pagination-wrap nav[role="navigation"] p {
            text-align: center;
            color: var(--muted);
            font-size: 13px;
            margin: 0;
        }

        .pagination-wrap nav[role="navigation"] > div:last-child > div:last-child,
        .pagination-wrap nav[role="navigation"] span[aria-current] {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 4px;
        }

        .pagination-wrap nav[role="navigation"] a,
        .pagination-wrap nav[role="navigation"] span[aria-disabled="true"],
        .pagination-wrap nav[role="navigation"] span[aria-current] > span,
        .pagination-wrap nav[role="navigation"] a[rel="prev"],
        .pagination-wrap nav[role="navigation"] a[rel="next"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 8px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1;
            color: var(--text);
            text-decoration: none;
        }

        .pagination-wrap nav[role="navigation"] a:hover {
            background: #f3f5f9;
            text-decoration: none;
        }

        .pagination-wrap nav[role="navigation"] span[aria-current] > span {
            background: var(--primary);
            color: #fff;
        }


        .header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .theme-switcher {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--card);
            box-shadow: var(--shadow);
            white-space: nowrap;
        }

        .theme-option {
            border: 0;
            border-radius: 999px;
            padding: 7px 11px;
            background: transparent;
            color: var(--muted);
            font-size: 13px;
            font-weight: 650;
            cursor: pointer;
            line-height: 1;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .theme-option:hover {
            color: var(--text);
        }

        html[data-theme="light"] .theme-option[data-theme-value="light"],
        html[data-theme="dark"] .theme-option[data-theme-value="dark"] {
            background: var(--primary);
            color: var(--bg);
        }

        html[data-theme="dark"] .field input,
        html[data-theme="dark"] .field select,
        html[data-theme="dark"] .field textarea {
            color-scheme: dark;
        }

        html[data-theme="dark"] .severity-info {
            background: rgba(96, 165, 250, 0.14);
            color: #93c5fd;
        }

        html[data-theme="dark"] .status-open {
            background: rgba(96, 165, 250, 0.14);
            color: #93c5fd;
        }

        html[data-theme="dark"] .status-ignored {
            background: rgba(148, 163, 184, 0.14);
            color: #cbd5e1;
        }

        html[data-theme="dark"] .regression-badge {
            background: rgba(244, 63, 94, 0.15);
            color: #fda4af;
        }


        /*
         * Dark theme refinements.
         */
        html[data-theme="dark"] body {
            background: var(--bg);
        }

        html[data-theme="dark"] .card {
            background: #111827;
            border-color: #2b3648;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.28);
        }

        html[data-theme="dark"] .table-wrapper {
            border-color: #2b3648;
        }

        html[data-theme="dark"] table {
            background: #0f172a;
        }

        html[data-theme="dark"] thead th {
            background: #0b1220;
            color: #aeb8c7;
            border-bottom-color: #334155;
        }

        html[data-theme="dark"] tbody td {
            border-bottom-color: #273244;
        }

        html[data-theme="dark"] tbody tr:hover td {
            background: rgba(148, 163, 184, 0.035);
        }

        html[data-theme="dark"] .message-title,
        html[data-theme="dark"] .issue-card-title {
            color: #e5e7eb;
        }

        html[data-theme="dark"] .message-exception,
        html[data-theme="dark"] .issue-grid-label,
        html[data-theme="dark"] .section-subtitle,
        html[data-theme="dark"] .header p {
            color: #9aa4b2;
        }

        html[data-theme="dark"] .severity-warning {
            background: rgba(251, 191, 36, 0.14);
            color: #fbbf24;
        }

        html[data-theme="dark"] .severity-error {
            background: rgba(251, 146, 60, 0.15);
            color: #fdba74;
        }

        html[data-theme="dark"] .severity-critical,
        html[data-theme="dark"] .severity-alert,
        html[data-theme="dark"] .severity-emergency {
            background: rgba(248, 113, 113, 0.17);
            color: #fca5a5;
        }

        html[data-theme="dark"] .status-open {
            background: rgba(96, 165, 250, 0.16);
            color: #93c5fd;
        }

        html[data-theme="dark"] .status-resolved {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
        }

        html[data-theme="dark"] .status-ignored {
            background: rgba(148, 163, 184, 0.14);
            color: #cbd5e1;
        }

        html[data-theme="dark"] .btn-primary {
            background: #334155;
            color: #f8fafc;
        }

        html[data-theme="dark"] .btn-primary:hover {
            background: #475569;
        }

        html[data-theme="dark"] .btn-link {
            color: #9aa4b2;
        }

        html[data-theme="dark"] .btn-link:hover {
            color: #e5e7eb;
        }

        html[data-theme="dark"] .theme-switcher {
            background: #0b1220;
            border-color: #334155;
        }

        html[data-theme="dark"] .theme-option {
            color: #9aa4b2;
        }

        html[data-theme="dark"] .theme-option:hover {
            color: #f8fafc;
        }

        html[data-theme="dark"] .theme-option[data-theme-value="dark"] {
            background: #e5e7eb;
            color: #0f172a;
        }

        html[data-theme="dark"] .stack-row td {
            background: #0b1220;
        }

        html[data-theme="dark"] .stack-panel {
            border-top-color: #334155;
        }

        html[data-theme="dark"] .logviewer-message {
            color: #f8fafc;
            border-bottom-color: #334155;
        }

        html[data-theme="dark"] .stack-panel h4 {
            color: #cbd5e1;
        }

        html[data-theme="dark"] .stack-pre {
            color: #d1d5db;
        }

        html[data-theme="dark"] .custom-pagination a,
        html[data-theme="dark"] .custom-pagination span {
            color: #cbd5e1;
        }

        html[data-theme="dark"] .custom-pagination a:hover {
            background: rgba(148, 163, 184, 0.11);
            color: #f8fafc;
        }

        html[data-theme="dark"] .custom-pagination .is-active {
            background: #e5e7eb;
            color: #0f172a;
        }

        html[data-theme="dark"] .issue-card {
            background: #0f172a;
            border-color: #2b3648;
        }

        html[data-theme="dark"] .mobile-stack-panel {
            background: #0b1220;
            border-color: #334155;
        }

        html[data-theme="dark"] .statistics-card,
        html[data-theme="dark"] .stat-card,
        html[data-theme="dark"] .top-list-card,
        html[data-theme="dark"] .statistics-list-card {
            background: #0b1220;
            border-color: #2b3648;
        }

        html[data-theme="dark"] .statistics-card-title,
        html[data-theme="dark"] .stat-label,
        html[data-theme="dark"] .top-list-title,
        html[data-theme="dark"] .statistics-list-title {
            color: #cbd5e1;
        }

        html[data-theme="dark"] .statistics-card-value,
        html[data-theme="dark"] .stat-value,
        html[data-theme="dark"] .top-item-count,
        html[data-theme="dark"] .statistics-count {
            color: #f8fafc;
        }

        html[data-theme="dark"] .statistics-card-description,
        html[data-theme="dark"] .stat-description,
        html[data-theme="dark"] .top-item-meta,
        html[data-theme="dark"] .statistics-meta {
            color: #94a3b8;
        }

        html[data-theme="dark"] .top-item-main,
        html[data-theme="dark"] .top-issue-link,
        html[data-theme="dark"] .statistics-link {
            color: #e5e7eb;
        }

        html[data-theme="dark"] .top-issue-link:hover,
        html[data-theme="dark"] .statistics-link:hover {
            color: #ffffff;
        }


        html[data-theme="dark"] .statistics a {
            color: #e5e7eb;
        }

        html[data-theme="dark"] .statistics a:hover {
            color: #ffffff;
        }

        html[data-theme="dark"] .statistics strong,
        html[data-theme="dark"] .statistics .count,
        html[data-theme="dark"] .statistics .value {
            color: #f8fafc;
        }

        html[data-theme="dark"] .statistics .muted,
        html[data-theme="dark"] .statistics small {
            color: #94a3b8;
        }
        @media (max-width: 1200px) {
            .filters { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .stats-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }

        @media (max-width: 900px) {
            .page { padding: 16px; }
            .header h1 { font-size: 24px; }
            .filters { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .statistics-header { flex-direction: column; align-items: stretch; }
            .stats-panels { grid-template-columns: 1fr; }
            .toolbar { gap: 10px; }
        }

        @media (max-width: 720px) {
            .page {
                padding: 12px;
            }

            .header {
                margin-bottom: 12px;
            }

            .header h1 {
                font-size: 22px;
            }

            .header p {
                font-size: 13px;
            }

            .header-row { flex-direction: column; }
            .header-actions { width: 100%; justify-content: space-between; }
            .monitoring-banner { align-items: flex-start; flex-direction: column; }
            .issues-header { align-items: stretch; flex-direction: column; margin-bottom: 12px; }
            .issues-header form { align-items: stretch; flex-direction: column; width: 100%; }
            .issues-header .btn { width: 100%; }
            .settings-layout { grid-template-columns: 1fr; }
            .settings-tabs { display: flex; overflow-x: auto; border-right: 0; border-bottom: 1px solid var(--line); }
            .settings-tab { width: auto; white-space: nowrap; }

            .card {
                padding: 14px;
                border-radius: 14px;
            }

            .card + .card {
                margin-top: 12px;
            }

            .filters {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .statistics-header {
                padding: 14px 14px 10px;
                gap: 10px;
            }

            .statistics-body {
                padding: 0 14px 14px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .stat-card {
                min-height: auto;
                padding: 11px;
            }

            .stat-value {
                font-size: 19px;
            }

            .stat-value-small {
                font-size: 13px;
            }

            .stats-panel {
                padding: 12px;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .btn,
            .btn-link {
                width: 100%;
                justify-content: center;
            }

            .section-title {
                font-size: 22px;
            }

            .section-subtitle {
                font-size: 13px;
                margin-bottom: 14px;
            }

            .table-wrapper {
                display: none;
            }

            .cards-mobile {
                display: block;
            }

            .issue-card {
                padding: 13px;
                border-radius: 13px;
            }

            .issue-card + .issue-card {
                margin-top: 10px;
            }

            .issue-card-top {
                gap: 10px;
                margin-bottom: 8px;
            }

            .issue-card-title {
                display: -webkit-box;
                -webkit-line-clamp: 5;
                -webkit-box-orient: vertical;
                overflow: hidden;
                font-size: 15px;
                line-height: 1.35;
            }

            .severity-badge,
            .status-badge {
                padding: 4px 9px;
                font-size: 10px;
            }

            .issue-grid {
                grid-template-columns: 1fr;
                gap: 7px;
                margin-top: 10px;
            }

            .issue-grid-label {
                font-size: 11px;
                margin-bottom: 2px;
            }

            .issue-grid-value {
                font-size: 13px;
            }

            .issue-card-actions {
                flex-direction: row;
                align-items: center;
                justify-content: flex-start;
                margin-top: 11px;
                padding-top: 11px;
            }

            .issue-card-actions .icon-actions {
                justify-content: flex-start;
            }

            .mobile-stack-panel {
                padding: 12px;
            }

            .logviewer-message,
            .stack-pre {
                font-size: 12px;
            }

            .pagination-wrap {
                margin-top: 14px;
            }
        }

        @media (max-width: 420px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }


        /* Mobile compact pass */
        @media (max-width: 720px) {
            .issue-card {
                padding: 11px 12px;
                border-radius: 12px;
            }

            .issue-card + .issue-card {
                margin-top: 8px;
            }

            .issue-card-top {
                gap: 8px;
                margin-bottom: 7px;
            }

            .issue-card-title {
                font-size: 14px;
                line-height: 1.32;
                font-weight: 400;
                -webkit-line-clamp: 4;
            }

            .issue-card .message-exception {
                font-size: 12px;
                line-height: 1.3;
            }

            .issue-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px 12px;
                margin-top: 9px;
            }

            .issue-grid-label {
                font-size: 11px;
                line-height: 1.2;
                margin-bottom: 2px;
            }

            .issue-grid-value {
                font-size: 12px;
                line-height: 1.3;
            }

            .issue-card-actions {
                margin-top: 9px;
                padding-top: 9px;
            }

            .severity-badge,
            .status-badge {
                padding: 3px 8px;
                font-size: 10px;
            }

            .mobile-stack-panel {
                margin-top: 9px;
                padding: 11px;
            }

            .logviewer-message,
            .stack-pre {
                font-size: 12px;
                line-height: 1.5;
            }

            .stack-panel h4,
            .mobile-stack-panel h4 {
                font-size: 13px;
            }

            .pagination-wrap {
                margin-top: 12px;
            }

            .pagination-wrap nav[role="navigation"] {
                gap: 6px;
            }

            .pagination-wrap nav[role="navigation"] p,
            .pagination-wrap nav[role="navigation"] > div:last-child > div:first-child {
                display: none !important;
            }

            .pagination-wrap nav[role="navigation"] > div:last-child {
                gap: 6px;
            }

            .pagination-wrap nav[role="navigation"] > div:last-child > div:last-child,
            .pagination-wrap nav[role="navigation"] span[aria-current] {
                gap: 2px;
            }

            .pagination-wrap nav[role="navigation"] a,
            .pagination-wrap nav[role="navigation"] span[aria-disabled="true"],
            .pagination-wrap nav[role="navigation"] span[aria-current] > span,
            .pagination-wrap nav[role="navigation"] a[rel="prev"],
            .pagination-wrap nav[role="navigation"] a[rel="next"] {
                min-width: 28px;
                height: 30px;
                padding: 0 6px;
                border-radius: 8px;
                font-size: 13px;
            }

            .pagination-wrap svg {
                width: 14px !important;
                height: 14px !important;
                max-width: 14px !important;
                max-height: 14px !important;
            }
        }

        @media (max-width: 420px) {
            .issue-grid {
                grid-template-columns: 1fr;
            }
        }


        /* Focused mobile compact overrides - applied also on tablet portrait widths. */
        @media (max-width: 900px) {
            .cards-mobile {
                display: block;
            }

            .table-wrapper {
                display: none;
            }

            .issue-card {
                padding: 10px 12px !important;
                border-radius: 12px !important;
            }

            .issue-card + .issue-card {
                margin-top: 8px !important;
            }

            .issue-card-top {
                gap: 8px !important;
                margin-bottom: 6px !important;
            }

            .issue-card-title {
                display: -webkit-box !important;
                -webkit-line-clamp: 3 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
                font-size: 13px !important;
                line-height: 1.28 !important;
                font-weight: 400 !important;
                margin-top: 0 !important;
            }

            .issue-card .message-exception {
                font-size: 11px !important;
                line-height: 1.25 !important;
            }

            .severity-badge,
            .status-badge {
                padding: 3px 8px !important;
                font-size: 10px !important;
                line-height: 1.2 !important;
            }

            .issue-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 7px 10px !important;
                margin-top: 8px !important;
            }

            .issue-grid-label {
                font-size: 10px !important;
                line-height: 1.2 !important;
                margin-bottom: 1px !important;
            }

            .issue-grid-value {
                font-size: 12px !important;
                line-height: 1.28 !important;
            }

            .issue-card-actions {
                margin-top: 8px !important;
                padding-top: 8px !important;
            }

            .icon-actions {
                gap: 12px !important;
            }

            .icon-action svg,
            .stack-toggle svg {
                width: 17px !important;
                height: 17px !important;
            }

            .mobile-stack-panel {
                margin-top: 8px !important;
                padding: 10px !important;
            }

            .logviewer-message,
            .stack-pre {
                font-size: 11px !important;
                line-height: 1.45 !important;
            }

            .pagination-wrap {
                margin-top: 10px !important;
            }

            .pagination-wrap nav[role="navigation"] {
                display: block !important;
            }

            .pagination-wrap nav[role="navigation"] > div:first-child,
            .pagination-wrap nav[role="navigation"] p,
            .pagination-wrap nav[role="navigation"] > div:last-child > div:first-child {
                display: none !important;
            }

            .pagination-wrap nav[role="navigation"] > div:last-child,
            .pagination-wrap nav[role="navigation"] > div:last-child > div:last-child,
            .pagination-wrap nav[role="navigation"] span[aria-current] {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-wrap: wrap !important;
                gap: 4px !important;
                width: 100% !important;
            }

            .pagination-wrap nav[role="navigation"] a,
            .pagination-wrap nav[role="navigation"] span[aria-disabled="true"],
            .pagination-wrap nav[role="navigation"] span[aria-current] > span {
                min-width: 26px !important;
                height: 28px !important;
                padding: 0 6px !important;
                border-radius: 8px !important;
                font-size: 12px !important;
                line-height: 1 !important;
            }

            .pagination-wrap svg {
                width: 13px !important;
                height: 13px !important;
                max-width: 13px !important;
                max-height: 13px !important;
            }
        }


        /* Custom compact paginator */
        .elm-pagination {
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .elm-pagination-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
            max-width: 100%;
        }

        .elm-page-link,
        .elm-page-active,
        .elm-page-disabled,
        .elm-page-ellipsis {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border-radius: 9px;
            font-size: 14px;
            line-height: 1;
            text-decoration: none;
        }

        .elm-page-link {
            color: var(--text);
            background: transparent;
        }

        .elm-page-link:hover {
            background: #f3f5f9;
            text-decoration: none;
        }

        .elm-page-active {
            background: var(--primary);
            color: #fff;
            font-weight: 650;
        }

        .elm-page-disabled,
        .elm-page-ellipsis {
            color: var(--muted);
        }

        .elm-page-disabled {
            opacity: 0.45;
        }

        .elm-page-arrow {
            font-size: 20px;
            font-weight: 500;
            padding-bottom: 2px;
        }

        @media (max-width: 900px) {
            .elm-pagination {
                margin-top: 14px !important;
            }

            .elm-pagination-controls {
                gap: 4px !important;
            }

            .elm-page-link,
            .elm-page-active,
            .elm-page-disabled,
            .elm-page-ellipsis {
                min-width: 29px !important;
                height: 29px !important;
                padding: 0 6px !important;
                border-radius: 8px !important;
                font-size: 13px !important;
            }

            .elm-page-arrow {
                font-size: 18px !important;
            }
        }

        @media (max-width: 420px) {
            .elm-pagination-controls {
                gap: 3px !important;
            }

            .elm-page-link,
            .elm-page-active,
            .elm-page-disabled,
            .elm-page-ellipsis {
                min-width: 27px !important;
                height: 28px !important;
                padding: 0 5px !important;
                font-size: 12px !important;
            }
        }

    </style>
</head>
<body>
    <main class="page">
        @include('error-log-monitor::partials.dashboard-content')
    </main>

    <script>
        (function () {
            const defaultTheme = @json($defaultTheme ?? 'light');
            const storedTheme = localStorage.getItem('error-log-monitor-theme');
            const initialTheme = storedTheme || defaultTheme;

            if (['light', 'dark'].includes(initialTheme)) {
                document.documentElement.setAttribute('data-theme', initialTheme);
            }

            document.addEventListener('click', function (event) {
                const button = event.target.closest('[data-theme-value]');

                if (!button) {
                    return;
                }

                const theme = button.getAttribute('data-theme-value');

                if (!['light', 'dark'].includes(theme)) {
                    return;
                }

                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('error-log-monitor-theme', theme);
            });
        })();

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-stack-toggle]');

            if (!button) {
                return;
            }

            const targetId = button.getAttribute('data-stack-toggle');
            const target = document.getElementById(targetId);

            if (!target) {
                return;
            }

            if (target.tagName === 'TR') {
                target.hidden = !target.hidden;
            } else {
                target.classList.toggle('is-open');
            }
        });

        (function () {
            const form = document.querySelector('[data-bulk-actions-form]');

            if (!form) {
                return;
            }

            const checkboxes = Array.from(document.querySelectorAll('[data-bulk-issue]'));
            const selectAll = document.querySelector('[data-bulk-select-all]');
            const actionButtons = Array.from(form.querySelectorAll('[data-bulk-action-button]'));
            const counts = Array.from(form.querySelectorAll('[data-bulk-selected-count]'));
            const hiddenInputs = form.querySelector('[data-bulk-hidden-inputs]');

            function selectedIds() {
                return [...new Set(
                    checkboxes
                        .filter(function (checkbox) { return checkbox.checked; })
                        .map(function (checkbox) { return checkbox.value; })
                )];
            }

            function updateBulkState() {
                const selected = selectedIds();
                const available = [...new Set(checkboxes.map(function (checkbox) { return checkbox.value; }))];

                form.hidden = selected.length === 0;
                actionButtons.forEach(function (button) { button.disabled = selected.length === 0; });
                counts.forEach(function (count) { count.textContent = String(selected.length); });

                if (selectAll) {
                    selectAll.checked = available.length > 0 && selected.length === available.length;
                    selectAll.indeterminate = selected.length > 0 && selected.length < available.length;
                }
            }

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    checkboxes
                        .filter(function (candidate) { return candidate.value === checkbox.value; })
                        .forEach(function (candidate) { candidate.checked = checkbox.checked; });
                    updateBulkState();
                });
            });

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
                    updateBulkState();
                });
            }

            form.addEventListener('submit', function (event) {
                const selected = selectedIds();

                const actionLabel = event.submitter?.getAttribute('data-bulk-action-label') || @json(__('error-log-monitor::messages.javascript.changed'));
                const confirmation = event.submitter?.getAttribute('data-confirmation')
                    || @json(__('error-log-monitor::messages.javascript.confirm_bulk')).replace(':action', actionLabel.toLowerCase());

                if (selected.length === 0 || !window.confirm(confirmation)) {
                    event.preventDefault();
                    return;
                }

                hiddenInputs.replaceChildren();
                selected.forEach(function (issueId) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'issue_ids[]';
                    input.value = issueId;
                    hiddenInputs.appendChild(input);
                });
            });

            updateBulkState();
        })();

        document.querySelectorAll('[data-delete-issue-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(@json(__('error-log-monitor::messages.javascript.confirm_delete')))) {
                    event.preventDefault();
                }
            });
        });

        document.addEventListener('click', function (event) {
            const dialog = document.querySelector('[data-settings-dialog]');

            if (!dialog) {
                return;
            }

            if (event.target.closest('[data-settings-open]')) {
                dialog.showModal();
            }

            if (event.target.closest('[data-settings-close]')) {
                dialog.close();
            }

            const tab = event.target.closest('[data-settings-tab]');
            if (tab) {
                const selected = tab.dataset.settingsTab;
                dialog.querySelectorAll('[data-settings-tab]').forEach((item) => {
                    const active = item.dataset.settingsTab === selected;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                dialog.querySelectorAll('[data-settings-panel]').forEach((panel) => {
                    panel.hidden = panel.dataset.settingsPanel !== selected;
                });
            }
        });

        const disableMonitoringForm = document.querySelector('[data-disable-monitoring-form]');

        if (disableMonitoringForm) {
            disableMonitoringForm.addEventListener('submit', function (event) {
                const confirmed = window.confirm(@json(__('error-log-monitor::messages.javascript.confirm_suspend')));

                if (!confirmed) {
                    event.preventDefault();
                }
            });
        }

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-statistics-toggle]');

            if (!button) {
                return;
            }

            const card = button.closest('.statistics-card');

            if (!card) {
                return;
            }

            card.classList.toggle('is-collapsed');
            const isCollapsed = card.classList.contains('is-collapsed');
            button.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');

            const label = button.querySelector('[data-statistics-toggle-label]');

            if (label) {
                label.textContent = isCollapsed
                    ? @json(__('error-log-monitor::messages.statistics.expand'))
                    : @json(__('error-log-monitor::messages.statistics.collapse'));
            }
        });
    </script>
</body>
</html>
