@php
    $errorCssVersion = file_exists(public_path('css/error-pages.css'))
        ? filemtime(public_path('css/error-pages.css'))
        : time();
    $primaryUrl = auth()->check() ? route('dashboard') : route('login');
    $primaryLabel = auth()->check() ? 'Reload Dashboard' : 'Go to Login';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 | Session Expired</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700,800,900" rel="stylesheet">
    <link href="{{ asset('css/error-pages.css') }}?v={{ $errorCssVersion }}" rel="stylesheet">
</head>
<body class="error-page-body">
    <main class="error-page-shell">
        <section class="error-page-card">
            <div class="error-page-content">
                <aside class="error-page-visual">
                    <span class="error-badge">Session Timeout</span>
                    <h1 class="error-code">419</h1>
                    <p class="error-code-note">Your session has expired.</p>
                </aside>

                <div class="error-page-copy">
                    <h2 class="error-title">Session expired, please continue again</h2>
                    <p class="error-description">
                        This usually happens when the page stays open for a long time or the security token becomes invalid.
                        Refresh and retry your last action.
                    </p>
                    <p class="error-help">
                        Unsaved form data may need to be entered again after refreshing the page.
                    </p>

                    <div class="error-actions">
                        <a href="{{ $primaryUrl }}" class="error-btn error-btn-primary">{{ $primaryLabel }}</a>
                        <a href="{{ url()->previous() }}" class="error-btn error-btn-secondary">Go Back</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
