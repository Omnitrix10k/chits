<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Goud Sangam community splash screen.">

    <title>Goud Sangam</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

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
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            overflow-x: hidden;
        }

        body {
            margin: 0;
            font-family: 'Manrope', sans-serif;
            color: var(--gs-text);
            background:
                radial-gradient(circle at 12% 10%, rgba(79, 134, 198, 0.22), transparent 32%),
                radial-gradient(circle at 88% 0%, rgba(30, 58, 95, 0.14), transparent 28%),
                linear-gradient(160deg, var(--gs-white), var(--gs-cream-50));
        }

        .splash-shell {
            min-height: 100svh;
            padding: clamp(0.7rem, 2vw, 1.5rem);
        }

        .splash-frame {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            border-radius: 28px;
            background: linear-gradient(150deg, rgba(255, 255, 255, 0.95), rgba(245, 239, 228, 0.92));
            border: 1px solid rgba(47, 95, 144, 0.16);
            box-shadow: 0 24px 70px rgba(30, 58, 95, 0.13);
            overflow: hidden;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: clamp(0.95rem, 2vw, 1.25rem) clamp(1rem, 2.2vw, 1.5rem);
            border-bottom: 1px solid rgba(47, 95, 144, 0.14);
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(4px);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            color: var(--gs-white);
            background: linear-gradient(145deg, var(--gs-blue-500), var(--gs-blue-900));
            box-shadow: 0 10px 20px rgba(30, 58, 95, 0.25);
        }

        .brand h1 {
            margin: 0;
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: var(--gs-blue-900);
        }

        .brand p {
            margin: 0;
            font-size: 0.82rem;
            color: rgba(33, 52, 75, 0.72);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.6rem 1.15rem;
            text-decoration: none;
            font-weight: 600;
            font-size: clamp(0.82rem, 1.3vw, 0.9rem);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            color: var(--gs-white);
            background: linear-gradient(135deg, var(--gs-blue-700), var(--gs-blue-900));
            box-shadow: 0 9px 20px rgba(30, 58, 95, 0.24);
        }

        .hero {
            display: grid;
            grid-template-columns: 1.06fr 0.94fr;
            gap: clamp(1.2rem, 2.5vw, 2rem);
            padding: clamp(1rem, 2.6vw, 2.1rem);
            align-items: center;
        }

        .hero-left,
        .hero-right,
        .vision-card,
        .message-strip {
            opacity: 0;
            transform: translateY(16px);
            animation: riseIn 0.8s ease forwards;
        }

        .hero-right {
            animation-delay: 0.16s;
        }

        .message-strip {
            animation-delay: 0.25s;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(79, 134, 198, 0.12);
            color: var(--gs-blue-900);
            border: 1px solid rgba(47, 95, 144, 0.18);
            border-radius: 999px;
            padding: 0.4rem 0.75rem;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .hero-title {
            margin: 0.95rem 0 0.45rem;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.25rem, 5vw, 4rem);
            line-height: 0.98;
            color: var(--gs-blue-900);
        }

        .hero-subtitle {
            margin: 0;
            max-width: 56ch;
            font-size: clamp(0.95rem, 1.5vw, 1.03rem);
            line-height: 1.66;
            color: rgba(33, 52, 75, 0.86);
        }

        .hero-actions {
            margin-top: 1.2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .btn-secondary {
            color: var(--gs-blue-900);
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(47, 95, 144, 0.25);
        }

        .hero-stats {
            margin-top: 1.15rem;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.65rem;
        }

        .stat {
            border-radius: 14px;
            border: 1px solid rgba(47, 95, 144, 0.15);
            background: rgba(255, 255, 255, 0.76);
            padding: 0.72rem;
            text-align: center;
        }

        .stat strong {
            display: block;
            color: var(--gs-blue-900);
            font-size: 1.1rem;
            font-weight: 700;
        }

        .stat span {
            font-size: 0.76rem;
            color: rgba(33, 52, 75, 0.77);
        }

        .hero-image-card {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(47, 95, 144, 0.2);
            box-shadow: 0 14px 34px rgba(30, 58, 95, 0.18);
            min-height: clamp(300px, 44vw, 455px);
            isolation: isolate;
            background: #dbe7f4;
        }

        .hero-image-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(16, 34, 53, 0.58), rgba(16, 34, 53, 0.04));
            z-index: 1;
        }

        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .hero-caption {
            position: absolute;
            left: 1rem;
            right: 1rem;
            bottom: 1rem;
            z-index: 2;
            color: var(--gs-white);
            border-radius: 14px;
            background: rgba(11, 25, 39, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 0.75rem;
            backdrop-filter: blur(3px);
        }

        .hero-caption strong {
            font-size: 1rem;
            display: block;
        }

        .hero-caption span {
            font-size: 0.8rem;
            opacity: 0.92;
        }

        .message-strip {
            margin: 0 clamp(1rem, 2.5vw, 2.1rem) clamp(1rem, 2.5vw, 2.1rem);
            border-radius: 18px;
            padding: clamp(1rem, 2vw, 1.25rem);
            background:
                linear-gradient(122deg, rgba(30, 58, 95, 0.97), rgba(79, 134, 198, 0.88));
            color: var(--gs-white);
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 1rem;
            align-items: center;
        }

        .message-strip h2 {
            margin: 0;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(1.8rem, 3vw, 2.35rem);
            line-height: 1.03;
        }

        .message-strip p {
            margin: 0.6rem 0 0;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .vision-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .vision-card {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.26);
            min-height: 160px;
            position: relative;
            background: #2f5f90;
        }

        .vision-card img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.55;
        }

        .vision-card span {
            position: absolute;
            left: 0.8rem;
            bottom: 0.8rem;
            font-size: 0.86rem;
            font-weight: 600;
            color: #fff;
        }

        .footer-note {
            text-align: center;
            padding: 0 1.1rem clamp(1rem, 2vw, 1.4rem);
            color: rgba(33, 52, 75, 0.72);
            font-size: 0.83rem;
        }

        @keyframes riseIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1080px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .hero-right {
                order: -1;
            }
        }

        @media (max-width: 980px) {
            .hero,
            .message-strip {
                grid-template-columns: 1fr;
            }

            .hero-image-card {
                min-height: 340px;
            }

            .hero-stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 820px) {
            .topbar {
                flex-wrap: wrap;
                justify-content: center;
                text-align: center;
            }

            .brand {
                justify-content: center;
            }

            .hero-actions {
                justify-content: flex-start;
            }

            .hero-stats {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .splash-shell {
                padding: 0.7rem;
            }

            .topbar,
            .hero,
            .message-strip {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .hero {
                padding-top: 1.45rem;
                padding-bottom: 1.45rem;
            }

            .hero-title {
                font-size: clamp(2rem, 12vw, 2.8rem);
            }

            .hero-stats {
                grid-template-columns: 1fr;
            }

            .vision-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
            }

            .hero-actions {
                flex-direction: column;
            }

            .message-strip {
                margin-left: 1rem;
                margin-right: 1rem;
                margin-bottom: 1rem;
            }

            .brand h1 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 420px) {
            .brand {
                flex-direction: column;
                gap: 0.45rem;
            }

            .brand p {
                font-size: 0.78rem;
            }

            .hero-image-card {
                min-height: 260px;
            }

            .hero-caption {
                left: 0.7rem;
                right: 0.7rem;
                bottom: 0.7rem;
                padding: 0.62rem;
            }
        }
    </style>
</head>
<body>
    <div class="splash-shell">
        <div class="splash-frame">
            <header class="topbar">
                <div class="brand">
                    <div class="brand-mark">GS</div>
                    <div>
                        <h1>Goud Sangam</h1>
                        <p>Community Committee</p>
                    </div>
                </div>

                @auth
                    <a class="btn btn-primary" href="{{ url('/dashboard') }}">Dashboard</a>
                @else
                    <a class="btn btn-primary" href="{{ route('login') }}">Member Login</a>
                @endauth
            </header>

            <section class="hero">
                <div class="hero-left">
                    <span class="badge">Community Splash</span>
                    <h2 class="hero-title">Goud Sangam</h2>
                    <p class="hero-subtitle">
                        A trusted committee space built for members, editors, and administrators to coordinate community programs,
                        strengthen shared values, and keep every contribution transparent.
                    </p>

                    <div class="hero-actions">
                        @auth
                            <a class="btn btn-primary" href="{{ url('/dashboard') }}">Open Dashboard</a>
                        @else
                            <a class="btn btn-primary" href="{{ route('login') }}">Sign In</a>
                        @endauth
                        <a class="btn btn-secondary" href="#vision">Community Vision</a>
                    </div>

                    <div class="hero-stats">
                        <div class="stat">
                            <strong>Unity</strong>
                            <span>Member-first culture</span>
                        </div>
                        <div class="stat">
                            <strong>Trust</strong>
                            <span>Verified records</span>
                        </div>
                        <div class="stat">
                            <strong>Growth</strong>
                            <span>Future-ready systems</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-image-card">
                        <img
                            class="hero-image"
                            src="{{ asset('images/goud-hero.jpg') }}"
                            alt="Community members together"
                        >
                        <div class="hero-caption">
                            <strong>Stronger Together</strong>
                            <span>Serving the Goud Sangam community with clarity and collaboration.</span>
                        </div>
                    </div>
                </div>
            </section>

            <section id="vision" class="message-strip">
                <div>
                    <h2>Tradition with Modern Structure</h2>
                    <p>
                        Goud Sangam carries legacy forward through disciplined administration, verified member records,
                        and organized editorial workflows.
                    </p>
                </div>
                <div class="vision-grid">
                    <article class="vision-card">
                        <img src="{{ asset('images/goud-vision-1.jpg') }}" alt="Community gathering">
                        <span>Member Wellbeing</span>
                    </article>
                    <article class="vision-card">
                        <img src="{{ asset('images/goud-vision-2.jpg') }}" alt="Team collaboration">
                        <span>Committee Leadership</span>
                    </article>
                </div>
            </section>

            <p class="footer-note">
                Goud Sangam Committee | Structured. Transparent. Community-driven.
            </p>
        </div>
    </div>
</body>
</html>
