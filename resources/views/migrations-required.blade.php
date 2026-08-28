<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('error-log-monitor::messages.migrations.title') }}</title>
    <style>
        body { margin: 0; background: #f4f6f9; color: #172033; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        main { max-width: 760px; margin: 60px auto; padding: 0 20px; }
        .card { background: #fff; border: 1px solid #dfe3ea; border-radius: 12px; padding: 28px; box-shadow: 0 8px 24px rgba(20, 30, 50, .06); }
        code { display: block; margin: 18px 0; padding: 14px; border-radius: 8px; background: #172033; color: #f8fafc; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        details { margin-top: 18px; color: #667085; }
        li { overflow-wrap: anywhere; }
    </style>
</head>
<body>
<main>
    <div class="card">
        <h1>{{ __('error-log-monitor::messages.migrations.title') }}</h1>
        <p>{{ __('error-log-monitor::messages.migrations.description') }}</p>
        <code>php artisan migrate</code>
        <p>{{ __('error-log-monitor::messages.migrations.production_hint') }}</p>

        <details>
            <summary>{{ __('error-log-monitor::messages.migrations.missing_requirements') }}</summary>
            <ul>
                @foreach($missingRequirements as $requirement)
                    <li>{{ $requirement }}</li>
                @endforeach
            </ul>
        </details>
    </div>
</main>
</body>
</html>
