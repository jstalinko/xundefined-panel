@extends('layouts.dashboard')

@section('title', 'Downloads')
@section('page-title', 'MY DOWNLOADS')

@section('content')
{{-- Flash Status Notifications --}}
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
            <span class="cyber-alert-title" style="color: var(--red-primary);">ERROR</span>
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
            <span class="prompt-dir">downloads</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">list --purchased</span>
        </div>
        <span class="terminal-badge">ACCOUNT: {{ $user->name }}</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">MY DOWNLOADS</h1>
            <p class="welcome-subtext">
                Download software packages, updates, and releases for your purchased products.
            </p>
        </div>

        {{-- Filter & Action Controls --}}
        <div class="filter-controls">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input 
                    type="text" 
                    id="downloadSearchInput" 
                    class="tool-filter-input" 
                    placeholder="Search downloads (product name, invoice, filename)..."
                    autocomplete="off"
                >
                <button type="button" id="clearDownloadSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="node-count-badge">
                <span class="node-count-num" id="visibleDownloadCount">{{ $orders->count() }}</span>
                <span class="node-count-label">PURCHASED PRODUCTS</span>
            </div>
        </div>
    </div>
</div>



{{-- Section Heading --}}
<div class="section-title-bar">
    <div class="title-with-icon">
        <i class="fa-solid fa-box-archive section-icon"></i>
        <h2 class="section-heading">PURCHASED PRODUCTS</h2>
    </div>
    <div class="section-divider-line"></div>
    <span class="section-meta-tag">[ YOUR ORDERS ]</span>
</div>

@if ($orders->isEmpty())
    {{-- Empty State --}}
    <div class="cyber-panel" style="text-align: center; padding: 48px 24px;">
        <i class="fa-solid fa-cloud-arrow-down" style="font-size: 3.5rem; color: var(--red-primary); margin-bottom: 16px; opacity: 0.7;"></i>
        <h3 style="font-family: var(--font-mono); font-size: 1.2rem; color: #ffffff; margin-bottom: 8px;">NO PURCHASED PRODUCTS YET</h3>
        <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 24px; font-size: 0.86rem; line-height: 1.6;">
            You have not purchased any products yet. Browse our store catalog to get started.
        </p>
        <a href="{{ route('dashboard.store') }}" class="cyber-btn cyber-btn-primary cyber-btn-md">
            <i class="fa-solid fa-cart-shopping"></i> BROWSE STORE
        </a>
    </div>
