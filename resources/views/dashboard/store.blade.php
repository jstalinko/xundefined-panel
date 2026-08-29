@extends('layouts.dashboard')

@section('title', 'Cyber Store Matrix')
@section('page-title', 'XSTORE MATRIX')

@section('content')
{{-- Flash Status Notifications --}}
@if (session('status'))
    <div class="cyber-alert" role="alert" style="border-color: var(--status-online); background: rgba(0, 255, 102, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-circle-check cyber-alert-icon" style="color: var(--status-online);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--status-online);">ORDER UPDATE</span>
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

{{-- Terminal Banner --}}
<div class="terminal-banner">
    <div class="terminal-banner-header">
        <div class="terminal-prompt">
            <span class="prompt-user">{{ strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name)) }}@xundefined</span>
            <span class="prompt-separator">:</span>
            <span class="prompt-dir">~/store</span>
            <span class="prompt-symbol">#</span>
            <span class="prompt-cmd">catalog --status=active --currency=IDR --auth-user={{ $user->id }}</span>
        </div>
        <span class="terminal-badge">CATALOG: {{ $products->count() }} MODULES</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">PRODUCT STORE</h1>
            <p class="welcome-subtext">
                Browse our catalog of software products, modules, and developer tools.
            </p>
        </div>

        {{-- Filter & Action Controls --}}
        <div class="filter-controls">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input 
                    type="text" 
                    id="storeSearchInput" 
                    class="tool-filter-input" 
                    placeholder="Search products (name, description)..."
                    autocomplete="off"
                >
                <button type="button" id="clearStoreSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="node-count-badge">
                <span class="node-count-num" id="visibleStoreCount">{{ $products->count() }}</span>
                <span class="node-count-label">PRODUCTS AVAILABLE</span>
            </div>
        </div>
    </div>
</div>

{{-- Telemetry Stats Bar --}}
<div class="cyber-stats-grid" style="margin-top: 0; margin-bottom: 24px;">
    <div class="cyber-stat-box">
        <div class="cyber-stat-lbl">TOTAL PRODUCTS</div>
        <div class="cyber-stat-val">{{ $products->count() }}</div>
    </div>
    <div class="cyber-stat-box">
        <div class="cyber-stat-lbl">PURCHASED</div>
        <div class="cyber-stat-val" style="color: var(--status-online);">{{ count($purchasedProductIds) }}</div>
    </div>
    <div class="cyber-stat-box">
        <div class="cyber-stat-lbl">AVAILABLE TO ORDER</div>
        <div class="cyber-stat-val" style="color: #48cae4;">{{ max(0, $products->count() - count($purchasedProductIds)) }}</div>
    </div>
    <div class="cyber-stat-box">
        <div class="cyber-stat-lbl">ACCOUNT ID</div>
        <div class="cyber-stat-val" style="color: var(--red-primary); font-size: 0.95rem;">USER #{{ $user->id }}</div>
    </div>
</div>

{{-- Section Heading --}}
<div class="section-title-bar">
    <div class="title-with-icon">
        <i class="fa-solid fa-store section-icon"></i>
        <h2 class="section-heading">PRODUCTS & SOFTWARE STORE</h2>
    </div>
    <div class="section-divider-line"></div>
    <span class="section-meta-tag">[ ALL PRODUCTS ]</span>
</div>

@if ($products->isEmpty())
    {{-- Empty State --}}
    <div class="cyber-panel" style="text-align: center; padding: 48px 24px;">
        <i class="fa-solid fa-boxes-packing" style="font-size: 3.5rem; color: var(--red-primary); margin-bottom: 16px; opacity: 0.7;"></i>
        <h3 style="font-family: var(--font-mono); font-size: 1.2rem; color: #ffffff; margin-bottom: 8px;">CATALOG CURRENTLY EMPTY</h3>
        <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 24px; font-size: 0.86rem; line-height: 1.6;">
            No products are currently available in the catalog. Please check back later.
        </p>
    </div>
