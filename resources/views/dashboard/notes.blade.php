@extends('layouts.dashboard')

@section('title', 'xNotes & News')
@section('page-title', 'xNOTES')

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
            <span class="prompt-dir">notes</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">fetch --news --all</span>
        </div>
        <span class="terminal-badge">ARTICLES: {{ $postCounts['all'] ?? 0 }} POSTS</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">xNOTES & NEWS</h1>
            <p class="welcome-subtext">
                Explore official announcements, changelogs, tutorials, and product updates.
            </p>
        </div>

        {{-- Filter & Search Bar --}}
        <div class="filter-controls">
            <form action="{{ route('dashboard.notes') }}" method="GET" style="display: flex; gap: 10px; width: 100%; max-width: 480px;">
                @if ($selectedCategory && $selectedCategory !== 'all')
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                @endif
                <div class="search-input-wrapper" style="flex: 1;">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input 
                        type="text" 
                        name="q" 
                        class="tool-filter-input" 
                        placeholder="Search notes (title, content)..."
                        value="{{ $search ?? '' }}"
                        autocomplete="off"
                    >
                    @if ($search)
                        <a href="{{ route('dashboard.notes', array_filter(['category' => $selectedCategory])) }}" class="clear-search-btn" title="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>
                <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-sm" style="padding: 0 16px;">
                    <i class="fa-solid fa-magnifying-glass"></i> SEARCH
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Category Filter Tabs --}}
<div class="notes-category-bar">
    <a 
        href="{{ route('dashboard.notes', array_filter(['q' => $search])) }}" 
        class="notes-cat-tab {{ empty($selectedCategory) || $selectedCategory === 'all' ? 'active' : '' }}"
    >
        <i class="fa-solid fa-border-all"></i>
        <span>All</span>
        <span class="cat-count">{{ $postCounts['all'] ?? 0 }}</span>
    </a>
    <a 
        href="{{ route('dashboard.notes', array_filter(['category' => 'announcement', 'q' => $search])) }}" 
        class="notes-cat-tab {{ ($selectedCategory ?? '') === 'announcement' ? 'active' : '' }}"
    >
        <i class="fa-solid fa-bullhorn" style="color: var(--red-primary);"></i>
        <span>Announcements</span>
        <span class="cat-count">{{ $postCounts['announcement'] ?? 0 }}</span>
    </a>
    <a 
        href="{{ route('dashboard.notes', array_filter(['category' => 'news', 'q' => $search])) }}" 
        class="notes-cat-tab {{ ($selectedCategory ?? '') === 'news' ? 'active' : '' }}"
    >
        <i class="fa-solid fa-newspaper" style="color: #48cae4;"></i>
        <span>News</span>
        <span class="cat-count">{{ $postCounts['news'] ?? 0 }}</span>
    </a>
    <a 
        href="{{ route('dashboard.notes', array_filter(['category' => 'changelog', 'q' => $search])) }}" 
        class="notes-cat-tab {{ ($selectedCategory ?? '') === 'changelog' ? 'active' : '' }}"
    >
        <i class="fa-solid fa-code-branch" style="color: var(--status-online);"></i>
        <span>Changelogs</span>
        <span class="cat-count">{{ $postCounts['changelog'] ?? 0 }}</span>
    </a>
    <a 
        href="{{ route('dashboard.notes', array_filter(['category' => 'tutorial', 'q' => $search])) }}" 
        class="notes-cat-tab {{ ($selectedCategory ?? '') === 'tutorial' ? 'active' : '' }}"
    >
        <i class="fa-solid fa-graduation-cap" style="color: #ffd166;"></i>
        <span>Tutorials</span>
        <span class="cat-count">{{ $postCounts['tutorial'] ?? 0 }}</span>
    </a>
</div>

{{-- Section Heading --}}
<div class="section-title-bar" style="margin-top: 20px;">
    <div class="title-with-icon">
        <i class="fa-solid fa-note-sticky section-icon"></i>
        <h2 class="section-heading">LATEST NOTES & ARTICLES</h2>
    </div>
    <div class="section-divider-line"></div>
    <span class="section-meta-tag">[ {{ $posts->total() }} POSTS FOUND ]</span>
</div>

