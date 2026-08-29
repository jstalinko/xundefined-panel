@extends('layouts.dashboard')

@section('title', 'Manage Posts - Admin')
@section('page-title', 'POSTS MANAGEMENT')

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
            <span class="prompt-dir">posts</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">list --articles</span>
        </div>
        <span class="terminal-badge">TOTAL POSTS: {{ $stats['total'] }}</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">NEWS & ANNOUNCEMENTS MANAGEMENT</h1>
            <p class="welcome-subtext">
                Publish platform updates, security changelogs, tutorials, and advisories to the xNotes feed.
            </p>
        </div>

        {{-- Filter & Action Controls --}}
        <div class="filter-controls">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input 
                    type="text" 
                    id="postClientSearch" 
                    class="tool-filter-input" 
                    placeholder="Search posts by title or category..."
                    autocomplete="off"
                >
                <button type="button" id="clearPostSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <a href="{{ route('post.create') }}" class="cyber-btn cyber-btn-primary">
                <i class="fa-solid fa-plus"></i>
                <span>WRITE ARTICLE</span>
            </a>
        </div>
    </div>
</div>

{{-- Posts Records Table Panel --}}
<div class="cyber-panel" style="margin-top: 20px;">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-newspaper"></i>
            PUBLISHED ARTICLES MATRIX
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge-status b-ok">
                <span class="status-dot online"></span>
                <span id="postCountBadge">{{ $posts->total() }} POSTS FOUND</span>
            </span>
            <a href="{{ route('post.create') }}" class="cyber-btn cyber-btn-primary cyber-btn-sm">
                <i class="fa-solid fa-plus"></i> NEW ARTICLE
            </a>
        </div>
    </div>

    @if ($posts->isEmpty())
        <div class="empty-state" style="padding: 48px 20px;">
            <i class="fa-solid fa-newspaper empty-icon"></i>
            <div class="empty-title">NO POSTS PUBLISHED</div>
            <p class="empty-desc">Create your first news or changelog article for users.</p>
            <a href="{{ route('post.create') }}" class="cyber-btn cyber-btn-primary cyber-btn-md" style="margin-top: 10px;">
                <i class="fa-solid fa-plus"></i> CREATE FIRST POST
            </a>
        </div>
    @else
        <div class="cyber-table-container">
            <table class="cyber-data-table" id="postsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>ARTICLE TITLE</th>
                        <th>CATEGORY</th>
                        <th>STATUS</th>
                        <th>PUBLISHED DATE</th>
                        <th style="text-align: right;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($posts as $index => $p)
                        @php
                            $catColor = match($p->category) {
                                'announcement' => 'var(--red-primary)',
                                'news' => '#48cae4',
                                'changelog' => 'var(--status-online)',
                                'tutorial' => '#ffd166',
                                'promotion' => '#f72585',
                                default => '#a0a0a0'
                            };
                            $catBg = match($p->category) {
                                'announcement' => 'rgba(255, 23, 68, 0.12)',
                                'news' => 'rgba(72, 202, 228, 0.12)',
                                'changelog' => 'rgba(0, 255, 102, 0.12)',
                                'tutorial' => 'rgba(255, 209, 102, 0.12)',
                                'promotion' => 'rgba(247, 37, 133, 0.12)',
                                default => 'rgba(255, 255, 255, 0.08)'
                            };
                        @endphp
                        <tr class="post-row-item" data-title="{{ strtolower($p->title) }}" data-category="{{ strtolower($p->category) }}">
                            <td style="color: var(--text-muted); font-weight: 700;">
                                {{ sprintf('%02d', $index + 1) }}
                            </td>
                            <td>
                                <div class="domain-name-cell">
                                    <i class="fa-solid fa-note-sticky" style="color: {{ $catColor }}; font-size: 0.9rem;"></i>
                                    <a href="{{ route('dashboard.notes.detail', $p->slug) }}" target="_blank" style="font-weight: 700; color: #ffffff; text-decoration: none;">
                                        {{ $p->title }}
                                    </a>
                                    <button 
                                        type="button" 
                                        class="cyber-copy-btn" 
                                        style="padding: 2px 6px; font-size: 0.65rem;" 
                                        onclick="copyText('{{ $p->title }}', this)" 
                                        title="Copy title"
                                    >
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                                @if ($p->content)
                                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px; max-width: 380px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ strip_tags($p->content) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge-status" style="background: {{ $catBg }}; border: 1px solid {{ $catColor }}; color: {{ $catColor }}; font-size: 0.72rem; text-transform: uppercase;">
                                    <i class="fa-solid fa-tag"></i> {{ $p->category }}
                                </span>
                            </td>
                            <td>
                                @if ($p->is_published)
                                    <span class="badge-status b-ok">
                                        <span class="status-dot online"></span>
                                        PUBLISHED
                                    </span>
                                @else
                                    <span class="badge-status b-err">
                                        <span class="status-dot offline"></span>
                                        DRAFT
                                    </span>
                                @endif
                            </td>
                            <td style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.76rem;">
                                {{ $p->created_at ? $p->created_at->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                    <a 
                                        href="{{ route('dashboard.notes.detail', $p->slug) }}" 
                                        target="_blank"
                                        class="cyber-btn cyber-btn-secondary cyber-btn-xs" 
                                        title="View Public Article"
                                    >
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                    
                                    <a 
                                        href="{{ route('post.edit', $p->id) }}" 
                                        class="cyber-btn cyber-btn-primary cyber-btn-xs" 
                                        title="Edit Article"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i> EDIT
                                    </a>
                                    
                                    <form action="{{ route('post.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete post: {{ $p->title }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cyber-btn cyber-btn-xs" style="background: rgba(255, 23, 68, 0.15); border: 1px solid var(--red-primary); color: var(--red-primary);" title="Delete article">
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
            {{ $posts->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Live Search Filter for Posts Table
    const postSearchInput = document.getElementById('postClientSearch');
    const clearPostSearchBtn = document.getElementById('clearPostSearchBtn');
    const postRows = document.querySelectorAll('.post-row-item');
    const postCountBadge = document.getElementById('postCountBadge');

    if (postSearchInput) {
        postSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let matches = 0;

            if (clearPostSearchBtn) {
                clearPostSearchBtn.style.display = query.length > 0 ? 'flex' : 'none';
            }

            postRows.forEach(row => {
                const title = row.getAttribute('data-title') || '';
                const cat = row.getAttribute('data-category') || '';

                if (title.includes(query) || cat.includes(query)) {
                    row.style.display = '';
                    matches++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (postCountBadge) {
                postCountBadge.textContent = matches + ' POSTS FOUND';
            }
        });
    }

    if (clearPostSearchBtn) {
        clearPostSearchBtn.addEventListener('click', function () {
            postSearchInput.value = '';
            postSearchInput.dispatchEvent(new Event('input'));
            postSearchInput.focus();
        });
    }
</script>
@endpush