@else
    {{-- Split Cards List (1 row per product/order, split 50/50 col-6 left info & col-6 right versions) --}}
    <div class="download-split-list" id="downloadsGrid">
        @foreach ($orders as $order)
            @php
                $product = $order->product;
                $contents = $product->evaluated_contents 
                    ?? (is_array($product->contents ?? null) 
                        ? $product->contents 
                        : (json_decode($product->contents ?? '[]', true) ?: []));
                
                if (empty($contents)) {
                    $contents = [[
                        'file' => ($product->name ?? 'package') . '.zip',
                        'version' => '1.0.0',
                        'md5sum' => 'a9f1b2c3d4e5f60718293a4b5c6d7e8f',
                        'changelog' => 'Initial release build.',
                        'exists_in_storage' => false,
                        'file_size_human' => 'Unavailable'
                    ]];
                }
                
                $latestFile = $contents[0]['file'] ?? ($product->name ?? 'package') . '.zip';
                $latestVer = $contents[0]['version'] ?? '1.0.0';
                $firstMd5 = $contents[0]['md5sum'] ?? 'xxxxxxxxxxxxxxxx';
            @endphp
            <div 
                class="download-split-card download-card-item" 
                data-title="{{ strtolower($product->name ?? '') }}" 
                data-invoice="{{ strtolower($order->invoice) }}"
                data-file="{{ strtolower($latestFile) }}"
            >
                <div class="card-glow-layer"></div>

                <div class="download-split-grid">
                    {{-- Left Column (col-6): Product Information & License Summary --}}
                    <div class="download-info-col">
                        <div>
                            {{-- Top Ribbons --}}
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                <div class="cyber-badge-group">
                                    <span class="cyber-node-badge">
                                        <i class="fa-solid fa-cube"></i> PRODUCT #{{ sprintf('%02d', $product->id ?? $order->product_id) }}
                                    </span>
                                    <span class="cyber-ver-badge">LATEST: v{{ $latestVer }}</span>
                                </div>
                                <span class="cyber-status-pill status-owned">
                                    <i class="fa-solid fa-circle-check"></i> PURCHASED
                                </span>
                            </div>

                            {{-- Hero Row --}}
                            <div class="cyber-hero-row" style="margin-bottom: 14px;">
                                <div class="cyber-avatar-box" style="width: 54px; height: 54px; font-size: 1.6rem; background: rgba(0, 255, 102, 0.08); border-color: rgba(0, 255, 102, 0.3); color: var(--status-online);">
                                    <i class="fa-solid fa-box-open"></i>
                                </div>
                                <div class="cyber-title-group">
                                    <h3 class="cyber-card-heading" style="font-size: 1.15rem;">{{ $product->name ?? 'Software Module' }}</h3>
                                    <div class="cyber-card-subheading">
                                        <i class="fa-solid fa-tag" style="color: var(--red-primary);"></i>
                                        <span>Software Package</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Description --}}
                            <p class="cyber-card-text" style="font-size: 0.84rem; margin-bottom: 16px;">
                                {{ $product->description ?? 'Official release package with verified file integrity.' }}
                            </p>

                            {{-- License & Order Meta Box --}}
                            <div class="cyber-spec-hud">
                                <div class="cyber-spec-row">
                                    <span class="cyber-spec-key"><i class="fa-solid fa-file-invoice"></i> INVOICE:</span>
                                    <span class="cyber-spec-val" style="color: var(--red-primary); font-weight: 700;">#{{ $order->invoice }}</span>
                                </div>
                                <div class="cyber-spec-row">
                                    <span class="cyber-spec-key"><i class="fa-solid fa-calendar-check"></i> PURCHASE DATE:</span>
                                    <span class="cyber-spec-val">{{ $order->created_at ? $order->created_at->format('Y-m-d H:i') : 'N/A' }}</span>
                                </div>
                                <div class="cyber-spec-row">
                                    <span class="cyber-spec-key"><i class="fa-solid fa-money-bill-wave"></i> PRICE:</span>
                                    <span class="cyber-spec-val" style="color: var(--status-online); font-weight: 700;">{{ number_format($order->price, 0, ',', '.') }} IDR</span>
                                </div>
                                <div class="cyber-spec-row">
                                    <span class="cyber-spec-key"><i class="fa-solid fa-credit-card"></i> PAYMENT METHOD:</span>
                                    <span class="cyber-spec-val" style="color: #ffffff;">{{ $order->payment_method ?? 'Instant Gateway' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons on Left Side --}}
                        <div style="display: flex; gap: 10px; margin-top: 14px;">
                            <a 
                                href="{{ route('dashboard.domain') }}" 
                                class="cyber-btn cyber-btn-secondary cyber-btn-sm"
                                style="flex: 1; justify-content: center;"
                                title="Bind custom domain endpoint to this product"
                            >
                                <i class="fa-solid fa-globe"></i> BIND DOMAIN
                            </a>
                            <button 
                                type="button" 
                                class="cyber-btn cyber-btn-outline cyber-btn-sm"
                                onclick="showReceiptModal('{{ $order->invoice }}', '{{ addslashes($product->name ?? '') }}', '{{ number_format($order->price, 0, ',', '.') }} IDR', '{{ $order->payment_method }}', '{{ $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '' }}', '{{ $firstMd5 }}')"
                                title="View Purchase Receipt"
                                style="padding: 0 16px;"
                            >
                                <i class="fa-solid fa-receipt"></i> INVOICE
                            </button>
                        </div>
                    </div>

                    {{-- Right Column (col-6): Available Version Files List with Scroll --}}
                    <div class="download-versions-col">
                        <div class="versions-header">
                            <div class="versions-title">
                                <i class="fa-solid fa-code-branch"></i>
                                <span>RELEASES</span>
                            </div>
                            <span class="versions-count-tag">
                                {{ count($contents) }} {{ count($contents) > 1 ? 'Files' : 'File' }}
                            </span>
                        </div>

                        <div class="versions-list-container">
                            @foreach ($contents as $index => $item)
                                @php
                                    $itemFile = $item['file'] ?? ($product->name . '-v' . ($item['version'] ?? '1.0') . '.zip');
                                    $itemVer = $item['version'] ?? '1.0.0';
                                    $itemMd5 = $item['md5sum'] ?? 'a9f1b2c3d4e5f60718293a4b5c6d7e8f';
                                    $itemChangelog = $item['changelog'] ?? 'Standard release build.';
                                    $isLatest = ($index === 0);
                                    $fileExists = isset($item['exists_in_storage']) 
                                        ? $item['exists_in_storage'] 
                                        : is_file(storage_path('app/private/' . $itemFile));
                                    $fileSize = $item['file_size_human'] ?? ($fileExists ? round(filesize(storage_path('app/private/' . $itemFile)) / 1024, 2) . ' KB' : 'Unavailable');
                                @endphp
                                <div class="release-version-item {{ $isLatest ? 'is-latest' : '' }}">
                                    {{-- Version & Filename Row --}}
                                    <div class="release-top-row">
                                        <div class="release-meta-left">
                                            <span class="release-ver-pill">v{{ $itemVer }}</span>
                                            @if ($isLatest)
                                                <span class="release-latest-tag">LATEST</span>
                                            @endif
                                            <span class="release-filename">
                                                <i class="fa-solid fa-file-zipper"></i>
                                                {{ $itemFile }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Checksum & Changelog Box --}}
                                    <div class="release-details-row">
                                        <div class="release-checksum-box">
                                            <span style="color: var(--text-muted);"><i class="fa-solid fa-fingerprint"></i> MD5:</span>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <span class="cyber-hash-tag">{{ substr($itemMd5, 0, 16) }}...</span>
                                                <button 
                                                    type="button" 
                                                    class="cyber-copy-btn" 
                                                    style="padding: 2px 6px; font-size: 0.65rem;"
                                                    onclick="copyText('{{ $itemMd5 }}', this)"
                                                    title="Copy full MD5 checksum"
                                                >
                                                    <i class="fa-solid fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>

                                        @if ($itemChangelog)
                                            <div class="release-changelog">
                                                <i class="fa-solid fa-circle-info"></i>
                                                <span>{{ $itemChangelog }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Download Action Row --}}
                                    <div class="download-action-row">
                                        @if ($fileExists)
                                            <span style="font-family: var(--font-mono); font-size: 0.68rem; color: #a3e635;">
                                                <i class="fa-solid fa-circle-check" style="color: var(--status-online);"></i> Ready ({{ $fileSize }})
                                            </span>
                                            <a 
                                                href="{{ route('dashboard.download.file', ['id' => $order->product_id, 'version' => $itemVer]) }}" 
                                                class="btn-download-version"
                                            >
                                                <i class="fa-solid fa-download"></i> Download v{{ $itemVer }}
                                            </a>
                                        @else
                                            <span style="font-family: var(--font-mono); font-size: 0.68rem; color: #ff5252;">
                                                <i class="fa-solid fa-triangle-exclamation" style="color: var(--red-primary);"></i> File missing in vault
                                            </span>
                                            <a 
                                                href="{{ route('dashboard.download.file', ['id' => $order->product_id, 'version' => $itemVer]) }}" 
                                                class="btn-download-version"
                                                style="opacity: 0.55; border-color: rgba(255,23,68,0.4); background: rgba(255,23,68,0.08); color: #ff859b;"
                                                title="File package not found in storage/app/private/ vault"
                                            >
                                                <i class="fa-solid fa-download"></i> Download v{{ $itemVer }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Search Empty State --}}
    <div id="noDownloadSearchMatch" class="empty-state" style="display: none; padding: 40px;">
        <i class="fa-solid fa-magnifying-glass-chart empty-icon"></i>
        <div class="empty-title">NO MATCHING PACKAGES FOUND</div>
        <p class="empty-desc">No downloads match your search criteria.</p>
    </div>
