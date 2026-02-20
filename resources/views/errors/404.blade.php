@php
    $errorCssVersion = file_exists(public_path('css/error-pages.css'))
        ? filemtime(public_path('css/error-pages.css'))
        : time();
    $primaryUrl = auth()->check() ? route('dashboard') : url('/');
    $primaryLabel = auth()->check() ? 'Back to Dashboard' : 'Go to Home';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 | Page Not Found</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700,800,900" rel="stylesheet">
    <link href="{{ asset('css/error-pages.css') }}?v={{ $errorCssVersion }}" rel="stylesheet">
</head>
<body class="error-page-body">
    <main class="error-page-shell">
        <section class="error-page-card">
            <div class="error-page-content">
                <aside class="error-page-visual">
                    <span class="error-badge">Page Missing</span>
                    <h1 class="error-code">404</h1>
                    <p class="error-code-note">Requested page was not found.</p>
                </aside>

                <div class="error-page-copy">
                    <h2 class="error-title">The page you are looking for does not exist</h2>
                    <p class="error-description">
                        The URL may be incorrect, or the page may have been moved while the system was updated.
                        Use one of the actions below to continue safely.
                    </p>
                    <p class="error-help">
                        If this keeps happening for a valid link, please contact your administrator and share the full URL.
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
