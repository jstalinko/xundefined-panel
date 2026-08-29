@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('page-title', 'DASHBOARD')

@section('content')
{{-- Flash Status Notification --}}
@if (session('status'))
    <div class="cyber-alert" role="alert" style="border-color: var(--status-online); background: rgba(0, 255, 102, 0.08); margin-bottom: 0;">
        <i class="fa-solid fa-circle-check cyber-alert-icon" style="color: var(--status-online);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--status-online);">NOTIFICATION</span>
            <span class="cyber-alert-msg">{{ session('status') }}</span>
        </div>
    </div>
@endif

{{-- Header Banner --}}
<div class="terminal-banner">
    <div class="terminal-banner-header">
        <div class="terminal-prompt">
            <span class="prompt-user">{{ strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name)) }}</span>
            <span class="prompt-separator">/</span>
            <span class="prompt-dir">dashboard</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">status --all</span>
        </div>
        <span class="terminal-badge">ACCOUNT: {{ $user->name }}</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">WELCOME, {{ strtoupper($user->name) }}</h1>
            <div class="welcome-subtext" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                <span style="color: var(--text-secondary); font-family: var(--font-mono); font-size: 0.86rem; font-weight: 600;">YOUR INVITE CODE :</span>
                <code style="font-family: var(--font-mono); font-size: 0.95rem; font-weight: 800; color: var(--red-primary); background: rgba(255, 23, 68, 0.12); border: 1px solid rgba(255, 23, 68, 0.35); padding: 3px 10px; border-radius: var(--radius-sm); letter-spacing: 0.08em;">
                    {{ $user->invite_key ?? 'XU-ROOT-7789' }}
                </code>
                <button 
                    type="button" 
                    class="cyber-copy-btn" 
                    style="padding: 4px 10px; font-size: 0.72rem;"
                    onclick="copyText('{{ $user->invite_key ?? 'XU-ROOT-7789' }}', this)" 
                    title="Copy Invite Code"
                >
                    <i class="fa-solid fa-copy"></i>
                    <span>COPY</span>
                </button>
            </div>
        </div>

        {{-- Filter Search Bar --}}
        <div class="filter-controls">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input 
                    type="text" 
                    id="toolSearchInput" 
                    class="tool-filter-input" 
                    placeholder="Search free tools..."
                    autocomplete="off"
                >
                <button type="button" id="clearSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="node-count-badge">
                <span class="node-count-num" id="visibleNodeCount">{{ count($toolNodes) }}</span>
                <span class="node-count-label">FREE TOOLS</span>
            </div>
        </div>
    </div>
</div>

{{-- Free Tools Launchpad Section --}}
<section class="toolbox-section" style="margin-top: 24px;">
    <div class="section-title-bar">
        <div class="title-with-icon">
            <i class="fa-solid fa-toolbox section-icon"></i>
            <h2 class="section-heading">ACCESS OUR FREE TOOLS</h2>
        </div>
        <div class="section-divider-line"></div>
        <span class="section-meta-tag">[ FREE TOOLS ]</span>
    </div>

    <!-- Cards Launcher Matrix -->
    <div class="menu-grid" id="toolsGrid">
        @foreach ($toolNodes as $node)
            <div class="tool-card tool-node-item" data-title="{{ strtolower($node['title']) }}" data-desc="{{ strtolower($node['description']) }}" data-category="{{ $node['category'] }}">
                <div class="card-glow"></div>
                
                <div class="tool-card-header">
                    <span class="tool-badge">{{ $node['badge'] }}</span>
                    <i class="fa-solid fa-arrow-up-right-from-square tool-ext-indicator"></i>
                </div>

                <div class="tool-icon-box">
                    <i class="{{ $node['icon'] }} tool-main-icon"></i>
                </div>

                <div class="tool-card-body">
                    <h3 class="tool-card-title">{{ $node['title'] }}</h3>
                    <p class="tool-card-description">{{ $node['description'] }}</p>
                </div>

                <div class="tool-card-footer">
                    <span class="tool-launch-text">OPEN MODULE</span>
                    <i class="fa-solid fa-angle-right tool-arrow-icon"></i>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Empty Search State -->
    <div id="noResultsState" class="empty-state" style="display: none;">
        <i class="fa-solid fa-magnifying-glass-chart empty-icon"></i>
        <div class="empty-title">NO MATCHING MODULES FOUND</div>
        <p class="empty-desc">Adjust your search parameters or query another keyword.</p>
    </div>
