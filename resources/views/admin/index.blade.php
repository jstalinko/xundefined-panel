@extends('layouts.dashboard')

@section('title', 'Admin Dashboard - Overview')
@section('page-title', 'ADMIN DASHBOARD')

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
            <span class="prompt-dir">dashboard</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">status --telemetry</span>
        </div>
        <span class="terminal-badge" style="border-color: var(--red-primary); color: var(--red-primary);">ADMINISTRATOR CLEARANCE</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">SYSTEM ADMIN CONTROL CENTER</h1>
            <p class="welcome-subtext">
                Real-time platform overview, user metrics, transactional orders, and domain registrations.
            </p>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('product.create') }}" class="cyber-btn cyber-btn-primary cyber-btn-md">
                <i class="fa-solid fa-plus"></i> ADD PRODUCT
            </a>
            <a href="{{ route('product.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-md">
                <i class="fa-solid fa-boxes-stacked"></i> MANAGE PRODUCTS
            </a>
        </div>
    </div>
</div>

{{-- 4 Stats Overview Cards: Total Users, Total Domains, Total Orders, Revenue --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin: 20px 0;">
    {{-- Card 1: Total Users --}}
    <div class="cyber-panel" style="padding: 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em;">TOTAL USERS</div>
            <div style="font-family: var(--font-mono); font-size: 2rem; font-weight: 800; color: #ffffff; margin-top: 4px;">
                {{ $stats['total_users'] }}
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">
                <i class="fa-solid fa-user-check" style="color: var(--status-online); font-size: 0.7rem;"></i> Registered Accounts
            </div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(72, 202, 228, 0.12); border-color: rgba(72, 202, 228, 0.35); color: #48cae4; width: 48px; height: 48px; font-size: 1.4rem;">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>

    {{-- Card 2: Total Domains --}}
    <div class="cyber-panel" style="padding: 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em;">TOTAL DOMAINS</div>
            <div style="font-family: var(--font-mono); font-size: 2rem; font-weight: 800; color: #ffffff; margin-top: 4px;">
                {{ $stats['total_domains'] }}
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">
                <i class="fa-solid fa-globe" style="color: var(--red-primary); font-size: 0.7rem;"></i> Connected Endpoints
            </div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(255, 23, 68, 0.12); border-color: rgba(255, 23, 68, 0.35); color: var(--red-primary); width: 48px; height: 48px; font-size: 1.4rem;">
            <i class="fa-solid fa-globe"></i>
        </div>
    </div>

    {{-- Card 3: Total Orders --}}
    <div class="cyber-panel" style="padding: 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em;">TOTAL ORDERS</div>
            <div style="font-family: var(--font-mono); font-size: 2rem; font-weight: 800; color: #ffffff; margin-top: 4px;">
                {{ $stats['total_orders'] }}
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">
                <i class="fa-solid fa-receipt" style="color: #ffd166; font-size: 0.7rem;"></i> All Invoices
            </div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(255, 209, 102, 0.12); border-color: rgba(255, 209, 102, 0.35); color: #ffd166; width: 48px; height: 48px; font-size: 1.4rem;">
            <i class="fa-solid fa-cart-shopping"></i>
        </div>
    </div>

    {{-- Card 4: Revenue (Sum of completed orders) --}}
    <div class="cyber-panel" style="padding: 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em;">REVENUE</div>
            <div style="font-family: var(--font-mono); font-size: 1.45rem; font-weight: 800; color: #00ff66; margin-top: 4px; line-height: 1.2;">
                Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">
                <i class="fa-solid fa-circle-check" style="color: var(--status-online); font-size: 0.7rem;"></i> Completed Sales
            </div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(0, 255, 102, 0.12); border-color: rgba(0, 255, 102, 0.35); color: var(--status-online); width: 48px; height: 48px; font-size: 1.4rem;">
            <i class="fa-solid fa-money-bill-trend-up"></i>
        </div>
    </div>
</div>

{{-- Section 1: Latest Orders (All Statuses, Max 10) --}}
<div class="cyber-panel" style="margin-top: 24px;">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-cart-shopping"></i>
            LATEST ORDERS
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge-status b-ok">
                <span class="status-dot online"></span>
                <span>MAX 10 LATEST</span>
            </span>
            <a href="{{ route('order.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
                <i class="fa-solid fa-arrow-right"></i> VIEW ALL ORDERS
            </a>
        </div>
    </div>

    @if ($latestOrders->isEmpty())
        <div class="empty-state" style="padding: 36px 20px;">
            <i class="fa-solid fa-cart-arrow-down empty-icon"></i>
            <div class="empty-title">NO ORDERS RECORDED YET</div>
            <p class="empty-desc">No customer transactions found in the database.</p>
        </div>
    @else
        <div class="cyber-table-container">
            <table class="cyber-data-table" id="latestOrdersTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>INVOICE</th>
                        <th>USER & EMAIL</th>
                        <th>PRODUCT</th>
                        <th>AMOUNT</th>
                        <th>GATEWAY</th>
                        <th>STATUS</th>
                        <th>ORDER DATE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($latestOrders as $index => $ord)
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 700;">
                                {{ sprintf('%02d', $index + 1) }}
                            </td>
                            <td>
                                <div class="domain-name-cell">
                                    <i class="fa-solid fa-receipt" style="color: var(--red-primary); font-size: 0.85rem;"></i>
                                    <code style="font-family: var(--font-mono); font-weight: 700; color: #ffffff;">
                                        {{ $ord->invoice }}
                                    </code>
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
                            </td>
                            <td>
                                <div style="color: #ffffff; font-weight: 700; font-size: 0.86rem;">
                                    {{ $ord->user->name ?? 'User #' . $ord->user_id }}
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.74rem; font-family: var(--font-mono);">
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
    @endif
</div>

{{-- Section 2: All Registered Domains with User Email --}}
<div class="cyber-panel" style="margin-top: 24px;">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-globe"></i>
            ALL REGISTERED DOMAINS
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge-status b-ok">
                <span class="status-dot online"></span>
                <span>{{ $allDomains->count() }} DOMAINS TOTAL</span>
            </span>
        </div>
    </div>

    @if ($allDomains->isEmpty())
        <div class="empty-state" style="padding: 36px 20px;">
            <i class="fa-solid fa-network-wired empty-icon"></i>
            <div class="empty-title">NO DOMAINS REGISTERED</div>
            <p class="empty-desc">No domains have been registered by any user yet.</p>
        </div>
    @else
        <div class="cyber-table-container">
            <table class="cyber-data-table" id="allDomainsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>DOMAIN NAME</th>
                        <th>BOUND PRODUCT</th>
                        <th>USER / OWNER EMAIL</th>
                        <th>STATUS</th>
                        <th>REGISTERED DATE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allDomains as $index => $dom)
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 700;">
                                {{ sprintf('%02d', $index + 1) }}
                            </td>
                            <td>
                                <div class="domain-name-cell">
                                    <i class="fa-solid fa-globe" style="color: var(--red-primary); font-size: 0.9rem;"></i>
                                    <a href="https://{{ $dom->domain }}" target="_blank" rel="noopener noreferrer" style="font-weight: 700; color: #ffffff; text-decoration: none;">
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
                            </td>
                            <td>
                                @if ($dom->product)
                                    <span class="badge-status b-ok" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-cube"></i> {{ $dom->product->name }}
                                    </span>
                                @else
                                    <span class="badge-status" style="background: rgba(255, 255, 255, 0.08); color: #ccc; font-size: 0.72rem;">
                                        Product #{{ $dom->product_id }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div style="color: #ffffff; font-weight: 600; font-size: 0.86rem;">
                                    {{ $dom->user->name ?? 'User #' . $dom->user_id }}
                                </div>
                                <div style="color: #00ff66; font-family: var(--font-mono); font-size: 0.75rem;">
                                    <i class="fa-solid fa-envelope" style="font-size: 0.68rem;"></i> {{ $dom->user->email ?? 'N/A' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge-status b-ok">
                                    <span class="status-dot online"></span>
                                    ACTIVE
                                </span>
                            </td>
                            <td style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.76rem;">
                                {{ $dom->created_at ? $dom->created_at->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
