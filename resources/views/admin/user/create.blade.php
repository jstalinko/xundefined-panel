@extends('layouts.dashboard')

@section('title', 'Create Operative - Admin')
@section('page-title', 'CREATE USER')

@section('content')
<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);">
        <a href="{{ route('admin.dashboard') }}" style="color: var(--text-secondary); text-decoration: none;">Admin Hub</a>
        <span>/</span>
        <a href="{{ route('user.index') }}" style="color: var(--text-secondary); text-decoration: none;">Users</a>
        <span>/</span>
        <span style="color: #ffffff;">Create</span>
    </div>

    <a href="{{ route('user.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
        <i class="fa-solid fa-arrow-left"></i> BACK TO DIRECTORY
    </a>
</div>

{{-- Validation Errors --}}
@if (isset($errors) && $errors->any())
    <div class="cyber-alert" role="alert" style="border-color: var(--red-primary); background: rgba(255, 23, 68, 0.1); margin-bottom: 20px;">
        <i class="fa-solid fa-triangle-exclamation cyber-alert-icon" style="color: var(--red-primary);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--red-primary);">VALIDATION ERROR</span>
            <ul style="margin: 4px 0 0 16px; font-size: 0.82rem; color: #ff80ab;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<form action="{{ route('user.store') }}" method="POST">
    @csrf

    <div class="cyber-panel">
        <div class="cyber-panel-header">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-user-plus"></i>
                NEW OPERATIVE ACCOUNT
            </div>
            <span class="badge-status b-ok">CLEARANCE ENROLLMENT</span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;" class="split-grid">
            <div>
                <div class="cyber-form-group">
                    <label class="cyber-label" for="name">
                        <i class="fa-solid fa-user"></i> OPERATIVE NAME / HANDLE *
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="cyber-input" 
                        placeholder="e.g. Agent_Cipher" 
                        value="{{ old('name') }}" 
                        required
                    >
                </div>

                <div class="cyber-form-group" style="margin-top: 16px;">
                    <label class="cyber-label" for="email">
                        <i class="fa-solid fa-envelope"></i> IDENTITY EMAIL *
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="cyber-input" 
                        placeholder="e.g. operative@xundefined.local" 
                        value="{{ old('email') }}" 
                        required
                    >
                </div>
            </div>

            <div>
                <div class="cyber-form-group">
                    <label class="cyber-label" for="password">
                        <i class="fa-solid fa-lock"></i> SECURITY PASSCODE *
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="cyber-input" 
                        placeholder="Minimum 8 characters" 
                        required
                    >
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 16px;">
                    <div class="cyber-form-group">
                        <label class="cyber-label" for="role">
                            <i class="fa-solid fa-shield-halved"></i> CLEARANCE ROLE *
                        </label>
                        <select id="role" name="role" class="cyber-input" required>
                            <option value="2" {{ old('role') == '2' ? 'selected' : '' }}>Cyber Operative (Member)</option>
                            <option value="1" {{ old('role') == '1' ? 'selected' : '' }}>System Administrator</option>
                        </select>
                    </div>

                    <div class="cyber-form-group">
                        <label class="cyber-label" for="invite_key">
                            <i class="fa-solid fa-ticket"></i> INVITE KEY
                        </label>
                        <input 
                            type="text" 
                            id="invite_key" 
                            name="invite_key" 
                            class="cyber-input" 
                            placeholder="XU-GENESIS" 
                            value="{{ old('invite_key', 'XU-GENESIS') }}"
                            style="text-transform: uppercase;"
                        >
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 16px;">
            <a href="{{ route('user.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-md">
                CANCEL
            </a>
            <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-md">
                <i class="fa-solid fa-check"></i> CREATE OPERATIVE
            </button>
        </div>
    </div>
</form>
@endsection
