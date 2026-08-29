@extends('layouts.dashboard')

@section('title', 'Domain Management')
@section('page-title', 'DOMAINS')

@section('content')
{{-- Flash Status Notifications --}}
@if (session('status'))
    <div class="cyber-alert" role="alert" style="border-color: var(--status-online); background: rgba(0, 255, 102, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-circle-check cyber-alert-icon" style="color: var(--status-online);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--status-online);">SUCCESS</span>
            <span class="cyber-alert-msg">{{ session('status') }}</span>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="cyber-alert" role="alert" style="border-color: var(--red-primary); background: rgba(255, 23, 68, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-triangle-exclamation cyber-alert-icon" style="color: var(--red-primary);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--red-primary);">ERROR</span>
            <span class="cyber-alert-msg">{{ session('error') }}</span>
        </div>
    </div>
@endif

@if (isset($errors) && $errors->any())
    <div class="cyber-alert" role="alert" style="border-color: var(--red-primary); background: rgba(255, 23, 68, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-circle-xmark cyber-alert-icon" style="color: var(--red-primary);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--red-primary);">VALIDATION ERROR</span>
            <ul style="margin: 4px 0 0 16px; font-family: var(--font-mono); font-size: 0.78rem; color: #ff859b;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- Header Banner --}}
<div class="terminal-banner">
    <div class="terminal-banner-header">
        <div class="terminal-prompt">
            <span class="prompt-user">{{ strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name)) }}</span>
            <span class="prompt-separator">/</span>
            <span class="prompt-dir">domains</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">list --domains</span>
        </div>
        <span class="terminal-badge">ACCOUNT: {{ $user->name }}</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">DOMAIN MANAGEMENT</h1>
            <p class="welcome-subtext">
                Manage your registered domains and connect them to your products.
            </p>
        </div>

        {{-- Filter & Action Controls --}}
        <div class="filter-controls">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input 
                    type="text" 
                    id="domainSearchInput" 
                    class="tool-filter-input" 
                    placeholder="Search registered domains (e.g. domain.com)..."
                    autocomplete="off"
                >
                <button type="button" id="clearDomainSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <button type="button" class="cyber-btn cyber-btn-primary" onclick="openRegisterDomainModal()">
                <i class="fa-solid fa-plus"></i>
                <span>REGISTER DOMAIN</span>
            </button>
        </div>
    </div>
</div>

{{-- Product Domain Quota Overview Cards --}}
@if (isset($productStats) && $productStats->isNotEmpty())
<div class="cyber-panel" style="margin-bottom: 24px;">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-chart-pie" style="color: var(--red-primary);"></i>
            PRODUCT DOMAIN QUOTAS
        </div>
        <span class="badge-status b-ok" style="font-size: 0.72rem;">
            <i class="fa-solid fa-shield-halved"></i> {{ $productStats->count() }} ACTIVE PRODUCTS
        </span>
    </div>
    <div style="padding: 16px 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px;">
            @foreach ($productStats as $stat)
                @php
                    $isFull = $stat['used'] >= $stat['quota'];
                    $barColor = $isFull ? 'var(--red-primary)' : 'var(--cyan-glow, #00f0ff)';
                @endphp
                <div style="background: rgba(15, 17, 23, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-sm); padding: 14px 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="font-family: var(--font-mono); font-size: 0.82rem; font-weight: 700; color: #fff; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 10px;">
                            <i class="fa-solid fa-cube" style="color: var(--red-primary); margin-right: 6px;"></i>
                            {{ $stat['product_name'] }}
                        </div>
                        <div style="font-family: var(--font-mono); font-size: 0.78rem; font-weight: 700; color: {{ $barColor }}; white-space: nowrap;">
                            {{ $stat['used'] }} / {{ $stat['quota'] }}
                        </div>
                    </div>
                    <div style="width: 100%; height: 4px; background: rgba(255, 255, 255, 0.08); border-radius: 2px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $stat['percentage'] }}%; background: {{ $barColor }}; border-radius: 2px;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Domain Records Table Panel --}}
