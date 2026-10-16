@extends('layouts.dashboard')

@section('title', 'Domain Details: ' . $domain->domain . ' - Admin')
@section('page-title', 'DOMAIN DETAILS')

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

<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);">
        <a href="{{ route('admin.dashboard') }}" style="color: var(--text-secondary); text-decoration: none;">Admin Hub</a>
        <span>/</span>
        <a href="{{ route('domain.index') }}" style="color: var(--text-secondary); text-decoration: none;">Domains</a>
        <span>/</span>
        <span style="color: #ffffff;">#{{ $domain->id }} {{ $domain->domain }}</span>
    </div>

    <div style="display: flex; gap: 8px;">
        <a href="https://{{ $domain->domain }}" target="_blank" rel="noopener noreferrer" class="cyber-btn cyber-btn-primary cyber-btn-sm">
            <i class="fa-solid fa-up-right-from-square"></i> VISIT DOMAIN
        </a>
        <a href="{{ route('domain.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
            <i class="fa-solid fa-arrow-left"></i> BACK TO DOMAINS
        </a>
    </div>
</div>

<div class="split-grid" style="align-items: start;">
    {{-- Left Column: Domain Specifications --}}
    <div class="cyber-panel">
        <div class="cyber-panel-header">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-globe" style="color: var(--red-primary);"></i>
                DOMAIN SPECIFICATIONS
            </div>
            @if ($domain->status === 'active')
                <span class="badge-status b-ok">
                    <span class="status-dot online"></span>
                    ACTIVE
                </span>
            @else
                <span class="badge-status b-err">
                    <span class="status-dot offline"></span>
                    {{ strtoupper($domain->status ?: 'INACTIVE') }}
                </span>
            @endif
        </div>

        <table class="info-table">
            <tbody>
                <tr>
                    <th style="width: 160px;">DOMAIN ID</th>
                    <td style="font-family: var(--font-mono); font-weight: 700; color: #ffffff;">#{{ $domain->id }}</td>
                </tr>
                <tr>
                    <th>DOMAIN HOSTNAME</th>
                    <td>
                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                            <a href="https://{{ $domain->domain }}" target="_blank" rel="noopener noreferrer" style="font-family: var(--font-mono); font-weight: 700; color: #00ff66; text-decoration: none;">
                                {{ $domain->domain }}
                            </a>
                            <button 
                                type="button" 
                                class="cyber-copy-btn" 
                                style="padding: 2px 6px; font-size: 0.65rem;" 
                                onclick="copyText('{{ $domain->domain }}', this)" 
                                title="Copy Domain"
                            >
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>BOUND PRODUCT</th>
                    <td>
                        @if ($domain->product)
                            <a href="{{ route('product.show', $domain->product->id) }}" style="text-decoration: none;">
                                <span class="badge-status b-ok" style="font-size: 0.76rem;">
                                    <i class="fa-solid fa-cube"></i> {{ $domain->product->name }}
                                </span>
                            </a>
                            @if ($domain->product->pid)
                                <code style="margin-left: 6px; font-family: var(--font-mono); font-size: 0.75rem; color: #48cae4;">
                                    ({{ $domain->product->pid }})
                                </code>
                            @endif
                        @else
                            <span style="color: var(--text-muted);">Product #{{ $domain->product_id ?? 'N/A' }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>SERVER / CLIENT IP</th>
                    <td style="font-family: var(--font-mono); color: #ffffff;">
                        {{ $domain->ip_address ?: 'Not recorded' }}
                    </td>
                </tr>
                <tr>
                    <th>VALIDATION HITS</th>
                    <td>
                        <span style="font-family: var(--font-mono); font-weight: 700; color: #ffd166;">
                            <i class="fa-solid fa-bolt"></i> {{ number_format((int) $domain->hits) }} validations
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>REGISTERED DATE</th>
                    <td style="font-family: var(--font-mono); font-size: 0.8rem; color: #ffffff;">
                        {{ $domain->created_at ? $domain->created_at->format('Y-m-d H:i:s T') : 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <th>LAST UPDATED</th>
                    <td style="font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-secondary);">
                        {{ $domain->updated_at ? $domain->updated_at->format('Y-m-d H:i:s T') : 'N/A' }}
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Domain Override / Update Form --}}
        <div style="padding: 18px; border-top: 1px solid rgba(255, 255, 255, 0.08); background: rgba(0, 0, 0, 0.25);">
            <div style="font-family: var(--font-mono); font-size: 0.78rem; font-weight: 700; color: #ffffff; margin-bottom: 12px;">
                <i class="fa-solid fa-sliders" style="color: var(--red-primary);"></i> UPDATE DOMAIN PARAMETERS
            </div>

            <form action="{{ route('domain.update', $domain->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="cyber-form-group" style="margin-bottom: 0;">
                        <label class="cyber-label" for="domainHostInput">DOMAIN HOSTNAME</label>
                        <input type="text" id="domainHostInput" name="domain" class="cyber-input" value="{{ $domain->domain }}" required>
                    </div>

                    <div class="cyber-form-group" style="margin-bottom: 0;">
                        <label class="cyber-label" for="domainStatusSelect">LICENSE STATUS</label>
                        <select id="domainStatusSelect" name="status" class="cyber-select">
                            <option value="active" {{ $domain->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $domain->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ $domain->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                    <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-sm">
                        <i class="fa-solid fa-floppy-disk"></i> SAVE CHANGES
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Right Column: Owner / Telegram & Order Details --}}
    <div style="display: flex; flex-direction: column; gap: 20px;">
        {{-- Owner & Telegram Panel --}}
        <div class="cyber-panel">
            <div class="cyber-panel-header">
                <div class="cyber-panel-title">
                    <i class="fa-brands fa-telegram" style="color: #48cae4;"></i>
                    OWNER & TELEGRAM IDENTITY
                </div>
                @if ($domain->user)
                    <a href="{{ route('user.show', $domain->user->id) }}" class="cyber-btn cyber-btn-secondary cyber-btn-xs">
                        <i class="fa-solid fa-user"></i> VIEW USER
                    </a>
                @endif
            </div>

            @if ($domain->user)
                <table class="info-table">
                    <tbody>
                        <tr>
                            <th style="width: 150px;">OPERATIVE NAME</th>
                            <td style="font-weight: 700; color: #ffffff;">{{ $domain->user->name }}</td>
                        </tr>
                        <tr>
                            <th>TELEGRAM LINK</th>
                            <td>
                                @if ($domain->user->telegram_username)
                                    <a href="https://t.me/{{ ltrim($domain->user->telegram_username, '@') }}" target="_blank" rel="noopener noreferrer" style="color: #48cae4; font-family: var(--font-mono); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fa-brands fa-telegram"></i>
                                        https://t.me/{{ ltrim($domain->user->telegram_username, '@') }}
                                    </a>
                                @elseif ($domain->user->telegram_id)
                                    <a href="https://t.me/{{ $domain->user->telegram_id }}" target="_blank" rel="noopener noreferrer" style="color: #48cae4; font-family: var(--font-mono); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fa-brands fa-telegram"></i>
                                        https://t.me/{{ $domain->user->telegram_id }}
                                    </a>
                                @else
                                    <span style="color: var(--text-muted);">Not linked</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>TELEGRAM CHAT ID</th>
                            <td style="font-family: var(--font-mono); color: #ffffff;">
                                {{ $domain->user->telegram_id ?: 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <th>EMAIL ADDRESS</th>
                            <td style="font-family: var(--font-mono); color: #00ff66;">{{ $domain->user->email }}</td>
                        </tr>
                        <tr>
                            <th>ACCOUNT KEY</th>
                            <td>
                                @if ($domain->user->account_key)
                                    <code style="font-family: var(--font-mono); font-weight: 700; color: #ffd166; background: rgba(255,209,102,0.1); border: 1px solid rgba(255,209,102,0.3); padding: 2px 6px; border-radius: 2px;">
                                        {{ $domain->user->account_key }}
                                    </code>
                                @else
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            @else
                <div style="padding: 20px; color: var(--text-muted); font-family: var(--font-mono); font-size: 0.82rem;">
                    Associated user record (#{{ $domain->user_id ?? 'N/A' }}) not found.
                </div>
            @endif
        </div>

        {{-- Bound Order / License Quota Panel --}}
        <div class="cyber-panel">
            <div class="cyber-panel-header">
                <div class="cyber-panel-title">
                    <i class="fa-solid fa-file-invoice-dollar" style="color: var(--red-primary);"></i>
                    ASSOCIATED LICENSE ORDER
                </div>
                @if ($domain->order)
                    <a href="{{ route('order.show', $domain->order->id) }}" class="cyber-btn cyber-btn-secondary cyber-btn-xs">
                        <i class="fa-solid fa-eye"></i> VIEW ORDER
                    </a>
                @endif
            </div>

            @if ($domain->order)
                <table class="info-table">
                    <tbody>
                        <tr>
                            <th style="width: 150px;">INVOICE</th>
                            <td>
                                <code style="font-family: var(--font-mono); font-weight: 700; color: #ffffff;">
                                    {{ $domain->order->invoice }}
                                </code>
                            </td>
                        </tr>
                        <tr>
                            <th>ORDER STATUS</th>
                            <td>
                                <span class="badge-status b-ok">{{ strtoupper($domain->order->status) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>DOMAIN QUOTA</th>
                            <td style="font-family: var(--font-mono); color: #00ff66; font-weight: 700;">
                                {{ $domain->order->domain_quota ?? 3 }} domains allowed
                            </td>
                        </tr>
                    </tbody>
                </table>
            @else
                <div style="padding: 20px; color: var(--text-muted); font-family: var(--font-mono); font-size: 0.82rem;">
                    Bound directly to product license{{ $domain->order_id ? ' (Order #' . $domain->order_id . ')' : '' }}.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
