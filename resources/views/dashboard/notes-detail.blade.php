@extends('layouts.dashboard')

@section('title', $post->title . ' - xNotes')
@section('page-title', 'xNOTES')

@section('content')
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

{{-- Navigation Breadcrumb --}}
<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);">
        <a href="{{ route('dashboard') }}" style="color: var(--text-secondary); text-decoration: none;">Dashboard</a>
        <span>/</span>
        <a href="{{ route('dashboard.notes') }}" style="color: var(--text-secondary); text-decoration: none;">xNotes</a>
        <span>/</span>
        <span style="color: #ffffff;">{{ \Illuminate\Support\Str::limit($post->title, 30) }}</span>
    </div>

    <a href="{{ route('dashboard.notes') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
        <i class="fa-solid fa-arrow-left"></i> BACK TO NOTES
    </a>
</div>

{{-- Main Article Card & Layout --}}
<div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start;">
    {{-- Article Body --}}
    <article class="cyber-panel" style="padding: 28px 32px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
            <span class="cyber-node-badge" style="background: {{ $catBg }}; border-color: {{ $catColor }}; color: {{ $catColor }}; font-size: 0.78rem; padding: 4px 12px;">
                <i class="fa-solid fa-tag"></i> {{ strtoupper($post->category) }}
            </span>
            <span style="font-family: var(--font-mono); font-size: 0.78rem; color: var(--text-muted);">
                <i class="fa-solid fa-clock"></i> {{ $post->created_at ? $post->created_at->format('F d, Y H:i T') : 'N/A' }}
            </span>
        </div>

        <h1 style="font-family: var(--font-mono); font-size: 1.6rem; font-weight: 700; color: #ffffff; line-height: 1.35; margin-bottom: 16px;">
            {{ $post->title }}
        </h1>

        <div style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
            <div class="profile-avatar-box" style="width: 32px; height: 32px; font-size: 0.85rem;">
                <i class="fa-solid fa-shield-halved" style="color: var(--red-primary);"></i>
            </div>
            <div>
                <div style="font-size: 0.84rem; font-weight: 600; color: #ffffff;">System Administration</div>
                <div style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--text-muted);">Verified Publication</div>
            </div>
        </div>

        {{-- Post Content --}}
        <div class="note-article-content" style="font-size: 0.95rem; line-height: 1.8; color: #e0e0e0;">
            {!! nl2br(e($post->content)) !!}
        </div>

        <div style="margin-top: 36px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.08); display: flex; justify-content: space-between; align-items: center;">
            <a href="{{ route('dashboard.notes') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
                <i class="fa-solid fa-arrow-left"></i> ALL NOTES
            </a>
            <button 
                type="button" 
                class="cyber-copy-btn" 
                onclick="copyText(window.location.href, this)" 
                style="padding: 6px 14px; font-size: 0.74rem;"
            >
                <i class="fa-solid fa-share-nodes"></i> SHARE LINK
            </button>
        </div>
    </article>

    {{-- Sidebar Recent Notes --}}
    <aside>
        <div class="cyber-panel">
            <div class="cyber-panel-header">
                <div class="cyber-panel-title">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    RECENT NOTES
                </div>
            </div>

            @if ($recentPosts->isEmpty())
                <p style="color: var(--text-muted); font-size: 0.8rem; padding: 10px 0;">No other recent notes found.</p>
            @else
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach ($recentPosts as $recent)
                        <a 
                            href="{{ route('dashboard.notes.detail', $recent->slug) }}" 
                            style="display: block; padding: 12px; background: rgba(15, 12, 18, 0.7); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: var(--radius-sm); text-decoration: none; transition: all var(--transition-fast);"
                            onmouseover="this.style.borderColor='rgba(255,23,68,0.4)'; this.style.transform='translateY(-2px)';"
                            onmouseout="this.style.borderColor='rgba(255,255,255,0.06)'; this.style.transform='translateY(0)';"
                        >
                            <div style="font-family: var(--font-mono); font-size: 0.68rem; color: var(--red-primary); margin-bottom: 4px; text-transform: uppercase;">
                                [ {{ $recent->category }} ]
                            </div>
                            <div style="font-size: 0.86rem; font-weight: 600; color: #ffffff; line-height: 1.35; margin-bottom: 6px;">
                                {{ $recent->title }}
                            </div>
                            <div style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--text-muted);">
                                {{ $recent->created_at ? $recent->created_at->format('M d, Y') : '' }}
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </aside>
</div>
@endsection
