<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Login') }} | {{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --gs-blue-900: #1e3a5f;
            --gs-blue-700: #2f5f90;
            --gs-blue-500: #4f86c6;
            --gs-cream-100: #f5efe4;
            --gs-cream-50: #fcf9f2;
            --gs-white: #ffffff;
            --gs-text: #21344b;
            --gs-danger: #b42318;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            width: 100%;
            overflow-x: hidden;
            font-family: 'Manrope', sans-serif;
            color: var(--gs-text);
            background:
                radial-gradient(circle at 10% 10%, rgba(79, 134, 198, 0.2), transparent 34%),
                radial-gradient(circle at 90% 0%, rgba(30, 58, 95, 0.14), transparent 30%),
                linear-gradient(160deg, var(--gs-white), var(--gs-cream-50));
        }

        .login-wrap {
            min-height: 100svh;
            padding: clamp(0.7rem, 2vw, 1.5rem);
            display: grid;
            place-items: center;
        }

        .login-frame {
            width: 100%;
            max-width: 1080px;
            border-radius: 26px;
            border: 1px solid rgba(47, 95, 144, 0.18);
            box-shadow: 0 24px 60px rgba(30, 58, 95, 0.14);
            overflow: hidden;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.97), rgba(245, 239, 228, 0.92));
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .login-visual {
            position: relative;
            min-height: 560px;
            background: #dbe7f4;
            border-right: 1px solid rgba(47, 95, 144, 0.15);
        }

        .login-visual img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .login-visual::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(16, 34, 53, 0.72), rgba(16, 34, 53, 0.16));
        }

        .visual-content {
            position: absolute;
            z-index: 2;
            left: 1.3rem;
            right: 1.3rem;
            bottom: 1.3rem;
            border-radius: 16px;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.25);
            background: rgba(11, 25, 39, 0.45);
            color: #fff;
            backdrop-filter: blur(3px);
        }

        .visual-content h1 {
            margin: 0;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2rem, 5vw, 2.6rem);
            line-height: 1;
        }

        .visual-content p {
            margin: 0.5rem 0 0;
            font-size: 0.92rem;
            line-height: 1.55;
            opacity: 0.95;
        }

        .login-form-pane {
            padding: clamp(1rem, 3vw, 2rem);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .top-nav {
            margin-bottom: 0.8rem;
        }

        .top-nav a {
            color: var(--gs-blue-700);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
        }

        .form-title {
            margin: 0;
            font-family: 'Cormorant Garamond', serif;
            color: var(--gs-blue-900);
            font-size: clamp(1.9rem, 4vw, 2.5rem);
            line-height: 1.05;
        }

        .form-sub {
            margin: 0.4rem 0 1.2rem;
            color: rgba(33, 52, 75, 0.8);
            font-size: 0.95rem;
            line-height: 1.55;
        }

        .status {
            margin-bottom: 0.85rem;
            border-radius: 12px;
            padding: 0.72rem 0.82rem;
            background: rgba(79, 134, 198, 0.13);
            border: 1px solid rgba(47, 95, 144, 0.22);
            color: var(--gs-blue-900);
            font-size: 0.88rem;
            font-weight: 600;
        }

        .field {
            margin-bottom: 0.95rem;
        }

        .field label {
            display: block;
            font-size: 0.84rem;
            font-weight: 700;
            color: rgba(33, 52, 75, 0.88);
            margin-bottom: 0.38rem;
            letter-spacing: 0.01em;
        }

        .field input {
            width: 100%;
            border: 1px solid rgba(47, 95, 144, 0.24);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.7rem 0.8rem;
            font-size: 0.93rem;
            color: var(--gs-text);
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .field input:focus {
            border-color: var(--gs-blue-700);
            box-shadow: 0 0 0 3px rgba(79, 134, 198, 0.18);
        }

        .error {
            margin-top: 0.3rem;
            color: var(--gs-danger);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 1rem;
            font-size: 0.87rem;
            color: rgba(33, 52, 75, 0.85);
        }

        .remember input {
            accent-color: var(--gs-blue-700);
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .forgot {
            color: var(--gs-blue-700);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.84rem;
        }

        .btn-login {
            border: none;
            border-radius: 999px;
            padding: 0.62rem 1.2rem;
            background: linear-gradient(135deg, var(--gs-blue-700), var(--gs-blue-900));
            color: #fff;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(30, 58, 95, 0.23);
        }

        @media (max-width: 980px) {
            .login-frame {
                grid-template-columns: 1fr;
            }

            .login-visual {
                min-height: 280px;
                border-right: none;
                border-bottom: 1px solid rgba(47, 95, 144, 0.15);
            }
        }

        @media (max-width: 640px) {
            .login-wrap {
                padding: 0.7rem;
            }

            .login-form-pane {
                padding: 1rem;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-login {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <section class="login-frame" aria-label="Goud Sangam login">
            <aside class="login-visual">
                <img src="{{ asset('images/goud-hero.jpg') }}" alt="Goud Sangam community">
                <div class="visual-content">
                    <h1>Goud Sangam</h1>
                    <p>Secure member access for admins, editors, and members with role-based authorization.</p>
                </div>
            </aside>

            <div class="login-form-pane">
                <div class="top-nav">
                    <a href="{{ url('/') }}">← Back to Splash</a>
                </div>

                <h2 class="form-title">Member Login</h2>
                <p class="form-sub">Use your email or mobile number with password to continue.</p>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="field">
                        <label for="login">Email or Mobile Number</label>
                        <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username">
                        @error('login')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password">
                        @error('password')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <label class="remember" for="remember_me">
                        <input id="remember_me" type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>

                    <div class="form-actions">
                        @if (Route::has('password.request'))
                            <a class="forgot" href="{{ route('password.request') }}">Forgot your password?</a>
                        @endif

                        <button type="submit" class="btn-login">Log in</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</body>
</html>
