@extends('layouts.payment')

@section('title', 'Status // Invoice #' . $order->invoice)

@push('styles')
<style>
    .status-container {
        max-width: 820px;
        margin: 0 auto;
    }

    .status-hero-card {
        background: rgba(14, 14, 18, 0.95);
        border: 1px solid rgba(255, 23, 68, 0.35);
        border-radius: var(--radius-md, 8px);
        box-shadow: 0 0 35px rgba(255, 23, 68, 0.12), inset 0 0 20px rgba(0, 0, 0, 0.8);
        overflow: hidden;
        margin-bottom: 24px;
        position: relative;
    }

    .status-hero-header {
        padding: 30px 24px 24px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        position: relative;
    }

    .hero-icon-wrap {
        width: 72px;
        height: 72px;
        margin: 0 auto 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        transition: all 0.3s ease;
    }

    .status-completed .hero-icon-wrap {
        background: rgba(0, 255, 102, 0.15);
        border: 2px solid #00ff66;
        color: #00ff66;
        box-shadow: 0 0 25px rgba(0, 255, 102, 0.4);
    }

    .status-processing .hero-icon-wrap {
        background: rgba(72, 202, 228, 0.15);
        border: 2px solid #48cae4;
        color: #48cae4;
        box-shadow: 0 0 25px rgba(72, 202, 228, 0.4);
    }

    .status-pending .hero-icon-wrap {
        background: rgba(255, 170, 0, 0.15);
        border: 2px solid #ffaa00;
        color: #ffaa00;
        box-shadow: 0 0 25px rgba(255, 170, 0, 0.35);
    }

    .status-cancelled .hero-icon-wrap {
        background: rgba(255, 23, 68, 0.15);
        border: 2px solid var(--red-primary, #ff1744);
        color: var(--red-primary, #ff1744);
        box-shadow: 0 0 25px rgba(255, 23, 68, 0.4);
    }

    .status-hero-title {
        font-family: var(--font-mono, monospace);
        font-size: 1.3rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .status-hero-desc {
        color: var(--text-secondary, #aaaaaa);
        font-size: 0.9rem;
        max-width: 580px;
        margin: 0 auto;
        line-height: 1.5;
    }

    .telemetry-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
        padding: 24px;
    }

    @media (max-width: 640px) {
        .telemetry-grid {
            grid-template-columns: 1fr;
        }
    }

    .telemetry-item {
        background: rgba(0, 0, 0, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: var(--radius-sm, 6px);
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .telemetry-label {
        font-family: var(--font-mono, monospace);
        font-size: 0.72rem;
        color: #888888;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .telemetry-value {
        font-family: var(--font-mono, monospace);
        font-size: 0.95rem;
        font-weight: 700;
        color: #ffffff;
        word-break: break-all;
    }

    .telemetry-value.highlight-green {
        color: #00ff66;
    }

    .telemetry-value.highlight-cyan {
        color: #48cae4;
    }

    .telemetry-value.highlight-amber {
        color: #ffaa00;
    }

    .status-actions-bar {
        padding: 18px 24px;
        background: rgba(0, 0, 0, 0.8);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 18px;
        font-family: var(--font-mono, monospace);
        font-size: 0.82rem;
        font-weight: 700;
        border-radius: 4px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }

    .action-btn-primary {
        background: var(--red-primary, #ff1744);
        color: #ffffff;
        box-shadow: 0 0 16px rgba(255, 23, 68, 0.3);
    }

    .action-btn-primary:hover {
        background: #d50000;
        box-shadow: 0 0 20px rgba(255, 23, 68, 0.5);
    }

    .action-btn-secondary {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff;
    }

    .action-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .action-btn-success {
        background: rgba(0, 255, 102, 0.15);
        border: 1px solid #00ff66;
        color: #00ff66;
    }

    .action-btn-success:hover {
        background: #00ff66;
        color: #000000;
    }
</style>
@endpush

@section('content')
<div class="status-container">
    {{-- Top Breadcrumb --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('dashboard.payment.show', $order->invoice) }}" class="action-btn action-btn-secondary" style="padding: 6px 14px; font-size: 0.78rem;">
                <i class="fa-solid fa-arrow-left"></i> PAYMENT INSTRUCTIONS
            </a>
            <a href="{{ auth()->check() ? route('admin.dashboard') : url('/') }}" class="action-btn action-btn-secondary" style="padding: 6px 14px; font-size: 0.78rem;">
                <i class="fa-solid fa-house"></i> {{ auth()->check() ? 'DASHBOARD' : 'HOME' }}
            </a>
        </div>
        <div style="font-family: var(--font-mono, monospace); font-size: 0.8rem; color: #888888;">
            INVOICE: <span style="color: #ffffff; font-weight: 700;">#{{ $order->invoice }}</span>
        </div>
    </div>

    @php
        $statusKey = strtolower($order->status);
        if ($statusKey === 'completed') {
            $cardClass = 'status-completed';
            $heroIcon = 'fa-circle-check';
            $heroTitle = 'Payment Completed // Settlement Confirmed';
            $heroDesc = 'Cryptocurrency deposit confirmed on the blockchain. Account balance and product license are credited and ready for deployment.';
            $titleColor = '#00ff66';
        } elseif ($statusKey === 'processing') {
            $cardClass = 'status-processing';
            $heroIcon = 'fa-circle-nodes fa-spin';
            $heroTitle = 'Transaction Detected // Awaiting Confirmations';
            $heroDesc = 'Your transfer has been detected on the blockchain network and is currently accumulating the required block confirmations.';
            $titleColor = '#48cae4';
        } elseif ($statusKey === 'cancelled') {
            $cardClass = 'status-cancelled';
            $heroIcon = 'fa-circle-xmark';
            $heroTitle = 'Payment Cancelled // Invoice Expired';
            $heroDesc = 'This payment session has timed out or was cancelled. If you already sent funds, please contact support with your TXN ID.';
            $titleColor = '#ff1744';
        } else {
            $cardClass = 'status-pending';
            $heroIcon = 'fa-clock-rotate-left';
            $heroTitle = 'Awaiting Blockchain Transfer';
            $heroDesc = 'No cryptocurrency transfer detected yet for this receiving address. Transfer the exact amount to proceed.';
            $titleColor = '#ffaa00';
        }
    @endphp

    {{-- Main Status Hero Card --}}
    <div class="status-hero-card {{ $cardClass }}" id="statusHeroCard">
        <div class="status-hero-header">
            <div class="hero-icon-wrap" id="heroIconWrap">
                <i class="fa-solid {{ $heroIcon }}" id="heroIcon"></i>
            </div>
            <h1 class="status-hero-title" id="heroTitle" style="color: {{ $titleColor }};">
                {{ $heroTitle }}
            </h1>
            <p class="status-hero-desc" id="heroDesc">
                {{ $heroDesc }}
            </p>
        </div>

        {{-- Telemetry Details Grid --}}
        <div class="telemetry-grid">
            <div class="telemetry-item">
                <span class="telemetry-label"><i class="fa-solid fa-file-invoice"></i> INVOICE NUMBER</span>
                <span class="telemetry-value">#{{ $order->invoice }}</span>
            </div>

            <div class="telemetry-item">
                <span class="telemetry-label"><i class="fa-solid fa-cube"></i> ORDER NUMBER</span>
                <span class="telemetry-value">#{{ $order->order_number }}</span>
            </div>

            <div class="telemetry-item">
                <span class="telemetry-label"><i class="fa-solid fa-box"></i> ITEM PURPOSE</span>
                <span class="telemetry-value">{{ $order->product ? $order->product->name : 'Balance Top-up' }}</span>
            </div>

            <div class="telemetry-item">
                <span class="telemetry-label"><i class="fa-solid fa-dollar-sign"></i> FIAT VALUE</span>
                <span class="telemetry-value">${{ number_format($order->price ?? $order->amount, 2) }} USD</span>
            </div>

            <div class="telemetry-item">
                <span class="telemetry-label"><i class="fa-solid fa-coins"></i> CRYPTO VALUE</span>
                <span class="telemetry-value highlight-green">{{ $order->payment_amount }} {{ $order->payment_currency }}</span>
            </div>

            <div class="telemetry-item">
                <span class="telemetry-label"><i class="fa-solid fa-shield-halved"></i> STATUS CODE</span>
                <span class="telemetry-value" id="gridStatusVal" style="color: {{ $titleColor }};">{{ strtoupper($order->status) }}</span>
            </div>

            <div class="telemetry-item" style="grid-column: 1 / -1;">
                <span class="telemetry-label"><i class="fa-solid fa-wallet"></i> RECEIVING ADDRESS ({{ $order->payment_currency }})</span>
                <span class="telemetry-value" style="font-size: 0.85rem;">{{ $order->payment_address }}</span>
            </div>

            @if (!empty($order->payment_dest_tag))
            <div class="telemetry-item">
                <span class="telemetry-label"><i class="fa-solid fa-tag"></i> DESTINATION TAG / MEMO</span>
                <span class="telemetry-value highlight-amber">{{ $order->payment_dest_tag }}</span>
            </div>
            @endif

            <div class="telemetry-item">
                <span class="telemetry-label"><i class="fa-solid fa-hashtag"></i> TRANSACTION HASH / TXN ID</span>
                <span class="telemetry-value" style="font-size: 0.8rem;" id="gridTxnVal">{{ $order->txn_id ?? 'PENDING' }}</span>
            </div>

            <div class="telemetry-item">
                <span class="telemetry-label"><i class="fa-solid fa-cubes"></i> BLOCK CONFIRMATIONS</span>
                <span class="telemetry-value highlight-cyan">{{ $order->payment_confirms_needed ?? 1 }} Block(s) Required</span>
            </div>

            <div class="telemetry-item">
                <span class="telemetry-label"><i class="fa-solid fa-calendar"></i> CREATED AT</span>
                <span class="telemetry-value" style="font-size: 0.8rem;">{{ $order->created_at ? $order->created_at->format('M d, Y H:i:s T') : 'N/A' }}</span>
            </div>
        </div>

        {{-- Bottom Actions Bar --}}
        <div class="status-actions-bar">
            <div style="font-family: var(--font-mono, monospace); font-size: 0.75rem; color: #888888; display: flex; align-items: center; gap: 6px;">
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #00ff66;"></span>
                <span id="lastCheckText">Live Telemetry Active</span>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="action-btn action-btn-secondary" id="refreshBtn" onclick="manualStatusCheck()">
                    <i class="fa-solid fa-rotate" id="refreshSpinner"></i> REFRESH TELEMETRY
                </button>

                @if (!$order->isCompleted())
                    <a href="{{ route('dashboard.payment.show', $order->invoice) }}" class="action-btn action-btn-primary">
                        <i class="fa-solid fa-qrcode"></i> PAYMENT INSTRUCTIONS & QR
                    </a>
                @else
                    <a href="{{ auth()->check() ? route('admin.dashboard') : url('/') }}" class="action-btn action-btn-success">
                        <i class="fa-solid fa-check"></i> {{ auth()->check() ? 'ACCESS DASHBOARD' : 'RETURN HOME' }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const invoice = '{{ $order->invoice }}';
    let isPolling = false;
    let pollInterval = null;

    async function checkStatus(refresh = false) {
        if (isPolling) return;
        isPolling = true;

        try {
            const url = `{{ route('dashboard.payment.status', $order->invoice) }}?refresh=${refresh ? '1' : '0'}`;
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) throw new Error('Poll failed');
            const data = await res.json();

            if (data.success) {
                const now = new Date().toLocaleTimeString();
                const lastCheckText = document.getElementById('lastCheckText');
                if (lastCheckText) lastCheckText.textContent = `Updated at ${now}`;

                if (data.is_completed) {
                    clearInterval(pollInterval);
                    updateUI('completed', 'Payment Completed // Settlement Confirmed', 'Cryptocurrency deposit confirmed on the blockchain. Account balance and product license are credited and ready.', '#00ff66', 'fa-circle-check');
                } else if (data.is_processing) {
                    updateUI('processing', 'Transaction Detected // Awaiting Confirmations', 'Your transfer has been detected on the blockchain network and is currently accumulating confirmations.', '#48cae4', 'fa-circle-nodes fa-spin');
                } else if (data.is_cancelled) {
                    clearInterval(pollInterval);
                    updateUI('cancelled', 'Payment Cancelled // Invoice Expired', 'This payment invoice has timed out or was cancelled.', '#ff1744', 'fa-circle-xmark');
                }
            }
        } catch (e) {
            console.error('Status check error:', e);
        } finally {
            isPolling = false;
        }
    }

    function updateUI(status, title, desc, color, icon) {
        const card = document.getElementById('statusHeroCard');
        const heroTitle = document.getElementById('heroTitle');
        const heroDesc = document.getElementById('heroDesc');
        const heroIconWrap = document.getElementById('heroIconWrap');
        const heroIcon = document.getElementById('heroIcon');
        const gridStatusVal = document.getElementById('gridStatusVal');

        if (card) {
            card.className = `status-hero-card status-${status}`;
        }
        if (heroTitle) {
            heroTitle.textContent = title;
            heroTitle.style.color = color;
        }
        if (heroDesc) heroDesc.textContent = desc;
        if (heroIcon) heroIcon.className = `fa-solid ${icon}`;
        if (gridStatusVal) {
            gridStatusVal.textContent = status.toUpperCase();
            gridStatusVal.style.color = color;
        }
    }

    async function manualStatusCheck() {
        const spinner = document.getElementById('refreshSpinner');
        const btn = document.getElementById('refreshBtn');
        if (spinner) spinner.classList.add('fa-spin');
        if (btn) btn.disabled = true;

        await checkStatus(true);

        setTimeout(() => {
            if (spinner) spinner.classList.remove('fa-spin');
            if (btn) btn.disabled = false;
        }, 1000);
    }

    pollInterval = setInterval(() => checkStatus(false), 8000);
</script>
@endpush