@if ($posts->isEmpty())
    {{-- Empty State --}}
    <div class="cyber-panel" style="text-align: center; padding: 48px 24px;">
        <i class="fa-solid fa-newspaper" style="font-size: 3.5rem; color: var(--red-primary); margin-bottom: 16px; opacity: 0.7;"></i>
        <h3 style="font-family: var(--font-mono); font-size: 1.2rem; color: #ffffff; margin-bottom: 8px;">NO NOTES FOUND</h3>
        <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 24px; font-size: 0.86rem; line-height: 1.6;">
            There are no published news or articles matching your filter criteria right now.
        </p>
        <a href="{{ route('dashboard.notes') }}" class="cyber-btn cyber-btn-secondary cyber-btn-md">
            <i class="fa-solid fa-arrow-rotate-left"></i> VIEW ALL NOTES
        </a>
    </div>
@else
    {{-- Notes Grid Matrix --}}
    <div class="cyber-cards-grid" id="notesGrid">
        @foreach ($posts as $post)
            @php
                $catColor = match($post->category) {
                    'announcement' => 'var(--red-primary)',
                    'news' => '#48cae4',
                    'changelog' => 'var(--status-online)',
                    'tutorial' => '#ffd166',
                    'promotion' => '#f72585',
                    default => '#a0a0a0'
                };
                $catBg = match($post->category) {
                    'announcement' => 'rgba(255, 23, 68, 0.12)',
                    'news' => 'rgba(72, 202, 228, 0.12)',
                    'changelog' => 'rgba(0, 255, 102, 0.12)',
                    'tutorial' => 'rgba(255, 209, 102, 0.12)',
                    'promotion' => 'rgba(247, 37, 133, 0.12)',
                    default => 'rgba(255, 255, 255, 0.08)'
                };
            @endphp
            <div class="cyber-card-item note-card-item">
                <div class="card-glow-layer"></div>

                {{-- Card Top Ribbon --}}
                <div class="cyber-card-topbar">
                    <span 
                        class="cyber-node-badge" 
                        style="background: {{ $catBg }}; border-color: {{ $catColor }}; color: {{ $catColor }};"
                    >
                        <i class="fa-solid fa-tag"></i> {{ strtoupper($post->category) }}
                    </span>
                    <span class="cyber-ver-badge">
                        <i class="fa-solid fa-calendar-days"></i> {{ $post->created_at ? $post->created_at->format('M d, Y') : 'Recent' }}
                    </span>
                </div>

                {{-- Card Main Content --}}
                <div class="cyber-card-main">
                    {{-- Hero Title --}}
                    <div class="cyber-hero-row" style="margin-bottom: 10px;">
                        <div class="cyber-avatar-box" style="background: {{ $catBg }}; border-color: {{ $catColor }}; color: {{ $catColor }}; width: 44px; height: 44px; font-size: 1.3rem;">
                            @if ($post->category === 'announcement')
                                <i class="fa-solid fa-bullhorn"></i>
                            @elseif ($post->category === 'news')
                                <i class="fa-solid fa-newspaper"></i>
                            @elseif ($post->category === 'changelog')
                                <i class="fa-solid fa-code-branch"></i>
                            @elseif ($post->category === 'tutorial')
                                <i class="fa-solid fa-graduation-cap"></i>
                            @else
                                <i class="fa-solid fa-note-sticky"></i>
                            @endif
                        </div>
                        <div class="cyber-title-group" style="min-width: 0;">
                            <h3 class="cyber-card-heading" style="font-size: 1.05rem; line-height: 1.3;">
                                <a href="{{ route('dashboard.notes.detail', $post->slug) }}" style="color: #ffffff; text-decoration: none;">
                                    {{ $post->title }}
                                </a>
                            </h3>
                            <div class="cyber-card-subheading">
                                <span>Published by System</span>
                            </div>
                        </div>
                    </div>

                    {{-- Excerpt Body --}}
                    <p class="cyber-card-text" style="font-size: 0.83rem; line-height: 1.5; color: #b8b8b8; margin-bottom: 14px;">
                        {{ \Illuminate\Support\Str::limit(strip_tags($post->content), 140) }}
                    </p>
                </div>

                {{-- Card Action Footer --}}
                <div class="cyber-card-action-bar">
                    <a 
                        href="{{ route('dashboard.notes.detail', $post->slug) }}" 
                        class="cyber-btn cyber-btn-primary cyber-btn-block"
                        style="justify-content: center;"
                    >
                        <span>READ FULL ARTICLE</span>
                        <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination Controls --}}
    <div style="margin-top: 30px; display: flex; justify-content: center;">
        {{ $posts->links() }}
    </div>
@endif
@endsection
