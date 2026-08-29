<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>XUNDEFINED PROJECT // By XingZhang Labs</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=JetBrains+Mono:wght@400;600;700;800;900&family=Orbitron:wght@600;800;900&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Free CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Xundefined Cyber Design System -->
    <link rel="stylesheet" href="{{ asset('app.css') }}">

    <style>
        body.welcome-cyber-bg {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #050406;
            background-image: 
                radial-gradient(circle at 50% 50%, rgba(255, 23, 68, 0.12) 0%, transparent 65%),
                linear-gradient(rgba(255, 23, 68, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 23, 68, 0.03) 1px, transparent 1px);
            background-size: 100% 100%, 36px 36px, 36px 36px;
            color: #ffffff;
            font-family: var(--font-mono, 'JetBrains Mono', monospace);
            overflow: hidden;
            position: relative;
        }

        .welcome-center-container {
            text-align: center;
            z-index: 10;
            padding: 40px 24px;
            max-width: 900px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            animation: fadeInWelcome 1s ease-out;
        }

        .welcome-big-title {
            font-family: 'Orbitron', 'JetBrains Mono', var(--font-mono, monospace);
            font-size: clamp(2.5rem, 7vw, 5.2rem);
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #ffffff;
            margin: 0 0 16px 0;
            line-height: 1.1;
            text-shadow: 
                0 0 20px rgba(255, 23, 68, 0.6),
                0 0 40px rgba(255, 23, 68, 0.3),
                0 0 80px rgba(255, 23, 68, 0.15);
            position: relative;
        }

        .welcome-big-title .glow-red {
            color: var(--red-primary, #ff1744);
            text-shadow: 
                0 0 25px rgba(255, 23, 68, 0.9),
                0 0 50px rgba(255, 23, 68, 0.5);
        }

        .welcome-subtitle {
            font-family: var(--font-mono, 'JetBrains Mono', monospace);
            font-size: clamp(1rem, 2.2vw, 1.45rem);
            font-weight: 600;
            letter-spacing: 0.2em;
            color: #d0d0d0;
            text-transform: uppercase;
            margin: 0 0 36px 0;
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .welcome-subtitle .author-name {
            color: var(--red-primary, #ff1744);
            font-weight: 800;
        }

        .welcome-nav-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .welcome-enter-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--red-primary, #ff1744), var(--red-hover, #d50000));
            color: #ffffff;
            font-family: var(--font-mono, 'JetBrains Mono', monospace);
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: var(--radius-sm, 2px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(255, 23, 68, 0.45);
            transition: all 0.2s ease;
        }

        .welcome-enter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(255, 23, 68, 0.7);
            color: #ffffff;
        }

        .welcome-secondary-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-secondary, #b8b8b8);
            font-family: var(--font-mono, 'JetBrains Mono', monospace);
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: var(--radius-sm, 2px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }

        .welcome-secondary-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 23, 68, 0.4);
            color: #ffffff;
        }

        .welcome-tagline-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 14px;
            background: rgba(255, 23, 68, 0.1);
            border: 1px solid rgba(255, 23, 68, 0.3);
            border-radius: var(--radius-full, 9999px);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            color: var(--red-primary, #ff1744);
            margin-bottom: 24px;
        }

        @keyframes fadeInWelcome {
            from {
                opacity: 0;
                transform: scale(0.96) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
</head>
<body class="welcome-cyber-bg">
    <!-- Cyber Background Elements from app.css -->
    <div class="cyber-grid" aria-hidden="true"></div>
    <div class="scanlines" aria-hidden="true"></div>

    <!-- Centered Content -->
    <main class="welcome-center-container">
        <div class="welcome-tagline-badge">
            <span class="status-dot online" style="width: 6px; height: 6px; background: var(--red-primary, #ff1744);"></span>
            <span>DEFINED BY NOTHING. KNOWN BY NO ONE.</span>
        </div>

        <h1 class="welcome-big-title">
            <span class="glow-red">XUNDEFINED</span> PROJECT
        </h1>

        <div class="welcome-subtitle">
            <span>By <span class="author-name">XingZhang Labs.</span></span>
        </div>

        <div class="welcome-nav-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="welcome-enter-btn">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>ENTER DASHBOARD</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="welcome-enter-btn">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>LOGIN</span>
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="welcome-secondary-btn">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>REGISTER</span>
                    </a>
                @endif
            @endauth
        </div>
    </main>
</body>
</html>