<div class="cyber-panel">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-network-wired"></i>
            REGISTERED DOMAINS
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge-status b-ok">
                <span class="status-dot online"></span>
                <span id="domainCounterTag">{{ $domains->count() }} DOMAINS FOUND</span>
            </span>
            <button type="button" class="cyber-btn cyber-btn-primary cyber-btn-sm" onclick="openRegisterDomainModal()">
                <i class="fa-solid fa-globe"></i> REGISTER DOMAIN
            </button>
        </div>
    </div>

    @if ($domains->isEmpty())
        {{-- Empty State --}}
        <div class="empty-state" style="padding: 40px 20px;">
            <i class="fa-solid fa-globe empty-icon"></i>
            <div class="empty-title">NO DOMAINS REGISTERED YET</div>
            <p class="empty-desc">
                No active domains were found for your account. Register your first domain to connect it with your products.
            </p>
            <button type="button" class="cyber-btn cyber-btn-primary cyber-btn-md" onclick="openRegisterDomainModal()" style="margin-top: 10px;">
                <i class="fa-solid fa-plus"></i> REGISTER YOUR FIRST DOMAIN
            </button>
        </div>
    @else
        {{-- Domain Table --}}
        <div class="cyber-table-container">
            <table class="cyber-data-table" id="domainsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>DOMAIN</th>
                        <th>PRODUCT</th>
                        <th>USER</th>
                        <th>HITS</th>
                        <th>STATUS</th>
                        <th>REGISTERED DATE</th>
                        <th style="text-align: right;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domains as $index => $domain)
                        <tr class="domain-row-item" data-domain="{{ strtolower($domain->domain) }}" data-product="{{ strtolower($domain->product->name ?? 'General Node') }}">
                            <td style="color: var(--text-muted); font-weight: 700;">
                                {{ sprintf('%02d', $index + 1) }}
                            </td>
                            <td>
                                <div class="domain-name-cell">
                                    <i class="fa-solid fa-globe" style="color: var(--red-primary); font-size: 0.9rem;"></i>
                                    <a href="https://{{ $domain->domain }}" target="_blank" rel="noopener noreferrer">
                                        {{ $domain->domain }}
                                    </a>
                                    <button 
                                        type="button" 
                                        class="cyber-copy-btn" 
                                        style="padding: 2px 6px; font-size: 0.65rem;" 
                                        onclick="copyText('{{ $domain->domain }}', this)" 
                                        title="Copy domain name"
                                    >
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                @if ($domain->product)
                                    <span class="badge-status b-ok" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-cube"></i> {{ $domain->product->name }}
                                    </span>
                                @else
                                    <span class="badge-status" style="background: rgba(255, 255, 255, 0.08); color: #ccc;">
                                        <i class="fa-solid fa-cube"></i> Product #{{ $domain->product_id }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span style="font-family: var(--font-mono); color: var(--text-secondary);">
                                    {{ $user->name }} (#{{ $domain->user_id }})
                                </span>
                            </td>
                            <td>
                                <span class="badge-status" style="background: rgba(0, 240, 255, 0.1); border: 1px solid var(--cyan-glow, #00f0ff); color: var(--cyan-glow, #00f0ff); font-family: var(--font-mono); font-size: 0.72rem;">
                                    <i class="fa-solid fa-bolt"></i> {{ number_format($domain->hits ?? 0) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-status b-ok">
                                    <span class="status-dot online"></span>
                                    ACTIVE
                                </span>
                            </td>
                            <td style="color: var(--text-muted);">
                                {{ $domain->created_at ? $domain->created_at->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; align-items: center;">
                                    <button 
                                        type="button" 
                                        class="cyber-btn cyber-btn-secondary cyber-btn-xs" 
                                        onclick="pingDomain('{{ $domain->domain }}', this)"
                                        title="Check DNS status"
                                    >
                                        <i class="fa-solid fa-network-wired"></i> PING
                                    </button>
                                    
                                    <form action="{{ route('dashboard.domain.destroy', $domain->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to disconnect domain: {{ $domain->domain }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cyber-btn cyber-btn-xs" style="background: rgba(255, 23, 68, 0.15); border: 1px solid var(--red-primary); color: var(--red-primary);" title="Disconnect domain">
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

        {{-- No Search Match State --}}
        <div id="noDomainSearchMatch" class="empty-state" style="display: none; padding: 30px;">
            <i class="fa-solid fa-magnifying-glass-chart empty-icon"></i>
            <div class="empty-title">NO MATCHING DOMAIN FOUND</div>
            <p class="empty-desc">No registered domain matches your query filter.</p>
        </div>
    @endif
</div>

{{-- Register Domain Modal --}}
<div class="cyber-modal-backdrop" id="registerDomainModal" aria-hidden="true">
    <div class="cyber-modal-window" role="dialog" aria-labelledby="modalDomainTitle">
        <div class="cyber-modal-header">
            <div class="cyber-modal-title" id="modalDomainTitle">
                <i class="fa-solid fa-globe"></i>
                REGISTER NEW DOMAIN
            </div>
            <button type="button" class="cyber-modal-close" onclick="closeRegisterDomainModal()" aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('dashboard.domain.store') }}" method="POST">
            @csrf
            <div class="cyber-modal-body">
                <div class="desc-box">
                    Connect a domain or subdomain to your account and assign it to a product.
                </div>

                <div class="cyber-form-group">
                    <label class="cyber-label" for="domainInput">
                        <i class="fa-solid fa-globe"></i> DOMAIN NAME
                        <span class="cyber-label-hint">e.g. domain.com, localhost:8000, 127.0.0.1</span>
                    </label>
                    <input 
                        type="text" 
                        id="domainInput" 
                        name="domain" 
                        class="cyber-input" 
                        placeholder="example.com, localhost:8080, or 127.0.0.1" 
                        value="{{ old('domain') }}"
                        required
                        autocomplete="off"
                        pattern="^(localhost|((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)|([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,})(:\d{1,5})?$"
                        title="Enter a domain, localhost, IP (e.g. 127.0.0.1), or with optional port (e.g. localhost:8080)"
                    >
                </div>

                <div class="cyber-form-group">
                    <label class="cyber-label" for="productSelect">
                        <i class="fa-solid fa-box-open"></i> ASSIGN TO PRODUCT
                        <span class="cyber-label-hint">Select product</span>
                    </label>
                    <select id="productSelect" name="product_id" class="cyber-select" required>
                        @forelse ($userProducts as $prod)
                            <option value="{{ $prod->id }}" {{ old('product_id') == $prod->id ? 'selected' : '' }}>
                                {{ $prod->name }}
                            </option>
                        @empty
                            <option value="1">Primary Web App (Default)</option>
                        @endforelse
                    </select>
                </div>

                <div style="background: rgba(0, 255, 102, 0.05); border: 1px solid rgba(0, 255, 102, 0.2); border-radius: var(--radius-sm); padding: 10px 14px; font-family: var(--font-mono); font-size: 0.72rem; color: #a3e635; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>This domain will be registered under user <strong>{{ $user->name }}</strong> ({{ $user->email }}).</span>
                </div>
            </div>

            <div class="cyber-modal-footer">
                <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-sm" onclick="closeRegisterDomainModal()">
                    <i class="fa-solid fa-xmark"></i> CANCEL
                </button>
                <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-sm">
                    <i class="fa-solid fa-circle-check"></i> REGISTER DOMAIN
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Register Domain Modal Controls
    const domainModal = document.getElementById('registerDomainModal');

    function openRegisterDomainModal() {
        if (domainModal) {
            domainModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                const input = document.getElementById('domainInput');
                if (input) input.focus();
            }, 100);
        }
    }

    function closeRegisterDomainModal() {
        if (domainModal) {
            domainModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Close on backdrop click
    if (domainModal) {
        domainModal.addEventListener('click', (e) => {
            if (e.target === domainModal) {
                closeRegisterDomainModal();
            }
        });
    }

    // Close on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && domainModal && domainModal.classList.contains('active')) {
            closeRegisterDomainModal();
        }
    });

    // Domain Search Filter
    const searchInput = document.getElementById('domainSearchInput');
    const clearBtn = document.getElementById('clearDomainSearchBtn');
    const domainRows = document.querySelectorAll('.domain-row-item');
    const noMatchState = document.getElementById('noDomainSearchMatch');
    const counterTag = document.getElementById('domainCounterTag');

    function filterDomains() {
        if (!searchInput) return;
        const query = searchInput.value.toLowerCase().trim();
        let matches = 0;

        if (clearBtn) {
            clearBtn.style.display = query.length > 0 ? 'flex' : 'none';
        }

        domainRows.forEach(row => {
            const domain = row.getAttribute('data-domain') || '';
            const product = row.getAttribute('data-product') || '';

            if (domain.includes(query) || product.includes(query)) {
                row.style.display = '';
                matches++;
            } else {
                row.style.display = 'none';
            }
        });

        if (counterTag) {
            counterTag.textContent = `${matches} DOMAINS MATCHED`;
        }

        if (noMatchState) {
            noMatchState.style.display = (matches === 0 && domainRows.length > 0) ? 'flex' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterDomains);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            filterDomains();
            searchInput.focus();
        });
    }

    // Realtime Ping Check (Real Endpoint)
    async function pingDomain(domain, btn) {
        if (!btn) return;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> PINGING...';
        btn.disabled = true;

        try {
            const res = await fetch(`/api/ping/${encodeURIComponent(domain)}`);
            const data = await res.json();

            if (res.ok && data.online && data.latency >= 0) {
                btn.innerHTML = `<i class="fa-solid fa-check"></i> ${data.latency}ms`;
                btn.style.borderColor = 'var(--status-online)';
                btn.style.color = 'var(--status-online)';
            } else {
                btn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> DOWN';
                btn.style.borderColor = 'var(--red-primary)';
                btn.style.color = 'var(--red-primary)';
            }
        } catch (err) {
            btn.innerHTML = '<i class="fa-solid fa-xmark"></i> ERROR';
            btn.style.borderColor = 'var(--red-primary)';
            btn.style.color = 'var(--red-primary)';
        } finally {
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.style.borderColor = '';
                btn.style.color = '';
            }, 3000);
        }
    }
</script>
@endpush
