<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Crypto Payment Gateway') // Xundefined</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Free CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Xundefined Cyber Red Design System -->
    <link rel="stylesheet" href="{{ asset('app.css') }}">

    <style>
        body.standalone-pay-body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: #060508;
            background-image: 
                radial-gradient(circle at 50% 15%, rgba(255, 23, 68, 0.08) 0%, transparent 60%),
                linear-gradient(rgba(255, 23, 68, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 23, 68, 0.02) 1px, transparent 1px);
            background-size: 100% 100%, 32px 32px, 32px 32px;
            color: #f5f5f5;
            font-family: var(--font-sans, 'Inter', sans-serif);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .standalone-nav-header {
            width: 100%;
            background: rgba(10, 10, 14, 0.92);
            border-bottom: 1px solid rgba(255, 23, 68, 0.25);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .standalone-nav-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .standalone-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .standalone-brand-icon {
            width: 36px;
            height: 36px;
            background: rgba(255, 23, 68, 0.15);
            border: 1px solid rgba(255, 23, 68, 0.5);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--red-primary, #ff1744);
            font-family: var(--font-mono, monospace);
            font-weight: 800;
            font-size: 0.88rem;
            box-shadow: 0 0 14px rgba(255, 23, 68, 0.25);
        }

        .standalone-brand-text {
            display: flex;
            flex-direction: column;
        }

        .standalone-brand-title {
            font-family: var(--font-mono, monospace);
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #ffffff;
            line-height: 1.2;
        }

        .standalone-brand-sub {
            font-family: var(--font-mono, monospace);
            font-size: 0.68rem;
            color: var(--red-primary, #ff1744);
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .standalone-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .standalone-security-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(0, 255, 102, 0.08);
            border: 1px solid rgba(0, 255, 102, 0.3);
            border-radius: 4px;
            font-family: var(--font-mono, monospace);
            font-size: 0.72rem;
            color: #00ff66;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        .standalone-nav-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 4px;
            font-family: var(--font-mono, monospace);
            font-size: 0.75rem;
            color: #d1d1d1;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .standalone-nav-link:hover {
            background: rgba(255, 23, 68, 0.15);
            border-color: var(--red-primary, #ff1744);
            color: #ffffff;
        }

        .standalone-content-wrap {
            flex: 1;
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
            padding: 30px 20px 40px;
            box-sizing: border-box;
        }

        .standalone-footer {
            width: 100%;
            background: rgba(8, 8, 12, 0.95);
            border-top: 1px solid rgba(255, 23, 68, 0.15);
            padding: 20px;
            box-sizing: border-box;
            margin-top: auto;
        }

        .standalone-footer-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            font-family: var(--font-mono, monospace);
            font-size: 0.75rem;
            color: #777777;
        }

        .standalone-footer-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .standalone-footer-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>

    @stack('styles')
</head>
<body class="standalone-pay-body">
    <!-- Cyber Background Elements -->
    <div class="cyber-grid" aria-hidden="true"></div>
    <div class="scanlines" aria-hidden="true"></div>

    <!-- Standalone Header Bar (No Sidebar or Admin Navigation) -->
    <header class="standalone-nav-header">
        <div class="standalone-nav-inner">
            <a href="{{ url('/') }}" class="standalone-brand">
                <div class="standalone-brand-icon">
                    <span>X/U</span>
                </div>
                <div class="standalone-brand-text">
                    <span class="standalone-brand-title">XUNDEFINED</span>
                    <span class="standalone-brand-sub">Crypto Payment Gateway</span>
                </div>
            </a>

            <div class="standalone-header-right">
                <div class="standalone-security-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>SSL 256-BIT ENCRYPTED</span>
                </div>

                @if (auth()->check())
                    <a href="{{ route('admin.dashboard') }}" class="standalone-nav-link">
                        <i class="fa-solid fa-solar-panel"></i>
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="standalone-nav-link">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Login</span>
                    </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Standalone Content -->
    <main class="standalone-content-wrap">
        @yield('content')
    </main>

    <!-- Standalone Clean Footer -->
    <footer class="standalone-footer">
        <div class="standalone-footer-inner">
            <div class="standalone-footer-meta">
                <span><i class="fa-solid fa-lock" style="color: #00ff66;"></i> COINPAYMENTS VERIFIED</span>
                <span>•</span>
                <span><i class="fa-solid fa-bolt" style="color: #ffaa00;"></i> AUTOMATED BLOCKCHAIN TELEMETRY</span>
                <span>•</span>
                <span><i class="fa-solid fa-network-wired"></i> SECURE DECENTRALIZED SETTLEMENT</span>
            </div>
            <div>
                &copy; {{ date('Y') }} Xundefined Project. All rights reserved.
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
