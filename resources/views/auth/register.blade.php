@extends('layouts.auth')

@section('title', 'Clearance Registration')

@section('content')
<header class="login-header">
    <div class="login-logo-badge">
        <span class="logo-bracket">[</span><span class="logo-symbol">X/U</span><span class="logo-bracket">]</span>
    </div>
    <h1 class="login-title">CLEARANCE REGISTRATION</h1>
    <p class="login-subtitle">JOIN WITH US !</p>
    
</header>

{{-- Validation Error Alert --}}
@if ($errors->any())
    <div class="cyber-alert" role="alert">
        <i class="fa-solid fa-triangle-exclamation cyber-alert-icon"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title">INITIALIZATION ERROR</span>
            @foreach ($errors->all() as $error)
                <span class="cyber-alert-msg">{{ $error }}</span>
            @endforeach
        </div>
    </div>
@endif

<form class="login-form" action="{{ route('register') }}" method="POST">
    @csrf

    {{-- Operative Name/Handle --}}
    <div class="form-group">
        <label for="name" class="form-label">
            <i class="fa-solid fa-user-ninja"></i>
             NAME
        </label>
        <div class="input-container">
            <input 
                type="text" 
                id="name" 
                name="name" 
                class="cyber-input" 
                placeholder="Agent_Null / CipherZero" 
                value="{{ old('name') }}" 
                required 
                autofocus 
                autocomplete="name"
            >
        </div>
    </div>

    {{-- Identity Email --}}
    <div class="form-group">
        <label for="email" class="form-label">
            <i class="fa-solid fa-envelope"></i>
            EMAIL
        </label>
        <div class="input-container">
            <input 
                type="email" 
                id="email" 
                name="email" 
                class="cyber-input" 
                placeholder="agent@xundefined.local" 
                value="{{ old('email') }}" 
                required 
                autocomplete="email"
            >
        </div>
    </div>

    {{-- Invite Key --}}
    <div class="form-group">
        <label for="invite_key" class="form-label" style="display: flex; justify-content: space-between;">
            <span>
                <i class="fa-solid fa-ticket"></i>
                INVITE KEY
            </span>
            <span style="color: var(--red-primary); font-size: 0.65rem;">[ REQUIRED ]</span>
        </label>
        <div class="input-container">
            <input 
                type="text" 
                id="invite_key" 
                name="invite_key" 
                class="cyber-input" 
                placeholder="ABCDEFG (16 chars max)" 
                value="{{ old('invite_key') }}" 
                required 
                maxlength="16"
                style="letter-spacing: 0.12em; text-transform: uppercase;"
                autocomplete="off"
            >
        </div>
    </div>

    {{-- Password --}}
    <div class="form-group">
        <label for="password" class="form-label">
            <i class="fa-solid fa-lock"></i>
            SECURITY PASSCODE (MIN 8 CHARS)
        </label>
        <div class="input-container">
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="cyber-input" 
                placeholder="••••••••••••" 
                required 
                autocomplete="new-password"
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

    {{-- Password Confirmation --}}
    <div class="form-group">
        <label for="password_confirmation" class="form-label">
            <i class="fa-solid fa-shield-halved"></i>
            CONFIRM  PASSCODE
        </label>
        <div class="input-container">
            <input 
                type="password" 
                id="password_confirmation" 
                name="password_confirmation" 
                class="cyber-input" 
                placeholder="••••••••••••" 
                required 
                autocomplete="new-password"
            >
            <button 
                type="button" 
                class="input-action-btn" 
                onclick="togglePass('password_confirmation', this)" 
                aria-label="Toggle confirmation password visibility"
                title="Toggle passcode visibility"
            >
                <i class="fa-solid fa-eye"></i>
            </button>
        </div>
    </div>

    {{-- Submit Button --}}
    <button type="submit" class="cyber-submit-btn" style="margin-top: 4px;">
        <span>REGISTER </span>
        <i class="fa-solid fa-user-plus btn-icon"></i>
    </button>

    {{-- Switch to Login --}}
    <div style="text-align: center; margin-top: 8px;">
        <span style="font-family: var(--font-mono); font-size: 0.76rem; color: var(--text-muted);">
            ALREADY HOLD CLEARANCE?
        </span>
        <a 
            href="{{ route('login') }}" 
            style="font-family: var(--font-mono); font-size: 0.76rem; color: var(--red-primary); font-weight: 700; text-decoration: none; margin-left: 6px; letter-spacing: 0.04em;"
            onmouseover="this.style.textDecoration='underline'" 
            onmouseout="this.style.textDecoration='none'"
        >
            [ LOGIN TO MAINFRAME &rarr; ]
        </a>
    </div>
</form>
@endsection
