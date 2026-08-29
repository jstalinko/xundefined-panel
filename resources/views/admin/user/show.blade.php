@extends('layouts.dashboard')

@section('title', 'Operative Profile: ' . $targetUser->name . ' - Admin')
@section('page-title', 'USER PROFILE')

@section('content')
<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);">
        <a href="{{ route('admin.dashboard') }}" style="color: var(--text-secondary); text-decoration: none;">Admin Hub</a>
        <span>/</span>
        <a href="{{ route('user.index') }}" style="color: var(--text-secondary); text-decoration: none;">Users</a>
        <span>/</span>
        <span style="color: #ffffff;">#{{ $targetUser->id }} {{ $targetUser->name }}</span>
    </div>

    <div style="display: flex; gap: 8px;">
        <a href="{{ route('user.edit', $targetUser->id) }}" class="cyber-btn cyber-btn-primary cyber-btn-sm">
            <i class="fa-solid fa-pen-to-square"></i> EDIT
        </a>
        <a href="{{ route('user.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
            <i class="fa-solid fa-arrow-left"></i> BACK
        </a>
    </div>
</div>

<div class="split-grid" style="align-items: start;">
    {{-- Left Column: Specifications --}}
    <div class="cyber-panel">
        <div class="cyber-panel-header">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-id-badge"></i>
                IDENTITY SPECIFICATIONS
            </div>
            @if ((int) $targetUser->role === 1)
                <span class="badge-status b-err" style="background: rgba(255,23,68,0.15); border-color: var(--red-primary);">
                    <i class="fa-solid fa-shield-halved"></i> SYSTEM ADMIN
                </span>
            @else
                <span class="badge-status b-ok">
                    <i class="fa-solid fa-user"></i> OPERATIVE MEMBER
                </span>
            @endif
        </div>

        <table class="info-table">
            <tbody>
                <tr>
                    <th style="width: 140px;">USER ID</th>
                    <td style="font-family: var(--font-mono); font-weight: 700; color: #ffffff;">#{{ $targetUser->id }}</td>
                </tr>
                <tr>
                    <th>OPERATIVE NAME</th>
                    <td style="font-weight: 700; color: #ffffff;">{{ $targetUser->name }}</td>
                </tr>
                <tr>
                    <th>IDENTITY EMAIL</th>
                    <td style="font-family: var(--font-mono); color: #00ff66;">{{ $targetUser->email }}</td>
                </tr>
                <tr>
                    <th>CLEARANCE ROLE</th>
                    <td>
                        <span style="font-family: var(--font-mono); font-weight: 700; color: #ffffff;">
                            {{ $targetUser->role_name }} (Role Code: {{ $targetUser->role }})
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>INVITE KEY</th>
                    <td>
                        @if ($targetUser->invite_key)
                            <code style="font-family: var(--font-mono); font-weight: 700; color: #ffd166; background: rgba(255,209,102,0.1); border: 1px solid rgba(255,209,102,0.3); padding: 2px 6px; border-radius: 2px;">
                                {{ $targetUser->invite_key }}
                            </code>
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>JOINED TIMESTAMP</th>
                    <td style="font-family: var(--font-mono); font-size: 0.78rem; color: var(--text-secondary);">
                        {{ $targetUser->created_at ? $targetUser->created_at->format('Y-m-d H:i:s T') : 'N/A' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Right Column: Connected Domains & Orders --}}
    <div style="display: flex; flex-direction: column; gap: 20px;">
        {{-- Domains Panel --}}
        <div class="cyber-panel">
            <div class="cyber-panel-header">
                <div class="cyber-panel-title">
                    <i class="fa-solid fa-globe"></i>
                    CONNECTED DOMAINS ({{ $targetUser->domains->count() }})
                </div>
            </div>

            @if ($targetUser->domains->isEmpty())
                <p style="color: var(--text-muted); padding: 18px 0; text-align: center; font-size: 0.82rem;">
                    No domains connected to this operative yet.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach ($targetUser->domains as $dom)
                        <div style="background: rgba(14, 11, 16, 0.7); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: var(--radius-sm); padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-globe" style="color: var(--red-primary);"></i>
                                <span style="font-family: var(--font-mono); font-weight: 700; color: #ffffff;">{{ $dom->domain }}</span>
                            </div>
                            <span class="badge-status b-ok" style="font-size: 0.7rem;">ACTIVE</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Orders Panel --}}
        <div class="cyber-panel">
            <div class="cyber-panel-header">
                <div class="cyber-panel-title">
                    <i class="fa-solid fa-cart-shopping"></i>
                    ORDER TRANSACTIONS ({{ $targetUser->orders->count() }})
                </div>
            </div>

            @if ($targetUser->orders->isEmpty())
                <p style="color: var(--text-muted); padding: 18px 0; text-align: center; font-size: 0.82rem;">
                    No orders recorded for this operative.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach ($targetUser->orders as $ord)
                        <div style="background: rgba(14, 11, 16, 0.7); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: var(--radius-sm); padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <code style="color: var(--red-primary); font-weight: 700; font-family: var(--font-mono);">{{ $ord->invoice }}</code>
                                <div style="font-size: 0.76rem; color: var(--text-secondary); margin-top: 2px;">{{ $ord->product->name ?? 'Product #' . $ord->product_id }}</div>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-family: var(--font-mono); font-weight: 800; color: #00ff66; font-size: 0.84rem;">
                                    Rp {{ number_format($ord->price, 0, ',', '.') }}
                                </span>
                                <div>
                                    <span class="badge-status b-ok" style="font-size: 0.68rem;">{{ strtoupper($ord->status) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
