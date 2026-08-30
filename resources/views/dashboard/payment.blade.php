@extends('layouts.dashboard')

@section('title', 'Crypto Invoice #' . $order->invoice)
@section('page-title', 'PAYMENT GATEWAY')

@push('styles')
<style>
    .payment-container {
        max-width: 900px;
        margin: 0 auto;
    }
    .pay-card {
        background: rgba(15, 15, 20, 0.95);
        border: 1px solid rgba(255, 23, 68, 0.35);
        border-radius: var(--radius-md, 8px);
        box-shadow: 0 0 30px rgba(255, 23, 68, 0.12), inset 0 0 20px rgba(0, 0, 0, 0.8);
        position: relative;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .pay-card-header {
        padding: 18px 24px;
        background: rgba(255, 23, 68, 0.08);
        border-bottom: 1px solid rgba(255, 23, 68, 0.25);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .pay-card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: var(--font-mono, 'JetBrains Mono', monospace);
        font-size: 1.05rem;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 0.05em;
    }
    .pay-card-body {
        padding: 24px;
    }
    .pay-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 768px) {
        .pay-grid {
            grid-template-columns: 1fr;
        }
    }
    .qr-box {
        background: #ffffff;
        padding: 16px;
        border-radius: var(--radius-sm, 6px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(255, 23, 68, 0.4);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
    }
    .qr-img {
        width: 100%;
        max-width: 220px;
        height: auto;
        aspect-ratio: 1 / 1;
        image-rendering: pixelated;
        display: block;
    }
    .qr-caption {
        margin-top: 10px;
        font-family: var(--font-mono, 'JetBrains Mono', monospace);
        font-size: 0.72rem;
        color: #111111;
        font-weight: 700;
        text-transform: uppercase;
        text-align: center;
    }
    .pay-detail-group {
        margin-bottom: 18px;
    }
    .pay-label {
        font-family: var(--font-mono, 'JetBrains Mono', monospace);
        font-size: 0.75rem;
        color: var(--text-muted, #888888);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .pay-copy-box {
        display: flex;
        align-items: stretch;
        background: rgba(0, 0, 0, 0.75);
        border: 1px solid rgba(255, 23, 68, 0.3);
        border-radius: var(--radius-sm, 4px);
        overflow: hidden;
    }
    .pay-copy-input {
        flex: 1;
        background: transparent;
        border: none;
        padding: 10px 14px;
        color: #ffffff;
        font-family: var(--font-mono, 'JetBrains Mono', monospace);
        font-size: 0.88rem;
        font-weight: 600;
        outline: none;
        min-width: 0;
    }
    .pay-copy-btn {
        background: rgba(255, 23, 68, 0.15);
        border: none;
        border-left: 1px solid rgba(255, 23, 68, 0.3);
        color: var(--red-primary, #ff1744);
        padding: 0 16px;
        font-family: var(--font-mono, 'JetBrains Mono', monospace);
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    .pay-copy-btn:hover {
        background: var(--red-primary, #ff1744);
        color: #ffffff;
    }
    .pay-amount-highlight {
        font-size: 1.3rem;
        color: #00ff66;
    }
    .status-hud-box {
        background: rgba(0, 0, 0, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: var(--radius-sm, 6px);
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .live-pulse {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #ffaa00;
        box-shadow: 0 0 10px #ffaa00;
        animation: pulseAnim 1.5s infinite;
        margin-right: 6px;
    }
    @keyframes pulseAnim {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 170, 0, 0.7); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(255, 170, 0, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 170, 0, 0); }
    }
    .countdown-bar-wrap {
        background: rgba(255, 255, 255, 0.08);
        height: 6px;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 8px;
    }
    .countdown-bar-fill {
        background: linear-gradient(90deg, #00ff66, var(--red-primary, #ff1744));
        height: 100%;
        width: 100%;
        transition: width 1s linear;
    }
    .cyber-notice-alert {
        background: rgba(255, 170, 0, 0.08);
        border: 1px solid rgba(255, 170, 0, 0.3);
        border-radius: var(--radius-sm, 6px);
        padding: 12px 16px;
        font-size: 0.82rem;
        color: #ffcc00;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        line-height: 1.5;
        margin-top: 20px;
    }
</style>
@endpush

@section('content')
<div class="payment-container">
    {{-- Flash Messages --}}
    @if (session('status'))
        <div class="cyber-alert" role="alert" style="border-color: var(--status-online); background: rgba(0, 255, 102, 0.08); margin-bottom: 20px;">
            <i class="fa-solid fa-circle-check cyber-alert-icon" style="color: var(--status-online);"></i>
            <div class="cyber-alert-content">
                <span class="cyber-alert-title" style="color: var(--status-online);">GATEWAY NOTIFICATION</span>
                <span class="cyber-alert-msg">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    {{-- Breadcrumb Navigation Bar --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <a href="{{ route('dashboard.store') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
            <i class="fa-solid fa-arrow-left"></i> BACK TO STORE
        </a>
        <div style="font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-secondary);">
            TRANSACTION ID: <span style="color: #ffffff; font-weight: 700;">{{ $order->txn_id ?? 'PENDING' }}</span>
        </div>
    </div>

    {{-- Main Payment HUD Card --}}
    <div class="pay-card">
        <div class="pay-card-header">
            <div class="pay-card-title">
                <i class="fa-solid fa-satellite-dish" style="color: var(--red-primary);"></i>
                <span>CRYPTO GATEWAY // INVOICE #{{ $order->invoice }}</span>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <span class="cyber-badge" style="background: rgba(255, 23, 68, 0.2); border: 1px solid var(--red-primary); color: #ffffff; padding: 4px 10px; font-size: 0.75rem;">
                    {{ $order->payment_currency ?? 'CRYPTO' }}
                </span>
                <span id="headerStatusBadge" class="cyber-badge" style="background: rgba(255, 170, 0, 0.15); border: 1px solid #ffaa00; color: #ffaa00; padding: 4px 10px; font-size: 0.75rem;">
                    <i class="fa-solid fa-clock"></i> <span id="statusBadgeText">{{ strtoupper($order->status) }}</span>
                </span>
            </div>
        </div>

        <div class="pay-card-body">
            {{-- Status and Expiration HUD --}}
            <div class="status-hud-box">
                <div>
                    <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">
                        PAYMENT TELEMETRY
                    </div>
                    <div style="font-family: var(--font-mono); font-size: 0.95rem; font-weight: 700; color: #ffffff; margin-top: 3px; display: flex; align-items: center;">
                        <span id="liveDot" class="live-pulse"></span>
                        <span id="liveStatusMessage">Awaiting On-Chain Transfer...</span>
                    </div>
                </div>

                <div style="text-align: right;">
                    <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">
                        TIME REMAINING
                    </div>
                    <div style="font-family: var(--font-mono); font-size: 1.1rem; font-weight: 800; color: #ffaa00;" id="countdownClock">
                        --:--
                    </div>
                </div>
            </div>

            <div class="pay-grid">
                {{-- Left Column: QR Code --}}
                <div>
                    <div class="qr-box">
                        @php
                            $qrUrl = $order->payment_qrcode_url;
                            if (!$qrUrl && !empty($order->payment_address)) {
                                $qrData = $order->payment_address;
                                if (!empty($order->payment_amount)) {
                                    $qrData = strtolower(explode('.', $order->payment_currency ?? 'crypto')[0]) . ':' . $order->payment_address . '?amount=' . $order->payment_amount;
                                }
                                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrData);
                            }
                        @endphp
                        <img src="{{ $qrUrl }}" alt=" Crypto QR Code" class="qr-img" id="qrImage">
                        <div class="qr-caption">
                            <i class="fa-solid fa-qrcode"></i> SCAN WITH WALLET APP
                        </div>
                    </div>

                    {{-- Order Summary Info --}}
                    <div style="margin-top: 16px; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.06); border-radius: var(--radius-sm); padding: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 6px;">
                            <span style="color: var(--text-muted);">Product:</span>
                            <span style="color: #ffffff; font-weight: 600;">{{ $order->product->name ?? 'Software Package' }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 6px;">
                            <span style="color: var(--text-muted);">Fiat Price:</span>
                            <span style="color: #ffffff;">{{ number_format($order->price, 0, ',', '.') }} IDR</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                            <span style="color: var(--text-muted);">Required Confirms:</span>
                            <span style="color: #48cae4; font-weight: 700;">{{ $order->payment_confirms_needed ?? 1 }} Block(s)</span>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Crypto Transfer Coordinates --}}
                <div>
                    {{-- Crypto Amount --}}
                    <div class="pay-detail-group">
                        <label class="pay-label">
                            <i class="fa-solid fa-coins" style="color: #00ff66;"></i> EXACT AMOUNT TO SEND ({{ $order->payment_currency }})
                        </label>
                        <div class="pay-copy-box">
                            <input 
                                type="text" 
                                id="cryptoAmountInput" 
                                class="pay-copy-input pay-amount-highlight" 
                                value="{{ $order->payment_amount }}" 
                                readonly
                            >
                            <button type="button" class="pay-copy-btn" onclick="copyInputValue('cryptoAmountInput', this)">
                                <i class="fa-solid fa-copy"></i> COPY
                            </button>
                        </div>
                    </div>

                    {{-- Crypto Deposit Address --}}
                    <div class="pay-detail-group">
                        <label class="pay-label">
                            <i class="fa-solid fa-wallet" style="color: var(--red-primary);"></i> RECEIVING ADDRESS ({{ $order->payment_currency }})
                        </label>
                        <div class="pay-copy-box">
                            <input 
                                type="text" 
                                id="cryptoAddressInput" 
                                class="pay-copy-input" 
                                value="{{ $order->payment_address }}" 
                                readonly
                            >
                            <button type="button" class="pay-copy-btn" onclick="copyInputValue('cryptoAddressInput', this)">
                                <i class="fa-solid fa-copy"></i> COPY
                            </button>
                        </div>
                    </div>

                    {{-- Destination Tag / Memo (if applicable) --}}
                    @if (!empty($order->payment_dest_tag))
                    <div class="pay-detail-group">
                        <label class="pay-label" style="color: #ffaa00;">
                            <i class="fa-solid fa-tag"></i> DESTINATION TAG / MEMO (REQUIRED)
                        </label>
                        <div class="pay-copy-box" style="border-color: rgba(255, 170, 0, 0.4);">
                            <input 
                                type="text" 
                                id="cryptoDestTagInput" 
                                class="pay-copy-input" 
                                style="color: #ffaa00;" 
                                value="{{ $order->payment_dest_tag }}" 
                                readonly
                            >
                            <button type="button" class="pay-copy-btn" style="color: #ffaa00; background: rgba(255, 170, 0, 0.15);" onclick="copyInputValue('cryptoDestTagInput', this)">
                                <i class="fa-solid fa-copy"></i> COPY
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Action Button Bar --}}
                    <div style="display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap;">
                        <button type="button" id="refreshStatusBtn" class="cyber-btn cyber-btn-primary" onclick="manualCheckStatus()" style="flex: 1; justify-content: center;">
                            <i class="fa-solid fa-rotate" id="refreshSpinner"></i> CHECK ON-CHAIN STATUS
                        </button>
                    </div>

                    {{-- Warning Note --}}
                    <div class="cyber-notice-alert">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.1rem; margin-top: 2px;"></i>
                        <div>
                            <strong>IMPORTANT:</strong> Send the exact cryptocurrency amount specified above. Sending from multiple transactions or using incompatible networks may cause delays. Once confirmed on the blockchain, this page will automatically redirect and activate your module license.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Copy input value helper
    function copyInputValue(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> COPIED';
            btn.style.background = '#00ff66';
            btn.style.color = '#000000';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.style.background = '';
                btn.style.color = '';
            }, 2000);
        });
    }

    // Expiration Countdown Timer
    let remainingSeconds = {{ (int) $remainingSeconds }};
    const countdownClock = document.getElementById('countdownClock');

    function updateCountdown() {
        if (!countdownClock) return;
        if (remainingSeconds <= 0) {
            countdownClock.textContent = 'EXPIRED / TIMEOUT';
            countdownClock.style.color = 'var(--red-primary)';
            return;
        }

        const minutes = Math.floor(remainingSeconds / 60);
        const seconds = remainingSeconds % 60;
        countdownClock.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        remainingSeconds--;
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();

    // Auto-polling status checker
    const invoiceNumber = '{{ $order->invoice }}';
    let pollInterval = null;
    let isChecking = false;

    async function checkOrderStatus(refresh = false) {
        if (isChecking) return;
        isChecking = true;

        try {
            const url = `{{ route('dashboard.payment.status', $order->invoice) }}?refresh=${refresh ? '1' : '0'}`;
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) throw new Error('Status poll failed');
            const data = await res.json();

            if (data.success) {
                const statusBadgeText = document.getElementById('statusBadgeText');
                const liveStatusMsg = document.getElementById('liveStatusMessage');
                const liveDot = document.getElementById('liveDot');

                if (data.is_completed) {
                    clearInterval(pollInterval);
                    if (statusBadgeText) statusBadgeText.textContent = 'COMPLETED';
                    if (liveStatusMsg) liveStatusMsg.textContent = 'Payment Confirmed! Unlocking Payload...';
                    if (liveDot) {
                        liveDot.style.background = '#00ff66';
                        liveDot.style.boxShadow = '0 0 10px #00ff66';
                    }

                    setTimeout(() => {
                        window.location.href = data.redirect_url || '{{ route('dashboard.download') }}';
                    }, 1500);
                } else if (data.is_processing) {
                    if (statusBadgeText) statusBadgeText.textContent = 'CONFIRMING';
                    if (liveStatusMsg) liveStatusMsg.textContent = 'Transaction Detected! Awaiting block confirmations...';
                    if (liveDot) {
                        liveDot.style.background = '#48cae4';
                        liveDot.style.boxShadow = '0 0 10px #48cae4';
                    }
                } else if (data.is_cancelled) {
                    clearInterval(pollInterval);
                    if (statusBadgeText) statusBadgeText.textContent = 'CANCELLED';
                    if (liveStatusMsg) liveStatusMsg.textContent = 'Payment expired or cancelled.';
                    if (liveDot) {
                        liveDot.style.background = 'var(--red-primary)';
                        liveDot.style.boxShadow = '0 0 10px var(--red-primary)';
                    }
                }
            }
        } catch (e) {
            console.error('Polling error:', e);
        } finally {
            isChecking = false;
        }
    }

    async function manualCheckStatus() {
        const spinner = document.getElementById('refreshSpinner');
        const btn = document.getElementById('refreshStatusBtn');
        if (spinner) spinner.classList.add('fa-spin');
        if (btn) btn.disabled = true;

        await checkOrderStatus(true);

        setTimeout(() => {
            if (spinner) spinner.classList.remove('fa-spin');
            if (btn) btn.disabled = false;
        }, 1000);
    }

    // Start polling every 10 seconds
    pollInterval = setInterval(() => checkOrderStatus(false), 10000);
</script>
@endpush
