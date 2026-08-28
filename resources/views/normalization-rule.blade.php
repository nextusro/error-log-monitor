<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('error-log-monitor::messages.normalization.proposal') }}</title>
    <style>
        body { margin: 0; background: #f4f6f9; color: #172033; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        main { max-width: 980px; margin: 36px auto; padding: 0 20px; }
        .card { background: #fff; border: 1px solid #dfe3ea; border-radius: 12px; padding: 24px; box-shadow: 0 8px 24px rgba(20, 30, 50, .06); }
        .field { margin: 16px 0; } label { display: block; font-weight: 700; margin-bottom: 6px; }
        input { box-sizing: border-box; width: 100%; padding: 10px 12px; border: 1px solid #c9d1dd; border-radius: 7px; font: inherit; }
        code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .preview { padding: 14px; margin: 10px 0; background: #f8fafc; border-radius: 8px; overflow-wrap: anywhere; }
        .original { color: #667085; margin-bottom: 8px; }
        .actions { display: flex; gap: 10px; margin-top: 22px; }
        .btn { border: 1px solid #c9d1dd; border-radius: 7px; padding: 10px 15px; color: #172033; background: #fff; text-decoration: none; cursor: pointer; }
        .primary { border-color: #2563eb; background: #2563eb; color: #fff; }
        .warning { padding: 12px; background: #fffbeb; color: #92400e; border: 1px solid #fde68a; border-radius: 8px; }
    </style>
</head>
<body>
<main>
    <div class="card">
        <h1>{{ __('error-log-monitor::messages.normalization.proposal') }}</h1>
        <p>{{ __('error-log-monitor::messages.normalization.proposal_help') }}</p>
        <div class="warning">{{ __('error-log-monitor::messages.normalization.regroup_warning') }}</div>

        <form method="POST" action="{{ route('error-log-monitor.normalization-rules.store') }}">
            @csrf
            <input type="hidden" name="enabled" value="1">
            <input type="hidden" name="type" value="regex">
            <div class="field"><label for="name">{{ __('error-log-monitor::messages.normalization.name') }}</label><input id="name" name="name" value="{{ old('name', $suggestion['name']) }}" required></div>
            <div class="field"><label for="pattern">{{ __('error-log-monitor::messages.normalization.pattern') }}</label><input id="pattern" name="pattern" value="{{ old('pattern', $suggestion['pattern']) }}" required></div>
            <div class="field"><label for="replacement">{{ __('error-log-monitor::messages.normalization.replacement') }}</label><input id="replacement" name="replacement" value="{{ old('replacement', $suggestion['replacement']) }}"></div>
            <div class="field"><label for="priority">{{ __('error-log-monitor::messages.normalization.priority') }}</label><input id="priority" type="number" name="priority" min="0" max="10000" value="{{ old('priority', 100) }}"></div>

            <h2>{{ __('error-log-monitor::messages.normalization.preview') }}</h2>
            @foreach($issues as $issue)
                <div class="preview">
                    <div class="original">{{ $issue->last_message }}</div>
                    <code>{{ $previews[$issue->id] }}</code>
                </div>
            @endforeach

            <div class="actions">
                <button class="btn primary" type="submit">{{ __('error-log-monitor::messages.normalization.accept') }}</button>
                <a class="btn" href="{{ route('error-log-monitor.dashboard') }}">{{ __('error-log-monitor::messages.normalization.cancel') }}</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>