@endif

{{-- Receipt / Invoice Info Modal --}}
<div class="cyber-modal-backdrop" id="receiptModal" aria-hidden="true">
    <div class="cyber-modal-window" role="dialog" aria-labelledby="receiptModalTitle">
        <div class="cyber-modal-header">
            <div class="cyber-modal-title" id="receiptModalTitle">
                <i class="fa-solid fa-receipt"></i>
                PURCHASE RECEIPT
            </div>
            <button type="button" class="cyber-modal-close" onclick="closeReceiptModal()" aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="cyber-modal-body">
            <div class="desc-box">
                Purchase details and invoice receipt for this order.
            </div>

            <table class="info-table">
                <tbody>
                    <tr>
                        <th style="width: 140px;">INVOICE CODE</th>
                        <td style="color: var(--red-primary); font-weight: 700;" id="receiptInvoice">--</td>
                    </tr>
                    <tr>
                        <th>PRODUCT NAME</th>
                        <td style="color: #ffffff; font-weight: 600;" id="receiptProduct">--</td>
                    </tr>
                    <tr>
                        <th>AMOUNT PAID</th>
                        <td style="color: var(--status-online); font-weight: 700;" id="receiptAmount">--</td>
                    </tr>
                    <tr>
                        <th>PAYMENT METHOD</th>
                        <td id="receiptGateway">--</td>
                    </tr>
                    <tr>
                        <th>PURCHASE DATE</th>
                        <td id="receiptTimestamp">--</td>
                    </tr>
                    <tr>
                        <th>STATUS</th>
                        <td><span class="badge-status b-ok"><i class="fa-solid fa-check"></i> Completed & Paid</span></td>
                    </tr>
                    <tr>
                        <th>MD5 CHECKSUM</th>
                        <td><code id="receiptMd5">--</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="cyber-modal-footer">
            <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-sm" onclick="closeReceiptModal()">
                <i class="fa-solid fa-xmark"></i> CLOSE
            </button>
            <button type="button" class="cyber-btn cyber-btn-primary cyber-btn-sm" onclick="copyReceiptDigest(this)">
                <i class="fa-solid fa-copy"></i> COPY RECEIPT DETAILS
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live Search Filter for Downloads
    const downloadSearch = document.getElementById('downloadSearchInput');
    const clearDownloadBtn = document.getElementById('clearDownloadSearchBtn');
    const downloadCards = document.querySelectorAll('.download-card-item');
    const noDownloadMatch = document.getElementById('noDownloadSearchMatch');
    const visibleDownloadCount = document.getElementById('visibleDownloadCount');

    function filterDownloads() {
        if (!downloadSearch) return;
        const query = downloadSearch.value.toLowerCase().trim();
        let matches = 0;

        if (clearDownloadBtn) {
            clearDownloadBtn.style.display = query.length > 0 ? 'flex' : 'none';
        }

        downloadCards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            const invoice = card.getAttribute('data-invoice') || '';
            const file = card.getAttribute('data-file') || '';

            if (title.includes(query) || invoice.includes(query) || file.includes(query)) {
                card.style.display = 'flex';
                matches++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visibleDownloadCount) {
            visibleDownloadCount.textContent = matches;
        }

        if (noDownloadMatch) {
            noDownloadMatch.style.display = (matches === 0 && downloadCards.length > 0) ? 'flex' : 'none';
        }
    }

    if (downloadSearch) {
        downloadSearch.addEventListener('input', filterDownloads);
    }

    if (clearDownloadBtn) {
        clearDownloadBtn.addEventListener('click', () => {
            downloadSearch.value = '';
            filterDownloads();
            downloadSearch.focus();
        });
    }

    // Receipt Modal Logic
    const receiptModal = document.getElementById('receiptModal');
    let currentReceiptDigest = '';

    function showReceiptModal(invoice, product, amount, gateway, time, md5) {
        document.getElementById('receiptInvoice').textContent = '#' + invoice;
        document.getElementById('receiptProduct').textContent = product;
        document.getElementById('receiptAmount').textContent = amount;
        document.getElementById('receiptGateway').textContent = gateway;
        document.getElementById('receiptTimestamp').textContent = time;
        document.getElementById('receiptMd5').textContent = md5;

        currentReceiptDigest = `INVOICE: ${invoice} | PRODUCT: ${product} | AMOUNT: ${amount} | TIME: ${time} | MD5: ${md5}`;

        if (receiptModal) {
            receiptModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeReceiptModal() {
        if (receiptModal) {
            receiptModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (receiptModal) {
        receiptModal.addEventListener('click', (e) => {
            if (e.target === receiptModal) closeReceiptModal();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && receiptModal && receiptModal.classList.contains('active')) {
            closeReceiptModal();
        }
    });

    function copyReceiptDigest(btn) {
        if (currentReceiptDigest) {
            copyText(currentReceiptDigest, btn);
        }
    }
</script>
@endpush
