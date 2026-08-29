<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Authentication') // Xundefined</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Free CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Xundefined Cyber Red Design System -->
    <link rel="stylesheet" href="{{ asset('app.css') }}">
</head>
<body class="login-body">
    <!-- Cyber Background Elements -->
    <div class="cyber-grid" aria-hidden="true"></div>
    <div class="scanlines" aria-hidden="true"></div>

    <div class="login-wrapper">
        <div class="login-card-container">
            <!-- Cyber Corner Accents -->
            <div class="cyber-corner top-left" aria-hidden="true"></div>
            <div class="cyber-corner top-right" aria-hidden="true"></div>
            <div class="cyber-corner bottom-left" aria-hidden="true"></div>
            <div class="cyber-corner bottom-right" aria-hidden="true"></div>

            <!-- Page Content -->
            @yield('content')

            <!-- Global Auth Footer -->
            <footer class="login-footer">
                <div class="footer-meta">
                    <span class="meta-item"><i class="fa-solid fa-shield-halved"></i> SECURE PROTOCOL</span>
                    <span>•</span>
                    <span class="meta-item"><i class="fa-solid fa-computer"></i> {{ request()->ip() ?? '127.0.0.1' }}</span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Password visibility toggle script -->
    <script>
        function togglePass(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