@else
    {{-- Products Grid --}}
    <div class="cyber-cards-grid" id="storeGrid">
        @foreach ($products as $product)
            @php
                $isPurchased = in_array($product->id, $purchasedProductIds);
                $contents = is_array($product->contents) 
                    ? $product->contents 
                    : (json_decode($product->contents ?? '[]', true) ?: []);
                $firstFile = $contents[0]['file'] ?? ($product->name . '.zip');
                $version = $contents[0]['version'] ?? '1.0.0';
                $md5 = $contents[0]['md5sum'] ?? null;
            @endphp
            <div 
                class="cyber-card-item store-card-item {{ $isPurchased ? 'is-owned' : '' }}" 
                data-title="{{ strtolower($product->name) }}" 
                data-desc="{{ strtolower($product->description ?? '') }}"
                data-status="{{ $isPurchased ? 'purchased' : 'available' }}"
            >
                <div class="card-glow-layer"></div>

                {{-- Card Top Ribbon --}}
                <div class="cyber-card-topbar">
                    <div class="cyber-badge-group">
                        <span class="cyber-node-badge">
                            <i class="fa-solid fa-cube"></i> PRODUCT #{{ sprintf('%02d', $product->id) }}
                        </span>
                        <span class="cyber-ver-badge">v{{ $version }}</span>
                    </div>
                    @if ($isPurchased)
                        <span class="cyber-status-pill status-owned">
                            <i class="fa-solid fa-circle-check"></i> OWNED
                        </span>
                    @else
                        <span class="cyber-status-pill status-available">
                            <i class="fa-solid fa-cart-shopping"></i> AVAILABLE
                        </span>
                    @endif
                </div>

                {{-- Card Body --}}
                <div class="cyber-card-main">
                    {{-- Hero Row --}}
                    <div class="cyber-hero-row">
                        <div class="cyber-avatar-box">
                            @if ($isPurchased)
                                <i class="fa-solid fa-shield-halved"></i>
                            @else
                                <i class="fa-solid fa-box-open"></i>
                            @endif
                        </div>
                        <div class="cyber-title-group">
                            <h3 class="cyber-card-heading">{{ $product->name }}</h3>
                            <div class="cyber-card-subheading">
                                <i class="fa-solid fa-tag" style="color: var(--red-primary);"></i>
                                <span>Software Product</span>
                            </div>
                        </div>
                    </div>

                    {{-- Show / Hide Description --}}
                    <div class="product-desc-wrapper">
                        <button 
                            type="button" 
                            class="cyber-toggle-desc-btn" 
                            onclick="toggleProductDesc({{ $product->id }}, this)"
                        >
                            <span class="btn-text">Show Description</span>
                            <i class="fa-solid fa-chevron-down toggle-chevron"></i>
                        </button>
                        <div id="desc-collapse-{{ $product->id }}" class="product-desc-content" style="display: none;">
                            <p class="cyber-card-text" style="margin: 0;">
                                {{ $product->description ?? 'Official software package release with complete documentation and features.' }}
                            </p>
                        </div>
                    </div>

                    {{-- Price HUD Box --}}
                    <div class="cyber-price-hud" style="{{ $isPurchased ? 'border-color: rgba(0, 255, 102, 0.25); background: rgba(0, 255, 102, 0.04);' : '' }}">
                        @if ($isPurchased)
                            <div>
                                <div class="cyber-price-label" style="color: var(--status-online);"><i class="fa-solid fa-circle-check"></i> STATUS</div>
                                <div style="font-family: var(--font-mono); font-weight: 700; color: #ffffff; font-size: 0.92rem; margin-top: 2px;">ACTIVE & LICENSED</div>
                            </div>
                            <span class="cyber-status-pill status-owned" style="font-size: 0.65rem;">LIFETIME ACCESS</span>
                        @else
                            <div>
                                <div class="cyber-price-label">PRICE</div>
                                <div class="cyber-price-amount">
                                    {{ number_format($product->price, 0, ',', '.') }}
                                    <span class="curr">IDR</span>
                                </div>
                            </div>
                            <span class="cyber-status-pill status-available" style="font-size: 0.65rem;">ONE-TIME</span>
                        @endif
                    </div>
                </div>

                {{-- Action Footer --}}
                <div class="cyber-card-action-bar">
                    @if ($isPurchased)
                        <a 
                            href="{{ route('dashboard.download') }}" 
                            class="cyber-btn cyber-btn-success cyber-btn-block"
                            style="justify-content: center; background: rgba(0, 255, 102, 0.15); border-color: var(--status-online); color: var(--status-online);"
                        >
                            <i class="fa-solid fa-circle-check"></i> Purchased
                            <i class="fa-solid fa-arrow-right" style="margin-left: 6px; font-size: 0.8rem;"></i>
                        </a>
                    @else
                        <button 
                            type="button" 
                            class="cyber-btn cyber-btn-primary cyber-btn-block"
                            onclick="openOrderModal({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, 'v{{ $version }}')"
                            style="justify-content: center;"
                        >
                            <i class="fa-solid fa-cart-shopping"></i> BUY PRODUCT
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Search Empty State --}}
    <div id="noStoreSearchMatch" class="empty-state" style="display: none; padding: 40px;">
        <i class="fa-solid fa-magnifying-glass-chart empty-icon"></i>
        <div class="empty-title">NO MATCHING PRODUCTS FOUND</div>
        <p class="empty-desc">No products match your search keywords.</p>
    </div>
@endif

