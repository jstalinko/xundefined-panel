@extends('layouts.dashboard')

@section('title', 'Order Details #' . $order->invoice)
@section('page-title', 'ORDER AUDIT')

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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <a href="{{ route('order.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
        <i class="fa-solid fa-arrow-left"></i> BACK TO ORDERS
    </a>
    <div style="display: flex; gap: 8px;">
        <span class="badge-status {{ $order->status === 'completed' ? 'b-ok' : ($order->status === 'pending' ? 'b-warn' : 'b-err') }}">
            STATUS: {{ strtoupper($order->status) }}
        </span>
    </div>
</div>

<div class="cyber-panel">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-file-invoice-dollar" style="color: var(--red-primary);"></i>
            TRANSACTION AUDIT // INVOICE #{{ $order->invoice }}
        </div>
        <div style="font-family: var(--font-mono); font-size: 0.78rem; color: var(--text-muted);">
            CREATED: {{ $order->created_at ? $order->created_at->format('Y-m-d H:i:s T') : 'N/A' }}
        </div>
    </div>

    <div class="cyber-panel-body" style="padding: 24px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
            {{-- Order Specs --}}
            <div style="background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 23, 68, 0.2); border-radius: var(--radius-sm); padding: 16px;">
                <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--red-primary); font-weight: 700; text-transform: uppercase; margin-bottom: 12px;">
                    CUSTOMER & PRODUCT DETAILS
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 8px;">
                    <span style="color: var(--text-muted);">Customer:</span>
                    <span style="color: #ffffff; font-weight: 700;">{{ $order->user->name ?? 'N/A' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 8px;">
                    <span style="color: var(--text-muted);">Email:</span>
                    <span style="color: #00ff66; font-family: var(--font-mono);">{{ $order->user->email ?? 'N/A' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 8px;">
                    <span style="color: var(--text-muted);">Product:</span>
                    <span style="color: #ffffff; font-weight: 700;">{{ $order->product->name ?? 'N/A' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem;">
                    <span style="color: var(--text-muted);">Fiat Price:</span>
                    <span style="color: #00ff66; font-family: var(--font-mono); font-weight: 700;">Rp {{ number_format($order->price, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- CoinPayments Specs --}}
            <div style="background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(0, 229, 255, 0.2); border-radius: var(--radius-sm); padding: 16px;">
                <div style="font-family: var(--font-mono); font-size: 0.72rem; color: #00e5ff; font-weight: 700; text-transform: uppercase; margin-bottom: 12px;">
                    GATEWAY TELEMETRY
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 8px;">
                    <span style="color: var(--text-muted);">Payment Method:</span>
                    <span style="color: #ffffff;">{{ $order->payment_method }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 8px;">
                    <span style="color: var(--text-muted);">CoinPayments TXN:</span>
                    <span style="color: #ffffff; font-family: var(--font-mono); font-size: 0.78rem;">{{ $order->txn_id ?? 'N/A' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 8px;">
                    <span style="color: var(--text-muted);">Crypto Amount:</span>
                    <span style="color: #00ff66; font-family: var(--font-mono); font-weight: 700;">{{ $order->payment_amount ? ($order->payment_amount . ' ' . $order->payment_currency) : 'N/A' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem;">
                    <span style="color: var(--text-muted);">Receiving Address:</span>
                    <span style="color: #ffffff; font-family: var(--font-mono); font-size: 0.75rem;">{{ $order->payment_address ? Str::limit($order->payment_address, 20) : 'N/A' }}</span>
                </div>
            </div>
        </div>

        {{-- Update Quota & Status Form --}}
        <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-sm); padding: 20px; margin-bottom: 24px;">
            <div style="font-family: var(--font-mono); font-size: 0.82rem; font-weight: 700; color: #ffffff; margin-bottom: 16px;">
                <i class="fa-solid fa-sliders" style="color: var(--red-primary);"></i> UPDATE ORDER OVERRIDE
            </div>

            <form action="{{ route('order.update', $order->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px; align-items: end;">
                    <div class="cyber-form-group">
                        <label class="cyber-label" for="orderQuotaInput">DOMAIN QUOTA</label>
                        <input type="number" id="orderQuotaInput" name="domain_quota" class="cyber-input" value="{{ $order->domain_quota }}" min="0" max="9999" required>
                    </div>

                    <div class="cyber-form-group">
                        <label class="cyber-label" for="orderStatusSelect">ORDER STATUS</label>
                        <select id="orderStatusSelect" name="status" class="cyber-select">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <button type="submit" class="cyber-btn cyber-btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> SAVE CHANGES
                    </button>
                </div>
            </form>
        </div>

        {{-- Raw Metadata / IPN Payload Inspector --}}
        @if (!empty($order->payment_meta))
        <div style="background: rgba(0, 0, 0, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-sm); padding: 16px;">
            <div style="font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">
                <i class="fa-solid fa-code"></i> RAW COINPAYMENTS GATEWAY PAYLOAD & IPN AUDIT
            </div>
            <pre style="background: rgba(10, 10, 10, 0.9); border: 1px solid rgba(255, 255, 255, 0.05); padding: 14px; border-radius: var(--radius-sm); font-family: var(--font-mono); font-size: 0.75rem; color: #a3e635; overflow-x: auto; max-height: 300px;">{{ json_encode($order->payment_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
        @endif
    </div>
</div>
@endsection
