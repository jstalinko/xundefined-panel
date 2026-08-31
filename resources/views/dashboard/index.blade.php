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
            <div class="tool-card tool-node-item" data-title="{{ strtolower($node['title']) }}" data-desc="{{ strtolower($node['description']) }}" data-category="{{ $node['category'] }}" onclick="window.open('{{ $node['route'] }}', '_blank', 'noopener,noreferrer')">
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

{{-- Cyber Activity Logs Panel --}}
<div class="cyber-panel" style="margin-top: 10px;">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-list-check"></i>
            ACTIVITY LOGS
        </div>
        <span class="badge-status b-ok">REALTIME</span>
    </div>

    <div style="margin-top: 16px;">
        <div class="cyber-out-box" id="terminalOutputBox" style="max-height: 320px; overflow-y: auto; font-family: var(--font-mono); font-size: 0.8rem; line-height: 1.6; white-space: normal;">
            <div style="color: var(--text-muted); margin-bottom: 6px;"><span class="hl-key">[INIT]</span> Activity Telemetry Engine initialized // Showing latest mainframe events</div>
            @forelse ($activityLogs as $log)
                @php
                    $typeUpper = strtoupper($log->type ?? 'SYS');
                    $badgeClass = match(strtolower($log->type ?? '')) {
                        'auth' => 'hl-dec',
                        'domain' => 'hl-key',
                        'account' => 'hl-val',
                        default => 'hl-named',
                    };
                @endphp
                <div style="margin-bottom: 4px;"><span style="color: var(--text-muted);">[{{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s') }}]</span> <span class="{{ $badgeClass }}">[{{ $typeUpper }}]</span> @if ($log->user)<span class="hl-named">{{ $log->user->name }}</span>: @endif<span style="color: #e2e8f0;">{{ $log->description }}</span></div>
            @empty
                <div style="color: var(--text-muted);"><span class="hl-val">[EMPTY]</span> No activity logs recorded in mainframe yet.</div>
            @endforelse
        </div>
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
</script>
@endpush
