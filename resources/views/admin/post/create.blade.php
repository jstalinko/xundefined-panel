@extends('layouts.dashboard')

@section('title', 'Write Article - Admin')
@section('page-title', 'NEW ARTICLE')

@section('content')
<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);">
        <a href="{{ route('admin.dashboard') }}" style="color: var(--text-secondary); text-decoration: none;">Admin Hub</a>
        <span>/</span>
        <a href="{{ route('post.index') }}" style="color: var(--text-secondary); text-decoration: none;">Posts</a>
        <span>/</span>
        <span style="color: #ffffff;">Write</span>
    </div>

    <a href="{{ route('post.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
        <i class="fa-solid fa-arrow-left"></i> BACK TO POSTS
    </a>
</div>

{{-- Validation Errors --}}
@if ($errors->any())
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

<form action="{{ route('post.store') }}" method="POST">
    @csrf

    <div class="cyber-panel">
        <div class="cyber-panel-header">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-pen-nib"></i>
                ARTICLE COMPOSER
            </div>
            <span class="badge-status b-ok">NEW DRAFT / POST</span>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;" class="split-grid">
            <div>
                <div class="cyber-form-group">
                    <label class="cyber-label" for="title">
                        <i class="fa-solid fa-heading"></i> ARTICLE TITLE *
                    </label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="cyber-input" 
                        placeholder="e.g. Platform v2.5 Security Update" 
                        value="{{ old('title') }}" 
                        required
                    >
                </div>

                <div class="cyber-form-group">
                    <label class="cyber-label" for="content">
                        <i class="fa-solid fa-align-left"></i> CONTENT / BODY *
                    </label>
                    <textarea 
                        id="content" 
                        name="content" 
                        class="cyber-input" 
                        rows="12" 
                        placeholder="Write your article markdown or formatted copy here..."
                        required
                    >{{ old('content') }}</textarea>
                </div>
            </div>

            <div>
                <div class="cyber-form-group">
                    <label class="cyber-label" for="category">
                        <i class="fa-solid fa-tag"></i> CATEGORY *
                    </label>
                    <select id="category" name="category" class="cyber-input" required>
                        <option value="announcement" {{ old('category') === 'announcement' ? 'selected' : '' }}>Announcement</option>
                        <option value="news" {{ old('category') === 'news' ? 'selected' : '' }}>News</option>
                        <option value="changelog" {{ old('category') === 'changelog' ? 'selected' : '' }}>Changelog</option>
                        <option value="tutorial" {{ old('category') === 'tutorial' ? 'selected' : '' }}>Tutorial</option>
                        <option value="promotion" {{ old('category') === 'promotion' ? 'selected' : '' }}>Promotion</option>
                        <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>General</option>
                    </select>
                </div>

                <div class="cyber-form-group" style="margin-top: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #ffffff; font-family: var(--font-mono); font-size: 0.86rem;">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', true) ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: var(--red-primary);">
                        <span>PUBLISHED (Immediately live on xNotes)</span>
                    </label>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 16px;">
            <a href="{{ route('post.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-md">
                CANCEL
            </a>
            <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-md">
                <i class="fa-solid fa-check"></i> PUBLISH ARTICLE
            </button>
        </div>
    </div>
</form>
@endsection
