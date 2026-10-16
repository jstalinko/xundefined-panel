@extends('layouts.dashboard')

@section('title', 'Manage Domains - Admin')
@section('page-title', 'REGISTERED DOMAINS')

@section('content')
{{-- Flash Status Notification --}}
@if (session('status'))
    <div class="cyber-alert" role="alert" style="border-color: var(--status-online); background: rgba(0, 255, 102, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-circle-check cyber-alert-icon" style="color: var(--status-online);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--status-online);">NOTIFICATION</span>
            <span class="cyber-alert-msg">{{ session('status') }}</span>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="cyber-alert" role="alert" style="border-color: var(--red-primary); background: rgba(255, 23, 68, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-triangle-exclamation cyber-alert-icon" style="color: var(--red-primary);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--red-primary);">SYSTEM ALERT</span>
            <span class="cyber-alert-msg">{{ session('error') }}</span>
        </div>
    </div>
@endif

{{-- Header Banner --}}
<div class="terminal-banner">
    <div class="terminal-banner-header">
        <div class="terminal-prompt">
            <span class="prompt-user">{{ strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name)) }}</span>
            <span class="prompt-separator">/</span>
            <span class="prompt-dir">admin</span>
            <span class="prompt-separator">/</span>
            <span class="prompt-dir">domains</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">list --registered-domains</span>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <span class="terminal-badge">TOTAL: {{ $stats['total'] }} DOMAINS</span>
            <span class="terminal-badge" style="border-color: rgba(0, 255, 102, 0.35); color: var(--status-online);">
                <i class="fa-solid fa-circle-check"></i> {{ $stats['active'] }} ACTIVE
            </span>
            <span class="terminal-badge" style="border-color: rgba(72, 202, 228, 0.35); color: #48cae4;">
                <i class="fa-solid fa-bolt"></i> {{ number_format($stats['total_hits']) }} HITS
            </span>
        </div>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">REGISTERED DOMAINS DIRECTORY</h1>
            <p class="welcome-subtext">
                Monitor all licensed domains bound to products, inspect owner Telegram profiles, track validation hits, and audit registration timestamps.
            </p>
        </div>

        {{-- Filter & Search Controls --}}
        <div class="filter-controls">
            <form method="GET" action="{{ route('domain.index') }}" style="display: flex; gap: 8px; flex: 1; max-width: 560px;">
                <div class="search-input-wrapper" style="flex: 1;">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input 
                        type="text" 
                        name="q"
                        id="domainClientSearch" 
                        class="tool-filter-input" 
                        placeholder="Search by domain, Telegram, user, product, IP..."
                        value="{{ request('q') }}"
                        autocomplete="off"
                    >
                    @if(request('q'))
                        <a href="{{ route('domain.index') }}" class="clear-search-btn" style="display: flex;" title="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @else
                        <button type="button" id="clearDomainSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    @endif
                </div>
                <button type="submit" class="cyber-btn cyber-btn-secondary" style="padding: 0 16px;" title="Search Domains">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Registered Domains Table Panel --}}
