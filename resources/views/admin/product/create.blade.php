@extends('layouts.dashboard')

@section('title', 'Add New Product - Admin')
@section('page-title', 'ADD PRODUCT')

@section('content')
<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);">
        <a href="{{ route('admin.dashboard') }}" style="color: var(--text-secondary); text-decoration: none;">Admin Hub</a>
        <span>/</span>
        <a href="{{ route('product.index') }}" style="color: var(--text-secondary); text-decoration: none;">Products</a>
        <span>/</span>
        <span style="color: #ffffff;">Create</span>
    </div>

    <a href="{{ route('product.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
        <i class="fa-solid fa-arrow-left"></i> BACK TO CATALOG
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

<form action="{{ route('product.store') }}" method="POST">
    @csrf

    <div class="cyber-panel">
        <div class="cyber-panel-header">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-box-archive"></i>
                NEW PRODUCT SPECIFICATIONS
            </div>
            <span class="badge-status b-ok">SPECIFICATION DRAFT</span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;" class="split-grid">
            <div>
                <div class="cyber-form-group">
                    <label class="cyber-label" for="name">
                        <i class="fa-solid fa-tag"></i> PRODUCT NAME *
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="cyber-input" 
                        placeholder="e.g. X-Sentinel Threat Bot" 
                        value="{{ old('name') }}" 
                        required
                    >
                </div>

                <div class="cyber-form-group" style="margin-top: 16px;">
                    <label class="cyber-label" for="price">
                        <i class="fa-solid fa-money-bill"></i> PRICE (IDR) *
                    </label>
                    <input 
                        type="number" 
                        id="price" 
                        name="price" 
                        class="cyber-input" 
                        placeholder="e.g. 175000" 
                        value="{{ old('price') }}" 
                        min="0" 
                        step="1000" 
                        required
                    >
                </div>
            </div>

            <div>
                <div class="cyber-form-group">
                    <label class="cyber-label" for="descriptionEditor">
                        <i class="fa-solid fa-align-left"></i> DESCRIPTION (RICH EDITOR)
                    </label>
                    <textarea id="description" name="description" style="display: none;">{{ old('description') }}</textarea>
                    <div id="descriptionEditor" class="cyber-quill-editor" style="min-height: 140px;">{!! old('description') !!}</div>
                </div>

                <div class="cyber-form-group" style="margin-top: 14px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #ffffff; font-family: var(--font-mono); font-size: 0.86rem;">
                        <input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: var(--red-primary);">
                        <span>ACTIVE (Visible in Store for users to buy)</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Dynamic Release Versions Box with Storage Scanner --}}
    <div class="cyber-panel" style="margin-top: 20px;">
        <div class="cyber-panel-header">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-code-branch"></i>
                RELEASES & DOWNLOADABLE PACKAGES
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span class="badge-status b-ok" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-hard-drive"></i> {{ count($storageFiles) }} STORAGE FILES DETECTED
                </span>
                <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-sm" onclick="addReleaseRow()">
                    <i class="fa-solid fa-plus"></i> ADD RELEASE VERSION
                </button>
            </div>
        </div>

        {{-- Storage scanner banner info --}}
        <div style="font-family: var(--font-mono); font-size: 0.76rem; color: #a3e635; background: rgba(0, 255, 102, 0.05); border: 1px solid rgba(0, 255, 102, 0.2); border-radius: var(--radius-sm); padding: 10px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <div>
                <i class="fa-solid fa-folder-tree" style="margin-right: 6px;"></i>
                Scanned <strong>storage/app/private/</strong>: 
                @if (count($storageFiles) > 0)
                    <strong>{{ count($storageFiles) }} file(s) ready</strong> for release assignment.
                @else
                    <span style="color: #ffd166;">No files currently in <code>storage/app/private/</code>. You can enter file name manually or upload to vault.</span>
                @endif
            </div>
            <span style="color: var(--text-muted); font-size: 0.7rem;">Select from dropdown or type custom filename</span>
        </div>

        <div id="releasesContainer" style="display: flex; flex-direction: column; gap: 16px;">
            {{-- Initial Release Row 0 --}}
            <div class="release-row-card" style="background: rgba(14, 11, 16, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-sm); padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 8px;">
                    <span style="font-family: var(--font-mono); font-size: 0.78rem; font-weight: 700; color: var(--red-primary);">
                        <i class="fa-solid fa-file-zipper"></i> RELEASE PACKAGE #1
                    </span>
                    <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-sm" style="padding: 2px 8px; color: var(--text-muted);" onclick="removeReleaseRow(this)">
                        <i class="fa-solid fa-xmark"></i> Remove
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 12px;" class="release-fields-grid">
                    <div>
                        <label class="cyber-label" style="font-size: 0.7rem;">FILE NAME *</label>
                        {{-- Select dropdown from storage/app/private/ --}}
                        <select class="cyber-input file-scan-select" onchange="onFileSelectPicker(this)" style="font-family: var(--font-mono); font-size: 0.76rem; margin-bottom: 6px;">
                            <option value="">-- Select from storage/app/private/ --</option>
                            @foreach ($storageFiles as $sf)
                                <option value="{{ $sf['filename'] }}" data-md5="{{ $sf['md5'] }}">
                                    {{ $sf['filename'] }} ({{ $sf['size_human'] }})
                                </option>
                            @endforeach
                        </select>
                        <input type="text" name="releases[0][file]" class="cyber-input release-file-input" placeholder="e.g. package-v1.0.0.zip" value="package-v1.0.0.zip" required>
                    </div>
                    <div>
                        <label class="cyber-label" style="font-size: 0.7rem;">VERSION TAG *</label>
                        <input type="text" name="releases[0][version]" class="cyber-input" placeholder="e.g. 1.0.0" value="1.0.0" required>
                    </div>
                    <div>
                        <label class="cyber-label" style="font-size: 0.7rem;">MD5 CHECKSUM</label>
                        <input type="text" name="releases[0][md5sum]" class="cyber-input release-md5-input" placeholder="e.g. a9f1b2c3d4e5f60718293a4b5c6d7e8f" value="{{ md5(uniqid()) }}">
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <label class="cyber-label" style="font-size: 0.7rem;">CHANGELOG / RELEASE NOTES</label>
                    <input type="text" name="releases[0][changelog]" class="cyber-input" placeholder="e.g. Initial stable release with core modules." value="Initial release package.">
                </div>
            </div>
        </div>
    </div>

    {{-- Form Action Buttons --}}
    <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
        <a href="{{ route('product.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-md">
            CANCEL
        </a>
        <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-md">
            <i class="fa-solid fa-check"></i> SAVE & CREATE PRODUCT
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const availableStorageFiles = @json($storageFiles);
    let releaseIndex = 1;

    // Quill Rich Editor Setup
    let descriptionQuill;
    if (document.getElementById('descriptionEditor') && typeof Quill !== 'undefined') {
        descriptionQuill = new Quill('#descriptionEditor', {
            theme: 'snow',
            placeholder: 'Detailed overview of capabilities, architecture, and tool specifications...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });

        descriptionQuill.on('text-change', function () {
            const descInput = document.getElementById('description');
            if (descInput) {
                descInput.value = descriptionQuill.root.innerHTML === '<p><br></p>' ? '' : descriptionQuill.root.innerHTML;
            }
        });
    }

    const productForm = document.querySelector('form[action="{{ route('product.store') }}"]');
    if (productForm) {
        productForm.addEventListener('submit', function () {
            if (descriptionQuill) {
                const descInput = document.getElementById('description');
                if (descInput) {
                    descInput.value = descriptionQuill.root.innerHTML === '<p><br></p>' ? '' : descriptionQuill.root.innerHTML;
                }
            }
        });
    }

    function onFileSelectPicker(selectEl) {
        const card = selectEl.closest('.release-row-card');
        if (!card) return;
        const fileInput = card.querySelector('.release-file-input');
        const md5Input = card.querySelector('.release-md5-input');

        const selectedOption = selectEl.options[selectEl.selectedIndex];
        if (selectEl.value && fileInput) {
            fileInput.value = selectEl.value;
            const md5Val = selectedOption.getAttribute('data-md5');
            if (md5Val && md5Input) {
                md5Input.value = md5Val;
            }
        }
    }

    function addReleaseRow() {
        const container = document.getElementById('releasesContainer');
        const count = container.querySelectorAll('.release-row-card').length + 1;
        const randomMd5 = Array.from(crypto.getRandomValues(new Uint8Array(16)))
            .map(b => b.toString(16).padStart(2, '0')).join('');

        let optionsHtml = '<option value="">-- Select from storage/app/private/ --</option>';
        availableStorageFiles.forEach(sf => {
            optionsHtml += `<option value="${sf.filename}" data-md5="${sf.md5}">${sf.filename} (${sf.size_human})</option>`;
        });

        const rowHtml = `
            <div class="release-row-card" style="background: rgba(14, 11, 16, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-sm); padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 8px;">
                    <span style="font-family: var(--font-mono); font-size: 0.78rem; font-weight: 700; color: var(--red-primary);">
                        <i class="fa-solid fa-file-zipper"></i> RELEASE PACKAGE #${count}
                    </span>
                    <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-sm" style="padding: 2px 8px; color: var(--text-muted);" onclick="removeReleaseRow(this)">
                        <i class="fa-solid fa-xmark"></i> Remove
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 12px;" class="release-fields-grid">
                    <div>
                        <label class="cyber-label" style="font-size: 0.7rem;">FILE NAME *</label>
                        <select class="cyber-input file-scan-select" onchange="onFileSelectPicker(this)" style="font-family: var(--font-mono); font-size: 0.76rem; margin-bottom: 6px;">
                            ${optionsHtml}
                        </select>
                        <input type="text" name="releases[${releaseIndex}][file]" class="cyber-input release-file-input" placeholder="e.g. package-v${releaseIndex}.0.0.zip" value="package-v${releaseIndex}.0.0.zip" required>
                    </div>
                    <div>
                        <label class="cyber-label" style="font-size: 0.7rem;">VERSION TAG *</label>
                        <input type="text" name="releases[${releaseIndex}][version]" class="cyber-input" placeholder="e.g. ${releaseIndex}.0.0" value="${releaseIndex}.0.0" required>
                    </div>
                    <div>
                        <label class="cyber-label" style="font-size: 0.7rem;">MD5 CHECKSUM</label>
                        <input type="text" name="releases[${releaseIndex}][md5sum]" class="cyber-input release-md5-input" placeholder="e.g. a9f1b2c3d4e5f60718293a4b5c6d7e8f" value="${randomMd5}">
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <label class="cyber-label" style="font-size: 0.7rem;">CHANGELOG / RELEASE NOTES</label>
                    <input type="text" name="releases[${releaseIndex}][changelog]" class="cyber-input" placeholder="e.g. Added enhancements." value="Release version updates and patches.">
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', rowHtml);
        releaseIndex++;
    }

    function removeReleaseRow(btn) {
        const container = document.getElementById('releasesContainer');
        const rows = container.querySelectorAll('.release-row-card');
        if (rows.length <= 1) {
            alert('At least one release package is required.');
            return;
        }
        btn.closest('.release-row-card').remove();
    }
</script>
@endpush
