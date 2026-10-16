@extends('layouts.dashboard')

@section('title', 'Free Tools - Admin')
@section('page-title', 'FREE TOOLS MANAGEMENT')

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
            <span class="prompt-dir">free-tools</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">list --tools</span>
        </div>
        <span class="terminal-badge">TOTAL TOOLS: {{ $stats['total'] }} (FILE: {{ $stats['file_count'] }} / URL: {{ $stats['url_count'] }})</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">FREE TOOLS REPOSITORY</h1>
            <p class="welcome-subtext">
                Manage free utilities, downloadable archives (.txt, .zip, .rar, .tar.gz), and external tool URLs accessible via Telegram Bot.
            </p>
        </div>

        {{-- Filter & Action Controls --}}
        <div class="filter-controls">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input 
                    type="text" 
                    id="freeToolClientSearch" 
                    class="tool-filter-input" 
                    placeholder="Search free tools by name, type, URL or filename..."
                    autocomplete="off"
                >
                <button type="button" id="clearFreeToolSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <a href="{{ route('free-tool.create') }}" class="cyber-btn cyber-btn-primary">
                <i class="fa-solid fa-plus"></i>
                <span>ADD FREE TOOL</span>
            </a>
        </div>
    </div>
</div>

{{-- Free Tools Records Table Panel --}}
<div class="cyber-panel" style="margin-top: 20px;">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            FREE TOOLS MATRIX
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge-status b-ok">
                <span class="status-dot online"></span>
                <span id="freeToolCountBadge">{{ $freeTools->total() }} TOOLS FOUND</span>
            </span>
            <a href="{{ route('free-tool.create') }}" class="cyber-btn cyber-btn-primary cyber-btn-sm">
                <i class="fa-solid fa-plus"></i> NEW FREE TOOL
            </a>
        </div>
    </div>

    @if ($freeTools->isEmpty())
        <div class="empty-state" style="padding: 48px 20px;">
            <i class="fa-solid fa-screwdriver-wrench empty-icon"></i>
            <div class="empty-title">NO FREE TOOLS AVAILABLE</div>
            <p class="empty-desc">Create your first free tool (file archive or URL link) for Telegram Bot users.</p>
            <a href="{{ route('free-tool.create') }}" class="cyber-btn cyber-btn-primary cyber-btn-md" style="margin-top: 10px;">
                <i class="fa-solid fa-plus"></i> ADD FIRST FREE TOOL
            </a>
        </div>
    @else
        <div class="cyber-table-container">
            <table class="cyber-data-table" id="freeToolsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>TOOL NAME</th>
                        <th>TYPE</th>
                        <th>TARGET (FILE / URL)</th>
                        <th>STATUS</th>
                        <th>CREATED DATE</th>
                        <th style="text-align: right;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($freeTools as $index => $tool)
                        <tr class="free-tool-row-item"
                            data-name="{{ strtolower($tool->name) }}"
                            data-type="{{ strtolower($tool->type) }}"
                            data-target="{{ strtolower($tool->isUrl() ? ($tool->url ?? '') : ($tool->file_name ?? '')) }}">
                            <td style="color: var(--text-muted); font-weight: 700;">
                                {{ sprintf('%02d', $index + 1) }}
                            </td>
                            <td>
                                <div class="domain-name-cell">
                                    <i class="fa-solid {{ $tool->isFile() ? 'fa-file-zipper' : 'fa-link' }}" style="color: {{ $tool->isFile() ? '#ffd166' : '#48cae4' }}; font-size: 0.9rem;"></i>
                                    <span style="font-weight: 700; color: #ffffff;">{{ $tool->name }}</span>
                                </div>
                                @if ($tool->description)
                                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px; max-width: 340px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ strip_tags($tool->description) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($tool->isFile())
                                    <span class="badge-status" style="background: rgba(255, 209, 102, 0.12); border: 1px solid #ffd166; color: #ffd166; font-size: 0.72rem; text-transform: uppercase;">
                                        <i class="fa-solid fa-file-arrow-down"></i> FILE
                                    </span>
                                @else
                                    <span class="badge-status" style="background: rgba(72, 202, 228, 0.12); border: 1px solid #48cae4; color: #48cae4; font-size: 0.72rem; text-transform: uppercase;">
                                        <i class="fa-solid fa-globe"></i> URL
                                    </span>
                                @endif
                            </td>
                            <td style="font-family: var(--font-mono); font-size: 0.76rem;">
                                @if ($tool->isUrl())
                                    <a href="{{ $tool->url }}" target="_blank" rel="noopener noreferrer" style="color: #48cae4; text-decoration: none;">
                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.68rem;"></i>
                                        {{ Str::limit($tool->url, 45) }}
                                    </a>
                                @else
                                    <div style="color: #e5e5e5;">
                                        <i class="fa-solid fa-paperclip" style="color: #ffd166;"></i>
                                        {{ $tool->file_name ?? basename($tool->file_path ?? 'N/A') }}
                                        @if ($tool->file_size_human)
                                            <span style="color: var(--text-muted);">({{ $tool->file_size_human }})</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($tool->is_active)
                                    <span class="badge-status b-ok">
                                        <span class="status-dot online"></span>
                                        ACTIVE
                                    </span>
                                @else
                                    <span class="badge-status b-err">
                                        <span class="status-dot offline"></span>
                                        INACTIVE
                                    </span>
                                @endif
                            </td>
                            <td style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.76rem;">
                                {{ $tool->created_at ? $tool->created_at->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                    @if ($tool->isUrl() && $tool->url)
                                        <a 
                                            href="{{ $tool->url }}" 
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="cyber-btn cyber-btn-secondary cyber-btn-xs" 
                                            title="Open URL"
                                        >
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @elseif ($tool->isFile() && $tool->file_path)
                                        <a 
                                            href="{{ route('free-tool.download', $tool->id) }}" 
                                            class="cyber-btn cyber-btn-secondary cyber-btn-xs" 
                                            title="Download File"
                                        >
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                    @endif

                                    <a 
                                        href="{{ route('free-tool.edit', $tool->id) }}" 
                                        class="cyber-btn cyber-btn-primary cyber-btn-xs" 
                                        title="Edit Free Tool"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i> EDIT
                                    </a>

                                    <form action="{{ route('free-tool.destroy', $tool->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete free tool: {{ $tool->name }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cyber-btn cyber-btn-xs" style="background: rgba(255, 23, 68, 0.15); border: 1px solid var(--red-primary); color: var(--red-primary);" title="Delete Free Tool">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $freeTools->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    const freeToolSearchInput = document.getElementById('freeToolClientSearch');
    const clearFreeToolSearchBtn = document.getElementById('clearFreeToolSearchBtn');
    const freeToolRows = document.querySelectorAll('.free-tool-row-item');
    const freeToolCountBadge = document.getElementById('freeToolCountBadge');

    if (freeToolSearchInput) {
        freeToolSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let matches = 0;

            if (clearFreeToolSearchBtn) {
                clearFreeToolSearchBtn.style.display = query.length > 0 ? 'flex' : 'none';
            }

            freeToolRows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const type = row.getAttribute('data-type') || '';
                const target = row.getAttribute('data-target') || '';

                if (name.includes(query) || type.includes(query) || target.includes(query)) {
                    row.style.display = '';
                    matches++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (freeToolCountBadge) {
                freeToolCountBadge.textContent = matches + ' TOOLS FOUND';
            }
        });
    }

    if (clearFreeToolSearchBtn) {
        clearFreeToolSearchBtn.addEventListener('click', function () {
            freeToolSearchInput.value = '';
            freeToolSearchInput.dispatchEvent(new Event('input'));
            freeToolSearchInput.focus();
        });
    }
</script>
@endpush
