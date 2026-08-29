<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') // Xundefined </title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Free CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Xundefined Cyber Red Design System -->
    <link rel="stylesheet" href="{{ asset('app.css') }}">
    @stack('styles')
</head>
<body>
    <!-- Cyber Background Elements -->
    <div class="cyber-grid" aria-hidden="true"></div>
    <div class="scanlines" aria-hidden="true"></div>
    <div class="cyber-sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <div class="cyber-app-layout">
        <!-- Cyber Sidebar -->
        <aside class="cyber-sidebar" id="cyberSidebar">
            <!-- Sidebar Brand Header -->
            <div class="cyber-sidebar-header">
                <a href="{{ route('dashboard') }}" class="cyber-sidebar-brand">
                    <div class="cyber-brand-icon">
                        <span>X/U</span>
                    </div>
                    <div class="cyber-brand-text">
                        <span class="cyber-brand-name">XUNDEFINED</span>
                        <span class="cyber-brand-tag">XingZheng Labs</span>
                    </div>
                </a>
                <button type="button" class="cyber-sidebar-close" id="sidebarCloseBtn" aria-label="Close sidebar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Operative Profile Card -->
            <div class="cyber-sidebar-profile">
                <div class="profile-identity-row">
                    <div class="profile-avatar-box">
                        <span>{{ strtoupper(substr(auth()->user()->name ?? 'O', 0, 1)) }}</span>
                        <span class="profile-status-indicator" title="Unit Online"></span>
                    </div>
                    <div class="profile-info">
                        <div class="profile-name" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                        <div class="profile-role-badge">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>{{ auth()->user()->role == 1 ? 'Admin' : 'Member' }}</span>
                        </div>
                    </div>
                </div>
                <div class="profile-meta-row">
                    <span class="profile-key-label">INVITE CODE:</span>
                    <span class="profile-key-val">{{ auth()->user()->invite_key ?? 'GENESIS' }}</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="cyber-sidebar-nav">
                <div class="nav-section-title">MAIN MENU</div>
                
                <div class="cyber-nav-item">
                    <a href="{{ route('dashboard') }}" class="cyber-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-home"></i>
                        <span>xDashboard</span>
                    </a>
                </div>

                <div class="cyber-nav-item">
                    <a href="{{ route('dashboard.download') }}" class="cyber-nav-link {{ request()->routeIs('dashboard.download*') ? 'active' : '' }}">
                        <i class="fa-solid fa-download"></i>
                        <span>xDownload</span>
                    </a>
                </div>

                <div class="cyber-nav-item">
                    <a href="{{ route('dashboard.domain') }}" class="cyber-nav-link {{ request()->routeIs('dashboard.domain*') ? 'active' : '' }}">
                        <i class="fa-solid fa-globe"></i>
                        <span>xDomain</span>
                    </a>
                </div>

                <div class="cyber-nav-item">
                    <a href="{{ route('dashboard.store') }}" class="cyber-nav-link {{ request()->routeIs('dashboard.store*') ? 'active' : '' }}">
                        <i class="fa-solid fa-shopping-cart"></i>
                        <span>xStore</span>
                    </a>
                </div>
                <div class="cyber-nav-item">
                    <a href="{{ route('dashboard.notes') }}" class="cyber-nav-link {{ request()->routeIs('dashboard.notes*') ? 'active' : '' }}">
                        <i class="fa-solid fa-note-sticky"></i>
                        <span>xNotes</span>
                    </a>
                </div>

                @if (auth()->check() && (int) auth()->user()->role === 1)
                    <div class="nav-section-title">ADMINISTRATION</div>

                    <div class="cyber-nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="cyber-nav-link {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                            <i class="fa-solid fa-solar-panel"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <div class="cyber-nav-item">
                        <a href="{{ route('product.index') }}" class="cyber-nav-link {{ request()->routeIs('product*') ? 'active' : '' }}">
                            <i class="fa-solid fa-box-archive"></i>
                            <span>Products</span>
                        </a>
                    </div>

                    <div class="cyber-nav-item">
                        <a href="{{ route('order.index') }}" class="cyber-nav-link {{ request()->routeIs('order*') ? 'active' : '' }}">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span>Orders</span>
                        </a>
                    </div>

                    <div class="cyber-nav-item">
                        <a href="{{ route('post.index') }}" class="cyber-nav-link {{ request()->routeIs('post*') ? 'active' : '' }}">
                            <i class="fa-solid fa-newspaper"></i>
                            <span>Posts</span>
                        </a>
                    </div>
                @endif

                <div class="nav-section-title">OUR LINKS</div>

                <div class="cyber-nav-item">
                    <a href="https://t.me/x3344677" target="_blank" class="cyber-nav-link">
                        <i class="fa-solid fa-fire-burner"></i>
                        <span>Contact Us</span>
                    </a>
                </div>

                <div class="cyber-nav-item">
                    <a href="https://t.me/undefinxed" target="_blank" class="cyber-nav-link">
                        <i class="fa-solid fa-id-card-clip"></i>
                        <span>xChitChat</span>
                    </a>
                </div>

                <div class="cyber-nav-item">
                    <a href="https://t.me/xdevlogs" target="_blank" class="cyber-nav-link">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Channels</span>
                    </a>
                </div>
                

            </nav>

            <!-- Sidebar Footer with Logout -->
            <div class="cyber-sidebar-footer">
                <div class="sidebar-system-telemetry">
                    <span><i class="fa-solid fa-computer" style="color: var(--status-online); margin-right: 4px;"></i> IP: {{ request()->ip() ?? '127.0.0.1' }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="sidebar-logout-form">
                    @csrf
                    <button type="submit" class="cyber-sidebar-logout">
                        <i class="fa-solid fa-arrow-right-from-bracket" style="color: var(--red-primary);"></i>
                        <span>LOGOUT</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="cyber-main-wrapper">
            <!-- Topbar -->
            <header class="cyber-topbar">
                <div class="topbar-left">
                    <button type="button" class="cyber-menu-toggle" id="sidebarToggleBtn" aria-label="Toggle navigation drawer">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="topbar-breadcrumbs">
                        <span class="crumb-root">MAINFRAME</span>
                        <span class="crumb-separator">/</span>
                        <span class="crumb-current">@yield('page-title', 'DASHBOARD')</span>
                    </div>
                </div>

                <div class="topbar-right">

                    <div class="live-clock-widget">
                        <i class="fa-regular fa-clock"></i>
                        <span id="topbarClock">--:--:-- UTC</span>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="cyber-content-area">
                @yield('content')
            </main>

            <!-- App Footer -->
            <footer class="app-footer">
                <div class="footer-container">
                    <div class="footer-left">
                        <span class="footer-brand"><i class="fa-solid fa-shield-virus"></i> XUNDEFINED PROJECT</span>
                        <span class="footer-slash">//</span>
                        <span class="footer-tagline"> by XingZheng Labs.</span>
                    </div>
                    
                </div>
            </footer>
        </div>
    </div>

    <!-- Global Cyber UI Scripts -->
    <script>
        // Live UTC Clock
        function updateCyberClock() {
            const clockEl = document.getElementById('topbarClock');
            if (clockEl) {
                const now = new Date();
                const hours = String(now.getUTCHours()).padStart(2, '0');
                const minutes = String(now.getUTCMinutes()).padStart(2, '0');
                const seconds = String(now.getUTCSeconds()).padStart(2, '0');
                clockEl.textContent = `${hours}:${minutes}:${seconds} UTC`;
            }
        }
        setInterval(updateCyberClock, 1000);
        updateCyberClock();

        // Responsive Sidebar Drawer Toggles
        const sidebar = document.getElementById('cyberSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const closeBtn = document.getElementById('sidebarCloseBtn');

        function openSidebar() {
            if (sidebar) sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);

        // Copy Helper
        function copyText(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                if (btn) {
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> COPIED';
                    btn.classList.add('copied');
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('copied');
                    }, 2000);
                }
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
