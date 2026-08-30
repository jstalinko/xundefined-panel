@extends('layouts.dashboard')

@section('title', 'Manage Orders - Admin')
@section('page-title', 'ORDERS MATRIX')

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
            <span class="prompt-dir">orders</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">list --invoices</span>
        </div>
        <span class="terminal-badge">TOTAL SALES: Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">ORDER & TRANSACTION MATRIX</h1>
            <p class="welcome-subtext">
                Audit customer purchase records, payment settlements, and license authorizations.
            </p>
        </div>

        {{-- Filter & Action Controls --}}
        <div class="filter-controls">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input 
                    type="text" 
                    id="orderClientSearch" 
                    class="tool-filter-input" 
                    placeholder="Search invoice, buyer, product..."
                    autocomplete="off"
                >
                <button type="button" id="clearOrderSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="cyber-btn cyber-btn-secondary">
                <i class="fa-solid fa-solar-panel"></i>
                <span>ADMIN HUB</span>
            </a>
        </div>
    </div>
</div>

{{-- Order Records Table Panel --}}
<div class="cyber-panel" style="margin-top: 20px;">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-cart-shopping"></i>
            CUSTOMER ORDERS MATRIX
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge-status b-ok">
                <span class="status-dot online"></span>
                <span id="orderCountBadge">{{ $orders->total() }} TRANSACTIONS</span>
            </span>
        </div>
    </div>

    @if ($orders->isEmpty())
        <div class="empty-state" style="padding: 48px 20px;">
            <i class="fa-solid fa-cart-arrow-down empty-icon"></i>
            <div class="empty-title">NO ORDERS RECORDED</div>
            <p class="empty-desc">No customer transactions found in the database.</p>
        </div>
    @else
        <div class="cyber-table-container">
            <table class="cyber-data-table" id="ordersTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>INVOICE</th>
                        <th>BUYER & EMAIL</th>
                        <th>PRODUCT</th>
                        <th>DOMAIN QUOTA</th>
                        <th>AMOUNT</th>
                        <th>GATEWAY</th>
                        <th>STATUS</th>
                        <th>DATE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $index => $ord)
                        <tr class="order-row-item" data-invoice="{{ strtolower($ord->invoice) }}" data-user="{{ strtolower($ord->user->name ?? '') }} {{ strtolower($ord->user->email ?? '') }}" data-product="{{ strtolower($ord->product->name ?? '') }}">
                            <td style="color: var(--text-muted); font-weight: 700;">
                                {{ sprintf('%02d', $index + 1) }}
                            </td>
                            <td>
                                <div class="domain-name-cell">
                                    <i class="fa-solid fa-receipt" style="color: var(--red-primary); font-size: 0.85rem;"></i>
                                    <a href="{{ route('order.show', $ord->id) }}" style="color: #ffffff; text-decoration: none;" title="View Audit Details">
                                        <code style="font-family: var(--font-mono); font-weight: 700; color: #ffffff;">
                                            {{ $ord->invoice }}
                                        </code>
                                    </a>
                                    <button 
                                        type="button" 
                                        class="cyber-copy-btn" 
                                        style="padding: 2px 6px; font-size: 0.65rem;" 
                                        onclick="copyText('{{ $ord->invoice }}', this)" 
                                        title="Copy Invoice"
                                    >
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                                @if ($ord->txn_id)
                                    <div style="font-size: 0.68rem; color: var(--text-muted); font-family: var(--font-mono); margin-top: 2px;">
                                        TXN: {{ Str::limit($ord->txn_id, 16) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div style="color: #ffffff; font-weight: 700; font-size: 0.86rem;">
                                    {{ $ord->user->name ?? 'User #' . $ord->user_id }}
                                </div>
                                <div style="color: #00ff66; font-size: 0.74rem; font-family: var(--font-mono);">
                                    <i class="fa-solid fa-envelope" style="font-size: 0.68rem;"></i> {{ $ord->user->email ?? 'N/A' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge-status" style="background: rgba(255, 23, 68, 0.1); border: 1px solid rgba(255, 23, 68, 0.3); color: #ffffff; font-size: 0.74rem;">
                                    <i class="fa-solid fa-cube" style="color: var(--red-primary);"></i>
                                    {{ $ord->product->name ?? 'Product #' . $ord->product_id }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="badge-status" style="background: rgba(0, 229, 255, 0.1); border: 1px solid rgba(0, 229, 255, 0.3); color: #00e5ff; font-family: var(--font-mono); font-weight: 700; font-size: 0.78rem;">
                                        <i class="fa-solid fa-globe"></i> {{ $ord->domain_quota }}
                                    </span>
                                    <button 
                                        type="button" 
                                        class="cyber-btn cyber-btn-xs" 
                                        style="padding: 2px 6px; font-size: 0.68rem; background: rgba(0, 229, 255, 0.12); border: 1px solid rgba(0, 229, 255, 0.4); color: #00e5ff;"
                                        onclick="openEditQuotaModal({{ json_encode($ord) }})"
                                        title="Edit Domain Quota"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <span style="font-family: var(--font-mono); font-weight: 800; color: #00ff66; font-size: 0.88rem;">
                                    Rp {{ number_format($ord->price, 0, ',', '.') }}
                                </span>
                            </td>
                            <td style="font-family: var(--font-mono); font-size: 0.76rem; color: var(--text-secondary);">
                                {{ $ord->payment_method ?? 'CyberPay' }}
                            </td>
                            <td>
                                @if ($ord->status === 'completed')
                                    <span class="badge-status b-ok">
                                        <span class="status-dot online"></span>
                                        COMPLETED
                                    </span>
                                @elseif ($ord->status === 'pending')
                                    <span class="badge-status" style="background: rgba(255, 209, 102, 0.15); border: 1px solid #ffd166; color: #ffd166;">
                                        <span class="status-dot" style="background: #ffd166;"></span>
                                        PENDING
                                    </span>
                                @else
                                    <span class="badge-status b-err">
                                        <span class="status-dot offline"></span>
                                        {{ strtoupper($ord->status) }}
                                    </span>
                                @endif
                            </td>
                            <td style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.76rem;">
                                {{ $ord->created_at ? $ord->created_at->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $orders->links() }}
        </div>
    @endif
</div>
{{-- Edit Order Domain Quota Modal --}}
<div class="cyber-modal-backdrop" id="editQuotaModalBackdrop">
    <div class="cyber-modal-window" style="max-width: 440px;">
        <div class="cyber-corner top-left"></div>
        <div class="cyber-corner top-right"></div>
        <div class="cyber-corner bottom-left"></div>
        <div class="cyber-corner bottom-right"></div>

        <div class="cyber-modal-header">
            <div class="cyber-modal-title">
                <i class="fa-solid fa-globe" style="color: #00e5ff;"></i>
                <span id="quotaModalTitle">EDIT DOMAIN QUOTA</span>
            </div>
            <button type="button" class="cyber-modal-close" onclick="closeEditQuotaModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="" method="POST" id="editQuotaForm">
            @csrf
            @method('PUT')

            <div class="cyber-modal-body">
                <div class="cyber-form-group">
                    <label class="cyber-label" for="modalQuotaInvoice">INVOICE NUMBER</label>
                    <input type="text" id="modalQuotaInvoice" class="cyber-input" readonly style="opacity: 0.7; font-family: var(--font-mono); font-weight: 700; color: #ffffff;">
                </div>

                <div class="cyber-form-group" style="margin-top: 14px;">
                    <label class="cyber-label" for="modalQuotaInput">
                        <i class="fa-solid fa-sliders"></i> ALLOWED DOMAIN QUOTA *
                    </label>
                    <input 
                        type="number" 
                        id="modalQuotaInput" 
                        name="domain_quota" 
                        class="cyber-input" 
                        min="0" 
                        max="9999" 
                        required 
                        style="font-family: var(--font-mono); font-weight: 800; color: #00e5ff; font-size: 1rem;"
                    >
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">
                        Max number of domains the buyer can register under this product license.
                    </div>
                </div>
            </div>

            <div class="cyber-modal-footer">
                <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-md" onclick="closeEditQuotaModal()">
                    CANCEL
                </button>
                <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-md">
                    <i class="fa-solid fa-floppy-disk"></i> SAVE QUOTA
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live Search Filter for Orders Table
    const orderSearchInput = document.getElementById('orderClientSearch');
    const clearOrderSearchBtn = document.getElementById('clearOrderSearchBtn');
    const orderRows = document.querySelectorAll('.order-row-item');
    const orderCountBadge = document.getElementById('orderCountBadge');

    if (orderSearchInput) {
        orderSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let matches = 0;

            if (clearOrderSearchBtn) {
                clearOrderSearchBtn.style.display = query.length > 0 ? 'flex' : 'none';
            }

            orderRows.forEach(row => {
                const inv = row.getAttribute('data-invoice') || '';
                const usr = row.getAttribute('data-user') || '';
                const prd = row.getAttribute('data-product') || '';

                if (inv.includes(query) || usr.includes(query) || prd.includes(query)) {
                    row.style.display = '';
                    matches++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (orderCountBadge) {
                orderCountBadge.textContent = matches + ' TRANSACTIONS';
            }
        });
    }

    if (clearOrderSearchBtn) {
        clearOrderSearchBtn.addEventListener('click', function () {
            orderSearchInput.value = '';
            orderSearchInput.dispatchEvent(new Event('input'));
            orderSearchInput.focus();
        });
    }

    // Edit Quota Modal Functions
    function openEditQuotaModal(data) {
        const backdrop = document.getElementById('editQuotaModalBackdrop');
        const form = document.getElementById('editQuotaForm');
        const invoiceInput = document.getElementById('modalQuotaInvoice');
        const quotaInput = document.getElementById('modalQuotaInput');

        if (backdrop && form) {
            form.action = `/admin/order/${data.id}`;
            if (invoiceInput) invoiceInput.value = data.invoice;
            if (quotaInput) quotaInput.value = data.domain_quota;

            backdrop.classList.add('active');
            document.body.style.overflow = 'hidden';
            setTimeout(() => { if (quotaInput) quotaInput.focus(); }, 100);
        }
    }

    function closeEditQuotaModal() {
        const backdrop = document.getElementById('editQuotaModalBackdrop');
        if (backdrop) {
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('click', function(e) {
        const backdrop = document.getElementById('editQuotaModalBackdrop');
        if (backdrop && e.target === backdrop) {
            closeEditQuotaModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditQuotaModal();
        }
    });

    window.openEditQuotaModal = openEditQuotaModal;
    window.closeEditQuotaModal = closeEditQuotaModal;
</script>
@endpush
