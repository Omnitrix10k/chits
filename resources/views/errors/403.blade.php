@php
    $errorCssVersion = file_exists(public_path('css/error-pages.css'))
        ? filemtime(public_path('css/error-pages.css'))
        : time();
    $primaryUrl = auth()->check() ? route('dashboard') : route('login');
    $primaryLabel = auth()->check() ? 'Back to Dashboard' : 'Go to Login';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 | Access Denied</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700,800,900" rel="stylesheet">
    <link href="{{ asset('css/error-pages.css') }}?v={{ $errorCssVersion }}" rel="stylesheet">
</head>
<body class="error-page-body">
    <main class="error-page-shell">
        <section class="error-page-card">
            <div class="error-page-content">
                <aside class="error-page-visual">
                    <span class="error-badge">Restricted Access</span>
                    <h1 class="error-code">403</h1>
                    <p class="error-code-note">You do not have permission for this action.</p>
                </aside>

                <div class="error-page-copy">
                    <h2 class="error-title">Access to this section is restricted</h2>
                    <p class="error-description">
                        Your current role is not authorized to view this page or perform this action.
                        Access policies are enforced to protect committee and member records.
                    </p>
                    <p class="error-help">
                        If you need this access, ask an administrator to update your role permissions.
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
