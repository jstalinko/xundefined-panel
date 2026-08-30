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

    <!-- Quill Rich Text Editor CDN -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
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
                    <div class="profile-info" style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                            <div class="profile-name" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                            <button 
                                type="button" 
                                class="cyber-btn cyber-btn-xs" 
                                style="padding: 2px 6px; font-size: 0.68rem; background: rgba(255, 23, 68, 0.12); border: 1px solid rgba(255, 23, 68, 0.35); color: var(--red-primary);"
                                onclick="openProfileEditModal()"
                                title="Edit Profile & Security"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
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

                    <div class="cyber-nav-item">
                        <a href="{{ route('invitecode.index') }}" class="cyber-nav-link {{ request()->routeIs('invitecode*') ? 'active' : '' }}">
                            <i class="fa-solid fa-key"></i>
                            <span>InviteCode</span>
                        </a>
                    </div>
                     <div class="cyber-nav-item">
                        <a href="{{ route('user.index') }}" class="cyber-nav-link {{ request()->routeIs('user*') ? 'active' : '' }}">
                            <i class="fa-solid fa-users"></i>
                            <span>Users</span>
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

    {{-- Edit Profile & Security Modal --}}
    @auth
    <div class="cyber-modal-backdrop" id="profileEditModalBackdrop">
        <div class="cyber-modal-window" style="max-width: 480px;">
            <div class="cyber-corner top-left"></div>
            <div class="cyber-corner top-right"></div>
            <div class="cyber-corner bottom-left"></div>
            <div class="cyber-corner bottom-right"></div>

            <div class="cyber-modal-header">
                <div class="cyber-modal-title">
                    <i class="fa-solid fa-user-pen" style="color: var(--red-primary);"></i>
                    <span>EDIT OPERATIVE PROFILE</span>
                </div>
                <button type="button" class="cyber-modal-close" onclick="closeProfileEditModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('dashboard.profile.update') }}" method="POST" id="profileEditForm">
                @csrf
                @method('PUT')

                <div class="cyber-modal-body">
                    {{-- Operative Name --}}
                    <div class="cyber-form-group">
                        <label class="cyber-label" for="profileNameInput">
                            <i class="fa-solid fa-id-card"></i> OPERATIVE HANDLE / NAME *
                        </label>
                        <input 
                            type="text" 
                            id="profileNameInput" 
                            name="name" 
                            class="cyber-input" 
                            value="{{ auth()->user()->name }}"
                            required
                        >
                    </div>

                    {{-- Password Section Notice --}}
                    <div style="margin-top: 14px; padding: 10px; background: rgba(255,255,255,0.03); border-left: 3px solid var(--red-primary); border-radius: var(--radius-sm); font-size: 0.75rem; color: var(--text-muted);">
                        <i class="fa-solid fa-lock" style="color: var(--red-primary); margin-right: 4px;"></i>
                        Leave password fields blank if you do not wish to update your security passcode.
                    </div>

                    {{-- New Password --}}
                    <div class="cyber-form-group" style="margin-top: 14px;">
                        <label class="cyber-label" for="profileNewPasswordInput">
                            <i class="fa-solid fa-key"></i> NEW PASSCODE (OPTIONAL)
                        </label>
                        <input 
                            type="password" 
                            id="profileNewPasswordInput" 
                            name="password" 
                            class="cyber-input" 
                            placeholder="Enter new passcode (min. 8 characters)"
                            oninput="toggleCurrentPasswordRequirement()"
                            autocomplete="new-password"
                        >
                    </div>

                    {{-- Current Password & Confirm Password Container --}}
                    <div id="passwordFieldsContainer" style="display: none; margin-top: 14px;">
                        <div class="cyber-form-group">
                            <label class="cyber-label" for="profileCurrentPasswordInput">
                                <i class="fa-solid fa-shield-halved"></i> CURRENT PASSCODE *
                            </label>
                            <input 
                                type="password" 
                                id="profileCurrentPasswordInput" 
                                name="current_password" 
                                class="cyber-input" 
                                placeholder="Enter current passcode to authorize change"
                                autocomplete="current-password"
                            >
                        </div>

                        <div class="cyber-form-group" style="margin-top: 14px;">
                            <label class="cyber-label" for="profilePasswordConfirmInput">
                                <i class="fa-solid fa-check-double"></i> CONFIRM NEW PASSCODE *
                            </label>
                            <input 
                                type="password" 
                                id="profilePasswordConfirmInput" 
                                name="password_confirmation" 
                                class="cyber-input" 
                                placeholder="Re-enter new passcode"
                                autocomplete="new-password"
                            >
                        </div>
                    </div>
                </div>

                <div class="cyber-modal-footer">
                    <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-md" onclick="closeProfileEditModal()">
                        CANCEL
                    </button>
                    <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-md">
                        <i class="fa-solid fa-floppy-disk"></i> SAVE PROFILE
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endauth

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

        // Profile Edit Modal Handlers
        function openProfileEditModal() {
            const backdrop = document.getElementById('profileEditModalBackdrop');
            if (backdrop) {
                backdrop.classList.add('active');
                document.body.style.overflow = 'hidden';
                const nameInput = document.getElementById('profileNameInput');
                if (nameInput) nameInput.focus();
            }
        }

        function closeProfileEditModal() {
            const backdrop = document.getElementById('profileEditModalBackdrop');
            if (backdrop) {
                backdrop.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function toggleCurrentPasswordRequirement() {
            const newPasswordInput = document.getElementById('profileNewPasswordInput');
            const currentPasswordInput = document.getElementById('profileCurrentPasswordInput');
            const confirmPasswordInput = document.getElementById('profilePasswordConfirmInput');
            const passwordFieldsContainer = document.getElementById('passwordFieldsContainer');

            if (!newPasswordInput || !passwordFieldsContainer) return;
            const hasNewPassword = newPasswordInput.value.trim().length > 0;
            passwordFieldsContainer.style.display = hasNewPassword ? 'block' : 'none';
            if (currentPasswordInput) currentPasswordInput.required = hasNewPassword;
            if (confirmPasswordInput) confirmPasswordInput.required = hasNewPassword;
        }

        document.addEventListener('click', function (e) {
            const backdrop = document.getElementById('profileEditModalBackdrop');
            if (backdrop && e.target === backdrop) {
                closeProfileEditModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeProfileEditModal();
            }
        });

        window.openProfileEditModal = openProfileEditModal;
        window.closeProfileEditModal = closeProfileEditModal;
        window.toggleCurrentPasswordRequirement = toggleCurrentPasswordRequirement;

        @if ($errors->has('name') || $errors->has('current_password') || $errors->has('password'))
        document.addEventListener('DOMContentLoaded', function () {
            openProfileEditModal();
            toggleCurrentPasswordRequirement();
        });
        @endif
    </script>

    <!-- Quill Rich Text Editor JS -->
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    @stack('scripts')
</body>
</html>