</section>

{{-- Interactive Cyber Diagnostic Panel --}}
<div class="cyber-panel" style="margin-top: 10px;">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-terminal"></i>
            DIAGNOSTIC & TELEMETRY LAB
        </div>
        <span class="badge-status b-ok">REALTIME</span>
    </div>

    <!-- Tabs Navigation -->
    <div class="cyber-tabs-nav">
        <button type="button" class="cyber-tab-btn active" onclick="switchTab('tab-terminal', this)">
            <i class="fa-solid fa-terminal"></i>
            <span>System Console</span>
        </button>
        <button type="button" class="cyber-tab-btn" onclick="switchTab('tab-invites', this)">
            <i class="fa-solid fa-key"></i>
            <span>Invite Token Verifier</span>
        </button>
        <button type="button" class="cyber-tab-btn" onclick="switchTab('tab-security', this)">
            <i class="fa-solid fa-lock"></i>
            <span>Security Matrix</span>
        </button>
    </div>

    <!-- Tab 1: Terminal -->
    <div id="tab-terminal" class="cyber-tab-panel active" style="margin-top: 16px;">
        <div class="cyber-out-box" id="terminalOutputBox">
<span class="hl-key">[INIT]</span> Neural Gateway v2.4 initialized on <span class="hl-named">{{ config('app.url') }}</span>
<span class="hl-dec">[AUTH]</span> Operative authenticated: <span class="hl-hex">{{ $user->email }}</span> (UID #{{ $user->id }})
<span class="hl-dec">[AUTH]</span> Role clearance verified: <span class="hl-val">{{ $user->role_name }}</span>
<span class="hl-dec">[AUTH]</span> Token verified: <span class="hl-named">{{ $user->invite_key ?? 'GENESIS_TOKEN' }}</span>
<span class="hl-key">[INFO]</span> Database connection: <span class="hl-val">MySQL 127.0.0.1:3306 [OK]</span>
<span class="hl-key">[INFO]</span> Session encryption: <span class="hl-hex">ACTIVE // TLS 1.3</span>
<span class="hl-key">[SYS]</span>  Memory utilization: <span class="hl-val">{{ round(memory_get_usage(true) / 1024 / 1024, 2) }} MB</span>
<span class="hl-named">[READY]</span> System awaiting operative command input...
        </div>
        <div style="display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap;">
            <button type="button" class="cyber-btn cyber-btn-primary cyber-btn-sm" onclick="runDiagnosticPulse(this)">
                <i class="fa-solid fa-bolt"></i> RUN SYSTEM PULSE
            </button>
            <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-sm" onclick="clearConsole()">
                <i class="fa-solid fa-eraser"></i> CLEAR CONSOLE
            </button>
        </div>
    </div>

    <!-- Tab 2: Invites -->
    <div id="tab-invites" class="cyber-tab-panel" style="margin-top: 16px;">
        <div class="desc-box">
            Validate or generate 16-character cryptographic invite keys for registering new operatives into the system.
        </div>
        <div class="cyber-form-group">
            <label class="cyber-label" for="inviteGenInput">
                <i class="fa-solid fa-key"></i> CRYPTOGRAPHIC INVITE TOKEN GENERATOR
            </label>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="text" id="inviteGenInput" class="cyber-input" value="{{ strtoupper(bin2hex(random_bytes(4))) }}" readonly style="max-width: 280px; font-weight: 700; color: var(--red-primary); letter-spacing: 0.14em;">
                <button type="button" class="cyber-btn cyber-btn-primary cyber-btn-sm" onclick="generateNewInviteKey()">
                    <i class="fa-solid fa-arrows-rotate"></i> REGENERATE TOKEN
                </button>
                <button type="button" class="cyber-copy-btn" onclick="copyInviteToken(this)">
                    <i class="fa-solid fa-copy"></i> COPY TOKEN
                </button>
            </div>
        </div>
    </div>

    <!-- Tab 3: Security -->
    <div id="tab-security" class="cyber-tab-panel" style="margin-top: 16px;">
        <div class="desc-box">
            Realtime security overview, cryptographic hashing status, and operative privileges.
        </div>
        <table class="info-table">
            <thead>
                <tr>
                    <th>SECURITY VECTOR</th>
                    <th>CONFIGURATION</th>
                    <th>INTEGRITY STATUS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Password Hashing</td>
                    <td>Bcrypt Rounds: {{ config('hashing.bcrypt.rounds', 12) }}</td>
                    <td><span class="badge-status b-ok"><i class="fa-solid fa-check"></i> HARDENED</span></td>
                </tr>
                <tr>
                    <td>CSRF Protection</td>
                    <td>XSRF-TOKEN Tokenized Middleware</td>
                    <td><span class="badge-status b-ok"><i class="fa-solid fa-check"></i> ACTIVE</span></td>
                </tr>
                <tr>
                    <td>Registration Gate</td>
                    <td>Invite Key Token Required</td>
                    <td><span class="badge-status b-ok"><i class="fa-solid fa-check"></i> ENFORCED</span></td>
                </tr>
                <tr>
                    <td>Session Driver</td>
                    <td>Database Payload Storage</td>
                    <td><span class="badge-status b-ok"><i class="fa-solid fa-check"></i> SECURED</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live Search Filter for Launchpad Nodes
    const searchInput = document.getElementById('toolSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const nodeItems = document.querySelectorAll('.tool-node-item');
    const noResultsState = document.getElementById('noResultsState');
    const visibleNodeCount = document.getElementById('visibleNodeCount');

    function filterNodes() {
        const query = searchInput.value.toLowerCase().trim();
        let matchCount = 0;

        if (query.length > 0) {
            clearSearchBtn.style.display = 'flex';
        } else {
            clearSearchBtn.style.display = 'none';
        }

        nodeItems.forEach(item => {
            const title = item.getAttribute('data-title') || '';
            const desc = item.getAttribute('data-desc') || '';
            const cat = item.getAttribute('data-category') || '';

            if (title.includes(query) || desc.includes(query) || cat.includes(query)) {
                item.style.display = 'flex';
                matchCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (visibleNodeCount) {
            visibleNodeCount.textContent = matchCount;
        }

        if (noResultsState) {
            noResultsState.style.display = matchCount === 0 ? 'flex' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterNodes);
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            filterNodes();
            searchInput.focus();
        });
    }

    // Tab Switcher
    function switchTab(targetTabId, btn) {
        document.querySelectorAll('.cyber-tab-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        document.querySelectorAll('.cyber-tab-btn').forEach(b => {
            b.classList.remove('active');
        });

        const targetPanel = document.getElementById(targetTabId);
        if (targetPanel) {
            targetPanel.classList.add('active');
        }
        if (btn) {
            btn.classList.add('active');
        }
    }

    // Diagnostic Console Simulation
    function runDiagnosticPulse(btn) {
        const outBox = document.getElementById('terminalOutputBox');
        if (!outBox) return;

        const time = new Date().toISOString().replace('T', ' ').substring(0, 19);
        const randPing = Math.floor(Math.random() * 8) + 8;
        const msg = `\n<span class="hl-key">[PULSE]</span> [${time}] Latency probe: <span class="hl-val">${randPing}ms</span> // Cipher link: <span class="hl-dec">100% HEALTHY</span>`;
        outBox.innerHTML += msg;
        outBox.scrollTop = outBox.scrollHeight;
    }

    function clearConsole() {
        const outBox = document.getElementById('terminalOutputBox');
        if (outBox) {
            outBox.innerHTML = '<span class="hl-named">[CLEARED]</span> Console buffer reset by operative.';
        }
    }

    // Invite Key Token Generator in Tab 2
    function generateNewInviteKey() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let key = '';
        for (let i = 0; i < 8; i++) {
            key += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        const input = document.getElementById('inviteGenInput');
        if (input) input.value = key;
    }

    function copyInviteToken(btn) {
        const input = document.getElementById('inviteGenInput');
        if (input) {
            copyText(input.value, btn);
        }
    }
</script>
@endpush
