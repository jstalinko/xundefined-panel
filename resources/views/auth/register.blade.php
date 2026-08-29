@extends('layouts.auth')

@section('title', 'Clearance Registration')

@section('content')
<header class="login-header">
    <div class="login-logo-badge">
        <span class="logo-bracket">[</span><span class="logo-symbol">X/U</span><span class="logo-bracket">]</span>
    </div>
    <h1 class="login-title">CLEARANCE REGISTRATION</h1>
    <p class="login-subtitle">JOIN THE MAINFRAME</p>
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

<form class="login-form" action="{{ route('register') }}" method="POST" id="registerForm">
    @csrf

    {{-- Operative Name/Handle --}}
    <div class="form-group">
        <label for="name" class="form-label">
            <i class="fa-solid fa-user-ninja"></i>
            OPERATIVE NAME / HANDLE
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

    {{-- Identity Email with Live Exists Check --}}
    <div class="form-group">
        <label for="email" class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
            <span>
                <i class="fa-solid fa-envelope"></i>
                IDENTITY EMAIL
            </span>
            <span id="emailStatusBadge" style="font-size: 0.68rem; font-family: var(--font-mono); display: none;"></span>
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
        <div id="emailFeedback" style="font-family: var(--font-mono); font-size: 0.72rem; margin-top: 4px; display: none;"></div>
    </div>

    {{-- Invite Key with Live Verification Check --}}
    <div class="form-group">
        <label for="invite_key" class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
            <span>
                <i class="fa-solid fa-ticket"></i>
                INVITE KEY
            </span>
            <span id="inviteStatusBadge" style="color: var(--red-primary); font-size: 0.65rem;">[ REQUIRED ]</span>
        </label>
        <div class="input-container">
            <input 
                type="text" 
                id="invite_key" 
                name="invite_key" 
                class="cyber-input" 
                placeholder="XU-XXXX-XXXX" 
                value="{{ old('invite_key') }}" 
                required 
                maxlength="64"
                style="letter-spacing: 0.12em; text-transform: uppercase;"
                autocomplete="off"
            >
        </div>
        <div id="inviteFeedback" style="font-family: var(--font-mono); font-size: 0.72rem; margin-top: 4px; display: none;"></div>
    </div>

    {{-- Password with Strength Meter --}}
    <div class="form-group">
        <label for="password" class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
            <span>
                <i class="fa-solid fa-lock"></i>
                SECURITY PASSCODE
            </span>
            <span id="passStrengthLabel" style="font-family: var(--font-mono); font-size: 0.68rem; font-weight: 700; color: var(--text-muted);">
                MIN 8 CHARS
            </span>
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

        {{-- Strength Bar --}}
        <div style="margin-top: 6px; background: rgba(255,255,255,0.06); height: 4px; border-radius: 2px; overflow: hidden;">
            <div id="passStrengthBar" style="height: 100%; width: 0%; transition: all 0.3s ease; background: var(--red-primary);"></div>
        </div>

        {{-- Password Requirements Checklist --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; margin-top: 8px; font-family: var(--font-mono); font-size: 0.68rem;">
            <div id="chk-len" style="color: var(--text-muted);">
                <i class="fa-solid fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i> 8+ characters
            </div>
            <div id="chk-case" style="color: var(--text-muted);">
                <i class="fa-solid fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i> Upper & lower
            </div>
            <div id="chk-num" style="color: var(--text-muted);">
                <i class="fa-solid fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i> Number (0-9)
            </div>
            <div id="chk-sym" style="color: var(--text-muted);">
                <i class="fa-solid fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i> Symbol (!@#$)
            </div>
        </div>
    </div>

    {{-- Password Confirmation with Match Checker --}}
    <div class="form-group">
        <label for="password_confirmation" class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
            <span>
                <i class="fa-solid fa-shield-halved"></i>
                CONFIRM PASSCODE
            </span>
            <span id="matchStatusLabel" style="font-family: var(--font-mono); font-size: 0.68rem; font-weight: 700; display: none;"></span>
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
        <div id="matchFeedback" style="font-family: var(--font-mono); font-size: 0.72rem; margin-top: 4px; display: none;"></div>
    </div>

    {{-- Submit Button --}}
    <button type="submit" class="cyber-submit-btn" id="submitRegBtn" style="margin-top: 6px;">
        <span>REGISTER & INITIALIZE</span>
        <i class="fa-solid fa-user-plus btn-icon"></i>
    </button>

    {{-- Switch to Login --}}
    <div style="text-align: center; margin-top: 12px;">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // DOM Elements
    const form = document.getElementById('registerForm');
    const emailInput = document.getElementById('email');
    const emailFeedback = document.getElementById('emailFeedback');
    const emailStatusBadge = document.getElementById('emailStatusBadge');

    const inviteInput = document.getElementById('invite_key');
    const inviteFeedback = document.getElementById('inviteFeedback');
    const inviteStatusBadge = document.getElementById('inviteStatusBadge');

    const passInput = document.getElementById('password');
    const passConfirmInput = document.getElementById('password_confirmation');
    const passStrengthBar = document.getElementById('passStrengthBar');
    const passStrengthLabel = document.getElementById('passStrengthLabel');
    const matchStatusLabel = document.getElementById('matchStatusLabel');
    const matchFeedback = document.getElementById('matchFeedback');

    const chkLen = document.getElementById('chk-len');
    const chkCase = document.getElementById('chk-case');
    const chkNum = document.getElementById('chk-num');
    const chkSym = document.getElementById('chk-sym');

    let emailDebounceTimer = null;
    let inviteDebounceTimer = null;
    let isEmailAvailable = true;

    // 1. Live Email Exists Check
    function checkEmailAvailability() {
        const email = emailInput.value.trim();
        if (!email) {
            emailFeedback.style.display = 'none';
            emailStatusBadge.style.display = 'none';
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            emailFeedback.style.display = 'block';
            emailFeedback.style.color = '#ff5252';
            emailFeedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Invalid email format.';
            emailStatusBadge.style.display = 'inline';
            emailStatusBadge.style.color = '#ff5252';
            emailStatusBadge.textContent = '[ INVALID FORMAT ]';
            isEmailAvailable = false;
            return;
        }

        fetch(`{{ route('auth.check-email') }}?email=${encodeURIComponent(email)}`)
            .then(res => res.json())
            .then(data => {
                emailFeedback.style.display = 'block';
                emailStatusBadge.style.display = 'inline';

                if (data.exists) {
                    emailFeedback.style.color = '#ff5252';
                    emailFeedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + data.message;
                    emailStatusBadge.style.color = '#ff5252';
                    emailStatusBadge.textContent = '[ ALREADY EXISTS ]';
                    isEmailAvailable = false;
                } else {
                    emailFeedback.style.color = '#00ff66';
                    emailFeedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message;
                    emailStatusBadge.style.color = '#00ff66';
                    emailStatusBadge.textContent = '[ AVAILABLE ]';
                    isEmailAvailable = true;
                }
            })
            .catch(() => {});
    }

    if (emailInput) {
        emailInput.addEventListener('input', () => {
            clearTimeout(emailDebounceTimer);
            emailDebounceTimer = setTimeout(checkEmailAvailability, 300);
        });
        emailInput.addEventListener('blur', checkEmailAvailability);
        if (emailInput.value) {
            checkEmailAvailability();
        }
    }

    // 2. Live Invite Code Verification Check
    function checkInviteCode() {
        const code = inviteInput.value.trim().toUpperCase();
        if (!code) {
            inviteFeedback.style.display = 'none';
            inviteStatusBadge.textContent = '[ REQUIRED ]';
            inviteStatusBadge.style.color = 'var(--red-primary)';
            return;
        }

        fetch(`{{ route('auth.check-invite') }}?code=${encodeURIComponent(code)}`)
            .then(res => res.json())
            .then(data => {
                inviteFeedback.style.display = 'block';

                if (data.valid) {
                    inviteFeedback.style.color = '#00ff66';
                    inviteFeedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message;
                    inviteStatusBadge.style.color = '#00ff66';
                    inviteStatusBadge.textContent = '[ VERIFIED ]';
                } else {
                    inviteFeedback.style.color = '#ff5252';
                    inviteFeedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + data.message;
                    inviteStatusBadge.style.color = '#ff5252';
                    inviteStatusBadge.textContent = '[ INVALID CODE ]';
                }
            })
            .catch(() => {});
    }

    if (inviteInput) {
        inviteInput.addEventListener('input', () => {
            clearTimeout(inviteDebounceTimer);
            inviteDebounceTimer = setTimeout(checkInviteCode, 300);
        });
        inviteInput.addEventListener('blur', checkInviteCode);
        if (inviteInput.value) {
            checkInviteCode();
        }
    }

    // 3. Password Strength Calculation
    function updatePasswordStrength() {
        const val = passInput.value;
        let score = 0;

        const hasLen = val.length >= 8;
        const hasUpper = /[A-Z]/.test(val);
        const hasLower = /[a-z]/.test(val);
        const hasCase = hasUpper && hasLower;
        const hasNum = /[0-9]/.test(val);
        const hasSym = /[^A-Za-z0-9]/.test(val);

        // Update Checklist UI
        updateChk(chkLen, hasLen);
        updateChk(chkCase, hasCase);
        updateChk(chkNum, hasNum);
        updateChk(chkSym, hasSym);

        if (hasLen) score += 25;
        if (hasCase) score += 25;
        if (hasNum) score += 25;
        if (hasSym) score += 25;

        // Visual Progress & Label
        passStrengthBar.style.width = score + '%';

        if (val.length === 0) {
            passStrengthLabel.textContent = 'MIN 8 CHARS';
            passStrengthLabel.style.color = 'var(--text-muted)';
            passStrengthBar.style.background = 'var(--red-primary)';
        } else if (score <= 25) {
            passStrengthLabel.textContent = 'WEAK';
            passStrengthLabel.style.color = '#ff5252';
            passStrengthBar.style.background = '#ff5252';
        } else if (score <= 50) {
            passStrengthLabel.textContent = 'FAIR';
            passStrengthLabel.style.color = '#ffd166';
            passStrengthBar.style.background = '#ffd166';
        } else if (score <= 75) {
            passStrengthLabel.textContent = 'STRONG';
            passStrengthLabel.style.color = '#00ff66';
            passStrengthBar.style.background = '#00ff66';
        } else {
            passStrengthLabel.textContent = 'CYBER-GRADE';
            passStrengthLabel.style.color = '#48cae4';
            passStrengthBar.style.background = '#48cae4';
        }

        checkPasswordMatch();
    }

    function updateChk(el, pass) {
        if (!el) return;
        if (pass) {
            el.style.color = '#00ff66';
            const icon = el.querySelector('i');
            if (icon) icon.className = 'fa-solid fa-circle-check';
        } else {
            el.style.color = 'var(--text-muted)';
            const icon = el.querySelector('i');
            if (icon) icon.className = 'fa-solid fa-circle';
        }
    }

    // 4. Password Confirmation Match Checker
    function checkPasswordMatch() {
        const pass = passInput.value;
        const confirm = passConfirmInput.value;

        if (!confirm) {
            matchFeedback.style.display = 'none';
            matchStatusLabel.style.display = 'none';
            return;
        }

        matchFeedback.style.display = 'block';
        matchStatusLabel.style.display = 'inline';

        if (pass === confirm) {
            matchFeedback.style.color = '#00ff66';
            matchFeedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> Passcodes match.';
            matchStatusLabel.style.color = '#00ff66';
            matchStatusLabel.textContent = '[ MATCH ]';
        } else {
            matchFeedback.style.color = '#ff5252';
            matchFeedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Passcodes do not match.';
            matchStatusLabel.style.color = '#ff5252';
            matchStatusLabel.textContent = '[ MISMATCH ]';
        }
    }

    if (passInput) {
        passInput.addEventListener('input', updatePasswordStrength);
    }
    if (passConfirmInput) {
        passConfirmInput.addEventListener('input', checkPasswordMatch);
    }

    // 5. Submit Form Validation Guard
    if (form) {
        form.addEventListener('submit', function (e) {
            if (passInput.value !== passConfirmInput.value) {
                e.preventDefault();
                matchFeedback.style.display = 'block';
                matchFeedback.style.color = '#ff5252';
                matchFeedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Passcodes do not match. Please confirm your passcode.';
                passConfirmInput.focus();
                return false;
            }

            if (!isEmailAvailable) {
                e.preventDefault();
                emailInput.focus();
                return false;
            }
        });
    }
});
</script>
@endpush