<div class="cyber-panel" style="margin-top: 20px;">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-globe"></i>
            REGISTERED DOMAINS MATRIX
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge-status b-ok">
                <span class="status-dot online"></span>
                <span id="domainCountBadge">{{ $domains->total() }} DOMAINS REGISTERED</span>
            </span>
        </div>
    </div>

    @if ($domains->isEmpty())
        <div class="empty-state" style="padding: 48px 20px;">
            <i class="fa-solid fa-network-wired empty-icon"></i>
            <div class="empty-title">NO DOMAINS REGISTERED</div>
            <p class="empty-desc">
                No registered domains were found matching your query.
            </p>
        </div>
    @else
        <div class="cyber-table-container">
            <table class="cyber-data-table" id="domainsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>DOMAIN</th>
                        <th>TELEGRAM</th>
                        <th>PRODUCT</th>
                        <th>STATUS</th>
                        <th>REGISTERED DATE</th>
                        <th style="text-align: right;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domains as $index => $dom)
                        <tr 
                            class="domain-row-item" 
                            data-id="{{ $dom->id }}" 
                            data-domain="{{ strtolower($dom->domain) }}" 
                            data-telegram="{{ strtolower(($dom->user->telegram_username ?? '') . ' ' . ($dom->user->telegram_id ?? '') . ' ' . ($dom->user->name ?? '')) }}" 
                            data-product="{{ strtolower(($dom->product->name ?? '') . ' ' . ($dom->product->pid ?? '')) }}"
                            data-ip="{{ strtolower($dom->ip_address ?? '') }}"
                        >
                            <td style="color: var(--text-muted); font-weight: 700; font-family: var(--font-mono);">
                                {{ sprintf('%02d', ($domains->currentPage() - 1) * $domains->perPage() + $index + 1) }}
                            </td>
                            <td>
                                <div class="domain-name-cell">
                                    <i class="fa-solid fa-globe" style="color: var(--red-primary); font-size: 0.9rem;"></i>
                                    <a href="https://{{ $dom->domain }}" target="_blank" rel="noopener noreferrer" style="font-weight: 700; color: #ffffff; text-decoration: none; font-family: var(--font-mono);">
                                        {{ $dom->domain }}
                                    </a>
                                    <button 
                                        type="button" 
                                        class="cyber-copy-btn" 
                                        style="padding: 2px 6px; font-size: 0.65rem;" 
                                        onclick="copyText('{{ $dom->domain }}', this)" 
                                        title="Copy domain name"
                                    >
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                                @if ($dom->ip_address || $dom->hits)
                                    <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                        @if ($dom->ip_address)
                                            <span>IP: {{ $dom->ip_address }}</span>
                                        @endif
                                        @if ($dom->ip_address && $dom->hits)
                                            <span>•</span>
                                        @endif
                                        @if ($dom->hits)
                                            <span style="color: #ffd166;">{{ number_format($dom->hits) }} hits</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($dom->user && $dom->user->telegram_username)
                                    <a href="https://t.me/{{ ltrim($dom->user->telegram_username, '@') }}" target="_blank" rel="noopener noreferrer" style="color: #48cae4; font-family: var(--font-mono); font-size: 0.8rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" title="Open Telegram Profile">
                                        <i class="fa-brands fa-telegram" style="color: #48cae4;"></i>
                                        t.me/{{ ltrim($dom->user->telegram_username, '@') }}
                                    </a>
                                    <div style="font-size: 0.73rem; color: var(--text-secondary); margin-top: 2px;">
                                        {{ $dom->user->name }}
                                    </div>
                                @elseif ($dom->user && $dom->user->telegram_id)
                                    <a href="https://t.me/{{ $dom->user->telegram_id }}" target="_blank" rel="noopener noreferrer" style="color: #48cae4; font-family: var(--font-mono); font-size: 0.78rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" title="Open Telegram ID">
                                        <i class="fa-brands fa-telegram" style="color: #48cae4;"></i>
                                        t.me/{{ $dom->user->telegram_id }}
                                    </a>
                                    <div style="font-size: 0.73rem; color: var(--text-secondary); margin-top: 2px;">
                                        {{ $dom->user->name }}
                                    </div>
                                @elseif ($dom->user)
                                    <div style="color: #ffffff; font-weight: 600; font-size: 0.82rem;">
                                        {{ $dom->user->name }}
                                    </div>
                                    <div style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.72rem;">
                                        No Telegram linked
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.76rem;">User #{{ $dom->user_id ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($dom->product)
                                    <a href="{{ route('product.show', $dom->product->id) }}" style="text-decoration: none;">
                                        <span class="badge-status b-ok" style="font-size: 0.74rem;">
                                            <i class="fa-solid fa-cube"></i> {{ $dom->product->name }}
                                        </span>
                                    </a>
                                    @if ($dom->product->pid)
                                        <div style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--text-muted); margin-top: 3px;">
                                            {{ $dom->product->pid }}
                                        </div>
                                    @endif
                                @else
                                    <span class="badge-status" style="background: rgba(255, 255, 255, 0.08); color: #ccc; font-size: 0.72rem;">
                                        Product #{{ $dom->product_id ?? 'N/A' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($dom->status === 'active')
                                    <span class="badge-status b-ok">
                                        <span class="status-dot online"></span>
                                        ACTIVE
                                    </span>
                                @else
                                    <span class="badge-status b-err">
                                        <span class="status-dot offline"></span>
                                        {{ strtoupper($dom->status ?: 'INACTIVE') }}
                                    </span>
                                @endif
                            </td>
                            <td style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.78rem;">
                                {{ $dom->created_at ? $dom->created_at->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                    <a 
                                        href="{{ route('domain.show', $dom->id) }}" 
                                        class="cyber-btn cyber-btn-primary cyber-btn-xs" 
                                        title="View Domain Details"
                                    >
                                        <i class="fa-solid fa-eye"></i> VIEW DETAILS
                                    </a>

                                    <form action="{{ route('domain.destroy', $dom->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete domain: {{ $dom->domain }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cyber-btn cyber-btn-xs" style="background: rgba(255, 23, 68, 0.15); border: 1px solid var(--red-primary); color: var(--red-primary);" title="Delete domain">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($domains->hasPages())
            <div style="margin-top: 20px; display: flex; justify-content: center;">
                {{ $domains->links() }}
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
    const domainSearchInput = document.getElementById('domainClientSearch');
    const clearDomainSearchBtn = document.getElementById('clearDomainSearchBtn');
    const domainRows = document.querySelectorAll('.domain-row-item');
    const domainCountBadge = document.getElementById('domainCountBadge');

    if (domainSearchInput) {
        domainSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let matches = 0;

            if (clearDomainSearchBtn) {
                clearDomainSearchBtn.style.display = query.length > 0 ? 'flex' : 'none';
            }

            domainRows.forEach(row => {
                const id = (row.getAttribute('data-id') || '').toLowerCase();
                const dom = (row.getAttribute('data-domain') || '').toLowerCase();
                const tg = (row.getAttribute('data-telegram') || '').toLowerCase();
                const prod = (row.getAttribute('data-product') || '').toLowerCase();
                const ip = (row.getAttribute('data-ip') || '').toLowerCase();

                if (id === query || dom.includes(query) || tg.includes(query) || prod.includes(query) || ip.includes(query)) {
                    row.style.display = '';
                    matches++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (domainCountBadge) {
                domainCountBadge.textContent = matches + ' DOMAINS REGISTERED';
            }
        });
    }

    if (clearDomainSearchBtn) {
        clearDomainSearchBtn.addEventListener('click', function () {
            domainSearchInput.value = '';
            domainSearchInput.dispatchEvent(new Event('input'));
            domainSearchInput.focus();
        });
    }
</script>
@endpush
