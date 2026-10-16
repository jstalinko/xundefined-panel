@extends('layouts.dashboard')

@section('title', 'Edit Free Tool - Admin')
@section('page-title', 'EDIT FREE TOOL')

@section('content')
<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);">
        <a href="{{ route('admin.dashboard') }}" style="color: var(--text-secondary); text-decoration: none;">Admin Hub</a>
        <span>/</span>
        <a href="{{ route('free-tool.index') }}" style="color: var(--text-secondary); text-decoration: none;">Free Tools</a>
        <span>/</span>
        <span style="color: #ffffff;">Edit: {{ $freeTool->name }}</span>
    </div>

    <a href="{{ route('free-tool.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
        <i class="fa-solid fa-arrow-left"></i> BACK TO FREE TOOLS
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

@php
    $selectedType = old('type', $freeTool->type);
@endphp

<form action="{{ route('free-tool.update', $freeTool->id) }}" method="POST" enctype="multipart/form-data" id="freeToolEditForm">
    @csrf
    @method('PUT')

    <div class="cyber-panel">
        <div class="cyber-panel-header">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-pen-to-square"></i>
                EDIT FREE TOOL // {{ strtoupper($freeTool->name) }}
            </div>
            <span class="badge-status b-ok">{{ strtoupper($freeTool->type) }}</span>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;" class="split-grid">
            <div>
                <div class="cyber-form-group">
                    <label class="cyber-label" for="name">
                        <i class="fa-solid fa-signature"></i> TOOL NAME *
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="cyber-input" 
                        value="{{ old('name', $freeTool->name) }}" 
                        required
                    >
                </div>

                {{-- Dynamic URL Input Section --}}
                <div class="cyber-form-group" id="urlInputGroup" style="margin-top: 16px; {{ $selectedType === 'url' ? '' : 'display: none;' }}">
                    <label class="cyber-label" for="url">
                        <i class="fa-solid fa-link"></i> TOOL URL *
                    </label>
                    <input 
                        type="url" 
                        id="url" 
                        name="url" 
                        class="cyber-input" 
                        placeholder="https://example.com/tool" 
                        value="{{ old('url', $freeTool->url) }}"
                    >
                    <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); margin-top: 6px;">
                        Enter full URL starting with <code>https://</code> or <code>http://</code>.
                    </div>
                </div>

                {{-- Dynamic File Upload Section --}}
                <div class="cyber-form-group" id="fileInputGroup" style="margin-top: 16px; {{ $selectedType === 'file' ? '' : 'display: none;' }}">
                    <label class="cyber-label" for="file">
                        <i class="fa-solid fa-file-arrow-up"></i> UPLOAD TOOL FILE (.txt, .zip, .rar, .tar.gz) {{ empty($freeTool->file_path) ? '*' : '(Optional to replace)' }}
                    </label>

                    @if ($freeTool->file_path)
                        <div style="margin-bottom: 10px; padding: 10px 12px; background: rgba(255, 209, 102, 0.08); border: 1px solid rgba(255, 209, 102, 0.35); border-radius: var(--radius-sm); font-family: var(--font-mono); font-size: 0.78rem; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                            <div>
                                <i class="fa-solid fa-paperclip" style="color: #ffd166;"></i>
                                <strong>Current File:</strong> {{ $freeTool->file_name ?? basename($freeTool->file_path) }}
                                @if ($freeTool->file_size_human)
                                    <span style="color: var(--text-muted);">({{ $freeTool->file_size_human }})</span>
                                @endif
                            </div>
                            <a href="{{ route('free-tool.download', $freeTool->id) }}" class="cyber-btn cyber-btn-secondary cyber-btn-xs">
                                <i class="fa-solid fa-download"></i> DOWNLOAD
                            </a>
                        </div>
                    @endif

                    <input 
                        type="file" 
                        id="file" 
                        name="file" 
                        class="cyber-input" 
                        accept=".txt,.zip,.rar,.tar.gz,.gz"
                        style="padding: 10px;"
                    >
                    <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); margin-top: 6px;">
                        Allowed formats: <code>.txt</code>, <code>.zip</code>, <code>.rar</code>, <code>.tar.gz</code> (Max 50 MB).
                    </div>
                </div>

                <div class="cyber-form-group" style="margin-top: 16px;">
                    <label class="cyber-label" for="description">
                        <i class="fa-solid fa-align-left"></i> DESCRIPTION
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="cyber-input" 
                        rows="5" 
                        placeholder="Describe what this free tool does and how users can use it..."
                    >{{ old('description', $freeTool->description) }}</textarea>
                </div>
            </div>

            <div>
                <div class="cyber-form-group">
                    <label class="cyber-label" for="type">
                        <i class="fa-solid fa-layer-group"></i> TOOL TYPE *
                    </label>
                    <select id="type" name="type" class="cyber-input" required onchange="toggleFreeToolType(this.value)">
                        <option value="url" {{ $selectedType === 'url' ? 'selected' : '' }}>URL (External Link)</option>
                        <option value="file" {{ $selectedType === 'file' ? 'selected' : '' }}>FILE (Upload .txt, .zip, .rar, .tar.gz)</option>
                    </select>
                </div>

                <div class="cyber-form-group" style="margin-top: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #ffffff; font-family: var(--font-mono); font-size: 0.86rem;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $freeTool->is_active) ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: var(--red-primary);">
                        <span>ACTIVE (Visible in Telegram Bot)</span>
                    </label>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 16px;">
            <a href="{{ route('free-tool.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-md">
                CANCEL
            </a>
            <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-md">
                <i class="fa-solid fa-check"></i> UPDATE FREE TOOL
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const hasExistingFile = {{ !empty($freeTool->file_path) ? 'true' : 'false' }};

    function toggleFreeToolType(type) {
        const urlGroup = document.getElementById('urlInputGroup');
        const fileGroup = document.getElementById('fileInputGroup');
        const urlInput = document.getElementById('url');
        const fileInput = document.getElementById('file');

        if (type === 'file') {
            if (urlGroup) urlGroup.style.display = 'none';
            if (fileGroup) fileGroup.style.display = '';
            if (urlInput) urlInput.removeAttribute('required');
            if (fileInput) {
                if (!hasExistingFile) {
                    fileInput.setAttribute('required', 'required');
                } else {
                    fileInput.removeAttribute('required');
                }
            }
        } else {
            if (urlGroup) urlGroup.style.display = '';
            if (fileGroup) fileGroup.style.display = 'none';
            if (urlInput) urlInput.setAttribute('required', 'required');
            if (fileInput) fileInput.removeAttribute('required');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('type');
        if (typeSelect) {
            toggleFreeToolType(typeSelect.value);
        }
    });
</script>
@endpush
