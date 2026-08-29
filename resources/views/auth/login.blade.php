@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<header class="login-header">
    <div class="login-logo-badge">
        <span class="logo-bracket">[</span><span class="logo-symbol">X/U</span><span class="logo-bracket">]</span>
    </div>
    <h1 class="login-title">XUNDEFINED DASHBOARD</h1>
    <p class="login-subtitle">GATEWAY</p>
   
</header>

{{-- Session Status or Notification Alert --}}
@if (session('status'))
    <div class="cyber-alert" role="alert" style="border-color: var(--status-online); background: rgba(0, 255, 102, 0.08);">
        <i class="fa-solid fa-circle-check cyber-alert-icon" style="color: var(--status-online);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--status-online);">SYSTEM TELEMETRY</span>
            <span class="cyber-alert-msg">{{ session('status') }}</span>
        </div>
    </div>
@endif

{{-- Validation Error Alert --}}
@if ($errors->any())
    <div class="cyber-alert" role="alert">
        <i class="fa-solid fa-triangle-exclamation cyber-alert-icon"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title">ACCESS VIOLATION</span>
            @foreach ($errors->all() as $error)
                <span class="cyber-alert-msg">{{ $error }}</span>
            @endforeach
        </div>
    </div>
@endif

<form class="login-form" action="{{ route('login') }}" method="POST">
    @csrf

    {{-- Email Field --}}
    <div class="form-group">
        <label for="email" class="form-label">
            <i class="fa-solid fa-fingerprint"></i>
            OPERATIVE IDENTITY (EMAIL)
        </label>
        <div class="input-container">
            <input 
                type="email" 
                id="email" 
                name="email" 
                class="cyber-input" 
                placeholder="operative@xundefined.local" 
                value="{{ old('email') }}" 
                required 
                autofocus 
                autocomplete="email"
            >
        </div>
    </div>

    {{-- Password Field --}}
    <div class="form-group">
        <label for="password" class="form-label">
            <i class="fa-solid fa-key"></i>
            SECURITY PASSCODE
        </label>
        <div class="input-container">
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="cyber-input" 
                placeholder="••••••••••••" 
                required 
                autocomplete="current-password"
            >
            <button 
                type="button" 
                class="input-action-btn" 
                onclick="togglePass('password', this)" 
                aria-label="Toggle password visibility"
                title="Toggle passcode visibility"
            >
                <i class="fa-solid fa-eye"></i>
            </button>
        </div>
    </div>

    {{-- Remember Me & Help --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: -4px;">
        <label class="cyber-checkbox-label" style="padding: 0; cursor: pointer;">
            <input type="checkbox" name="remember" id="remember" class="cyber-checkbox" {{ old('remember') ? 'checked' : '' }}>
            <span>Persist Link Session</span>
        </label>
    </div>

    {{-- Submit Button --}}
    <button type="submit" class="cyber-submit-btn">
        <span>AUTHORIZE ACCESS</span>
        <i class="fa-solid fa-arrow-right-to-bracket btn-icon"></i>
    </button>

    {{-- Switch to Register --}}
    <div style="text-align: center; margin-top: 8px;">
        <span style="font-family: var(--font-mono); font-size: 0.76rem; color: var(--text-muted);">
            NO CLEARANCE RECORD?
        </span>
        <a 
            href="{{ route('register') }}" 
            style="font-family: var(--font-mono); font-size: 0.76rem; color: var(--red-primary); font-weight: 700; text-decoration: none; margin-left: 6px; letter-spacing: 0.04em;"
            onmouseover="this.style.textDecoration='underline'" 
            onmouseout="this.style.textDecoration='none'"
        >
            [ INITIALIZE WITH INVITE KEY &rarr; ]
        </a>
    </div>
</form>
@endsection