{{-- Purchase / Checkout Order Modal --}}
<div class="cyber-modal-backdrop" id="orderModal" aria-hidden="true">
    <div class="cyber-modal-window" role="dialog" aria-labelledby="orderModalTitle">
        <div class="cyber-modal-header">
            <div class="cyber-modal-title" id="orderModalTitle">
                <i class="fa-solid fa-cart-shopping"></i>
                BUY PRODUCT
            </div>
            <button type="button" class="cyber-modal-close" onclick="closeOrderModal()" aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('dashboard.store.purchase') }}" method="POST">
            @csrf
            <input type="hidden" name="product_id" id="orderProductId" value="">

            <div class="cyber-modal-body">
                <div class="desc-box">
                    Confirm your purchase. The software and all release versions will be unlocked immediately in your downloads.
                </div>

                {{-- Order Summary Details --}}
                <div style="background: rgba(10, 10, 10, 0.9); border: 1px solid rgba(255, 23, 68, 0.25); border-radius: var(--radius-md); padding: 14px 16px; margin-bottom: 16px;">
                    <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">
                        ORDER SUMMARY
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-size: 0.88rem; font-weight: 700; color: #ffffff;" id="orderProductName">--</span>
                        <span class="badge-status b-ok" id="orderProductVer">v1.0.0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid rgba(255, 255, 255, 0.05);">
                        <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--text-secondary);">TOTAL DUE:</span>
                        <span style="font-family: var(--font-mono); font-size: 1.15rem; font-weight: 800; color: var(--status-online);" id="orderProductPrice">0 IDR</span>
                    </div>
                </div>

                {{-- Payment Method Selection --}}
                <div class="cyber-form-group">
                    <label class="cyber-label" for="paymentMethodSelect">
                        <i class="fa-solid fa-credit-card"></i> PAYMENT METHOD
                    </label>
                    <select id="paymentMethodSelect" name="payment_method" class="cyber-select" required>
                        <option value="CyberPay Instant Gateway">CyberPay Instant Gateway (Instant Activation)</option>
                        <option value="QRIS Instant Settlement">QRIS Instant (QR Code)</option>
                        <option value="BCA / Mandiri Virtual Account">BCA / Mandiri Virtual Account</option>
                        <option value="Crypto USDT / BTC Gateway">Crypto USDT / BTC Gateway</option>
                    </select>
                </div>

                <div style="background: rgba(0, 255, 102, 0.05); border: 1px solid rgba(0, 255, 102, 0.2); border-radius: var(--radius-sm); padding: 10px 14px; font-family: var(--font-mono); font-size: 0.72rem; color: #a3e635; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Product license will be registered to <strong>{{ $user->name }}</strong> ({{ $user->email }}).</span>
                </div>
            </div>

            <div class="cyber-modal-footer">
                <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-sm" onclick="closeOrderModal()">
                    <i class="fa-solid fa-xmark"></i> CANCEL
                </button>
                <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-sm">
                    <i class="fa-solid fa-check"></i> CONFIRM & BUY
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Toggle Product Description
    function toggleProductDesc(id, btn) {
        const content = document.getElementById('desc-collapse-' + id);
        if (!content) return;
        const textSpan = btn.querySelector('.btn-text');
        const isHidden = content.style.display === 'none' || content.style.display === '';
        
        if (isHidden) {
            content.style.display = 'block';
            btn.classList.add('active');
            if (textSpan) textSpan.textContent = 'Hide Description';
        } else {
            content.style.display = 'none';
            btn.classList.remove('active');
            if (textSpan) textSpan.textContent = 'Show Description';
        }
    }

    // Live Search Filter for Store
    const storeSearch = document.getElementById('storeSearchInput');
    const clearStoreBtn = document.getElementById('clearStoreSearchBtn');
    const storeCards = document.querySelectorAll('.store-card-item');
    const noStoreMatch = document.getElementById('noStoreSearchMatch');
    const visibleStoreCount = document.getElementById('visibleStoreCount');

    function filterStore() {
        if (!storeSearch) return;
        const query = storeSearch.value.toLowerCase().trim();
        let matches = 0;

        if (clearStoreBtn) {
            clearStoreBtn.style.display = query.length > 0 ? 'flex' : 'none';
        }

        storeCards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            const desc = card.getAttribute('data-desc') || '';

            if (title.includes(query) || desc.includes(query)) {
                card.style.display = 'flex';
                matches++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visibleStoreCount) {
            visibleStoreCount.textContent = matches;
        }

        if (noStoreMatch) {
            noStoreMatch.style.display = (matches === 0 && storeCards.length > 0) ? 'flex' : 'none';
        }
    }

    if (storeSearch) {
        storeSearch.addEventListener('input', filterStore);
    }

    if (clearStoreBtn) {
        clearStoreBtn.addEventListener('click', () => {
            storeSearch.value = '';
            filterStore();
            storeSearch.focus();
        });
    }

    // Order Modal Controls
    const orderModal = document.getElementById('orderModal');

    function openOrderModal(productId, productName, price, version) {
        document.getElementById('orderProductId').value = productId;
        document.getElementById('orderProductName').textContent = productName;
        document.getElementById('orderProductPrice').textContent = new Intl.NumberFormat('id-ID').format(price) + ' IDR';
        document.getElementById('orderProductVer').textContent = version || 'v1.0';

        if (orderModal) {
            orderModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeOrderModal() {
        if (orderModal) {
            orderModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (orderModal) {
        orderModal.addEventListener('click', (e) => {
            if (e.target === orderModal) closeOrderModal();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && orderModal && orderModal.classList.contains('active')) {
            closeOrderModal();
        }
    });
</script>
@endpush
