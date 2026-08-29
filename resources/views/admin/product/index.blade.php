@extends('layouts.dashboard')

@section('title', 'Manage Products - Admin')
@section('page-title', 'MANAGE PRODUCTS')

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

{{-- Header Banner --}}
<div class="terminal-banner">
    <div class="terminal-banner-header">
        <div class="terminal-prompt">
            <span class="prompt-user">{{ strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name)) }}</span>
            <span class="prompt-separator">/</span>
            <span class="prompt-dir">admin</span>
            <span class="prompt-separator">/</span>
            <span class="prompt-dir">products</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">list --catalog</span>
        </div>
        <span class="terminal-badge">CATALOG: {{ $stats['total'] }} PRODUCTS</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">PRODUCT CATALOG MANAGEMENT</h1>
            <p class="welcome-subtext">
                Create, update, release version packages, configure prices, and manage software payloads.
            </p>
        </div>

        {{-- Filter & Action Controls --}}
        <div class="filter-controls">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input 
                    type="text" 
                    id="productClientSearch" 
                    class="tool-filter-input" 
                    placeholder="Search products by name or description..."
                    autocomplete="off"
                >
                <button type="button" id="clearProdSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <a href="{{ route('product.create') }}" class="cyber-btn cyber-btn-primary">
                <i class="fa-solid fa-plus"></i>
                <span>ADD NEW PRODUCT</span>
            </a>
        </div>
    </div>
</div>

{{-- Product Records Table Panel --}}
<div class="cyber-panel" style="margin-top: 20px;">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-boxes-stacked"></i>
            PRODUCT CATALOG MATRIX
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge-status b-ok">
                <span class="status-dot online"></span>
                <span id="productCountBadge">{{ $products->total() }} PRODUCTS FOUND</span>
            </span>
            <a href="{{ route('product.create') }}" class="cyber-btn cyber-btn-primary cyber-btn-sm">
                <i class="fa-solid fa-plus"></i> NEW PRODUCT
            </a>
        </div>
    </div>

    @if ($products->isEmpty())
        {{-- Empty State --}}
        <div class="empty-state" style="padding: 48px 20px;">
            <i class="fa-solid fa-box-open empty-icon"></i>
            <div class="empty-title">NO PRODUCTS IN CATALOG</div>
            <p class="empty-desc">
                No software products were found. Create your first product to list it in the store.
            </p>
            <a href="{{ route('product.create') }}" class="cyber-btn cyber-btn-primary cyber-btn-md" style="margin-top: 10px;">
                <i class="fa-solid fa-plus"></i> CREATE FIRST PRODUCT
            </a>
        </div>
    @else
        {{-- Table identical in layout and styles to domain.blade.php --}}
        <div class="cyber-table-container">
            <table class="cyber-data-table" id="productsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>PRODUCT NAME</th>
                        <th>PRICE</th>
                        <th>RELEASES</th>
                        <th>STATUS</th>
                        <th>CREATED DATE</th>
                        <th style="text-align: right;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $index => $prod)
                        <tr class="product-row-item" data-name="{{ strtolower($prod->name) }}" data-desc="{{ strtolower($prod->description ?? '') }}">
                            <td style="color: var(--text-muted); font-weight: 700;">
                                {{ sprintf('%02d', $index + 1) }}
                            </td>
                            <td>
                                <div class="domain-name-cell">
                                    <i class="fa-solid fa-cube" style="color: var(--red-primary); font-size: 0.9rem;"></i>
                                    <a href="{{ route('product.show', $prod->id) }}" style="font-weight: 700; color: #ffffff; text-decoration: none;">
                                        {{ $prod->name }}
                                    </a>
                                    <button 
                                        type="button" 
                                        class="cyber-copy-btn" 
                                        style="padding: 2px 6px; font-size: 0.65rem;" 
                                        onclick="copyText('{{ $prod->name }}', this)" 
                                        title="Copy product name"
                                    >
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                                @if ($prod->description)
                                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px; max-width: 340px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $prod->description }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span style="font-family: var(--font-mono); font-weight: 800; color: #00ff66; font-size: 0.88rem;">
                                    Rp {{ number_format($prod->price, 0, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                @php $relCount = is_array($prod->contents) ? count($prod->contents) : 0; @endphp
                                <span class="badge-status b-ok" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-code-branch"></i> {{ $relCount }} Releases
                                </span>
                            </td>
                            <td>
                                @if ($prod->active)
                                    <span class="badge-status b-ok">
                                        <span class="status-dot online"></span>
                                        ACTIVE
                                    </span>
                                @else
                                    <span class="badge-status b-err">
                                        <span class="status-dot offline"></span>
                                        INACTIVE
                                    </span>
                                @endif
                            </td>
                            <td style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.76rem;">
                                {{ $prod->created_at ? $prod->created_at->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                    <a 
                                        href="{{ route('product.show', $prod->id) }}" 
                                        class="cyber-btn cyber-btn-secondary cyber-btn-xs" 
                                        title="View Details"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    
                                    <a 
                                        href="{{ route('product.edit', $prod->id) }}" 
                                        class="cyber-btn cyber-btn-primary cyber-btn-xs" 
                                        title="Edit Product"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i> EDIT
                                    </a>
                                    
                                    <form action="{{ route('product.destroy', $prod->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete product: {{ $prod->name }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cyber-btn cyber-btn-xs" style="background: rgba(255, 23, 68, 0.15); border: 1px solid var(--red-primary); color: var(--red-primary);" title="Delete product">
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
        <div style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Live Search Filter on Product Table
    const prodSearchInput = document.getElementById('productClientSearch');
    const clearProdSearchBtn = document.getElementById('clearProdSearchBtn');
    const prodRows = document.querySelectorAll('.product-row-item');
    const prodCountBadge = document.getElementById('productCountBadge');

    if (prodSearchInput) {
        prodSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let matches = 0;

            if (clearProdSearchBtn) {
                clearProdSearchBtn.style.display = query.length > 0 ? 'flex' : 'none';
            }

            prodRows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const desc = row.getAttribute('data-desc') || '';

                if (name.includes(query) || desc.includes(query)) {
                    row.style.display = '';
                    matches++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (prodCountBadge) {
                prodCountBadge.textContent = matches + ' PRODUCTS FOUND';
            }
        });
    }

    if (clearProdSearchBtn) {
        clearProdSearchBtn.addEventListener('click', function () {
            prodSearchInput.value = '';
            prodSearchInput.dispatchEvent(new Event('input'));
            prodSearchInput.focus();
        });
    }
</script>
@endpush
