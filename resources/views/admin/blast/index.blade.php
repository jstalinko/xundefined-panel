@extends('layouts.dashboard')

@section('title', 'Blast Message - Telegram Broadcast')
@section('page-title', 'BLAST MESSAGE')

@section('content')
{{-- Flash Status Notifications --}}
@if (session('status'))
    <div class="cyber-alert" role="alert" style="border-color: var(--status-online); background: rgba(0, 255, 102, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-circle-check cyber-alert-icon" style="color: var(--status-online);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--status-online);">TRANSMISSION DISPATCHED</span>
            <span class="cyber-alert-msg">{{ session('status') }}</span>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="cyber-alert" role="alert" style="border-color: var(--red-primary); background: rgba(255, 23, 68, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-triangle-exclamation cyber-alert-icon" style="color: var(--red-primary);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--red-primary);">TRANSMISSION FAILED</span>
            <span class="cyber-alert-msg">{{ session('error') }}</span>
        </div>
    </div>
@endif

@if (isset($errors) && $errors->any())
    <div class="cyber-alert" role="alert" style="border-color: var(--red-primary); background: rgba(255, 23, 68, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-circle-xmark cyber-alert-icon" style="color: var(--red-primary);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--red-primary);">VALIDATION ERROR</span>
            <ul style="margin: 4px 0 0 16px; font-family: var(--font-mono); font-size: 0.78rem; color: #ff859b;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
            <span class="prompt-dir">broadcast</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">blast --telegram-broadcast</span>
        </div>
        <span class="terminal-badge"><i class="fa-brands fa-telegram" style="color: #48cae4; margin-right: 4px;"></i> COLLECTED SUBSCRIBERS: {{ $stats['total_subscribers'] }}</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">TELEGRAM MESSAGE BROADCAST MAINFRAME</h1>
            <p class="welcome-subtext">
                Dispatch mass announcements, system news, and notifications to Telegram users using chat IDs collected directly from user accounts.
            </p>
        </div>
    </div>
</div>

{{-- Quick Telemetry Stats Cards --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 20px 0;">
    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">SUBSCRIBERS</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: #48cae4; margin-top: 4px;">{{ $stats['total_subscribers'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary);">With Telegram Chat ID</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(72, 202, 228, 0.12); border-color: rgba(72, 202, 228, 0.35); color: #48cae4;">
            <i class="fa-brands fa-telegram"></i>
        </div>
    </div>

    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">MEMBERS</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: #00ff66; margin-top: 4px;">{{ $stats['members'] }}</div>
            <div style="font-size: 0.75rem; color: var(--status-online);">Active Operatives</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(0, 255, 102, 0.12); border-color: rgba(0, 255, 102, 0.35); color: var(--status-online);">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>

    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">TOTAL BLASTS</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: var(--red-primary); margin-top: 4px;">{{ $stats['total_blasts'] }}</div>
            <div style="font-size: 0.75rem; color: var(--red-primary);">Campaigns Sent</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(255, 23, 68, 0.12); border-color: rgba(255, 23, 68, 0.35); color: var(--red-primary);">
            <i class="fa-solid fa-bullhorn"></i>
        </div>
    </div>

    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">DELIVERED</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: #ffd166; margin-top: 4px;">{{ $stats['total_delivered'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ $stats['success_rate'] }}% Delivery Rate</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(255, 209, 102, 0.12); border-color: rgba(255, 209, 102, 0.35); color: #ffd166;">
            <i class="fa-solid fa-paper-plane"></i>
        </div>
    </div>
</div>

{{-- Main Grid: Broadcast Composer & History --}}
<div style="display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 24px;">
    
    {{-- Panel: Compose Broadcast Message --}}
    <div class="cyber-panel">
        <div class="cyber-panel-header" style="justify-content: space-between;">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-tower-broadcast" style="color: var(--red-primary);"></i>
                <span>COMPOSE TELEGRAM BROADCAST</span>
            </div>
            <div style="font-family: var(--font-mono); font-size: 0.74rem; color: var(--text-muted);">
                <i class="fa-solid fa-signal" style="color: var(--status-online); margin-right: 4px;"></i> API READY
            </div>
        </div>

        <form action="{{ route('admin.blast-message.send') }}" method="POST" id="blastMessageForm" style="padding: 24px;">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 18px;">
                {{-- Campaign / Title --}}
                <div class="cyber-form-group" style="margin-bottom: 0;">
                    <label class="cyber-label" for="blastTitle">
                        <i class="fa-solid fa-tag"></i> CAMPAIGN TITLE / REFERENCE (OPTIONAL)
                    </label>
                    <input 
                        type="text" 
                        name="title" 
                        id="blastTitle" 
                        class="cyber-input" 
                        placeholder="e.g. Major Platform Update v2.5 Announcement"
                        value="{{ old('title') }}"
                    >
                </div>

                {{-- Target Audience --}}
                <div class="cyber-form-group" style="margin-bottom: 0;">
                    <label class="cyber-label" for="targetAudienceSelect">
                        <i class="fa-solid fa-crosshairs"></i> TARGET AUDIENCE *
                    </label>
                    <select name="target_audience" id="targetAudienceSelect" class="cyber-select" onchange="handleAudienceChange()">
                        <option value="all" {{ old('target_audience', 'all') === 'all' ? 'selected' : '' }}>
                            📢 All Telegram Subscribers ({{ $stats['total_subscribers'] }} users)
                        </option>
                        <option value="members" {{ old('target_audience') === 'members' ? 'selected' : '' }}>
                            👤 Active Members / Operatives ({{ $stats['members'] }} users)
                        </option>
                        <option value="admins" {{ old('target_audience') === 'admins' ? 'selected' : '' }}>
                            🛡️ System Administrators ({{ $stats['admins'] }} users)
                        </option>
                        <option value="orders" {{ old('target_audience') === 'orders' ? 'selected' : '' }}>
                            📦 Users with Completed Orders ({{ $stats['orders'] }} users)
                        </option>
                        <option value="custom" {{ old('target_audience') === 'custom' ? 'selected' : '' }}>
                            🎯 Custom Telegram Chat IDs (Manual Entry)
                        </option>
                        <option value="test" {{ old('target_audience') === 'test' ? 'selected' : '' }}>
                            🧪 Test Broadcast (Single Chat ID)
                        </option>
                    </select>
                </div>

                {{-- Parse Mode --}}
                <div class="cyber-form-group" style="margin-bottom: 0;">
                    <label class="cyber-label" for="parseModeSelect">
                        <i class="fa-solid fa-code"></i> TEXT FORMATTING MODE *
                    </label>
                    <select name="parse_mode" id="parseModeSelect" class="cyber-select">
                        <option value="Markdown" {{ old('parse_mode', 'Markdown') === 'Markdown' ? 'selected' : '' }}>Markdown (*bold*, _italic_, `code`, [link](url))</option>
                        <option value="HTML" {{ old('parse_mode') === 'HTML' ? 'selected' : '' }}>HTML (&lt;b&gt;bold&lt;/b&gt;, &lt;code&gt;code&lt;/code&gt;, &lt;a href=""&gt;link&lt;/a&gt;)</option>
                        <option value="plain" {{ old('parse_mode') === 'plain' ? 'selected' : '' }}>Plain Text (Raw message without parsing)</option>
                    </select>
                </div>
            </div>

            {{-- Custom Chat IDs Box (Hidden by default) --}}
            <div class="cyber-form-group" id="customChatIdsBox" style="display: {{ old('target_audience') === 'custom' ? 'block' : 'none' }}; margin-bottom: 18px; background: rgba(0,0,0,0.25); border: 1px dashed rgba(255, 23, 68, 0.4); padding: 14px; border-radius: 4px;">
                <label class="cyber-label" for="customChatIdsInput">
                    <i class="fa-solid fa-list-ol"></i> ENTER CUSTOM TELEGRAM CHAT IDs OR USERNAMES *
                </label>
                <textarea 
                    name="custom_chat_ids" 
                    id="customChatIdsInput" 
                    class="cyber-textarea" 
                    rows="3" 
                    placeholder="Enter chat IDs separated by commas or new lines, e.g.:&#10;123456789&#10;987654321, @username"
                >{{ old('custom_chat_ids') }}</textarea>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; font-family: var(--font-mono);">
                    Tip: Separate multiple numeric chat_ids or Telegram usernames with commas or newlines.
                </div>
            </div>

            {{-- Test Chat ID Box (Hidden by default) --}}
            <div class="cyber-form-group" id="testChatIdBox" style="display: {{ old('target_audience') === 'test' ? 'block' : 'none' }}; margin-bottom: 18px; background: rgba(0,0,0,0.25); border: 1px dashed rgba(72, 202, 228, 0.4); padding: 14px; border-radius: 4px;">
                <label class="cyber-label" for="testChatIdInput">
                    <i class="fa-solid fa-vial"></i> TEST TELEGRAM CHAT ID *
                </label>
                <input 
                    type="text" 
                    name="test_chat_id" 
                    id="testChatIdInput" 
                    class="cyber-input" 
                    placeholder="Enter your chat ID, e.g. 123456789"
                    value="{{ old('test_chat_id', $user->telegram_id ?? '') }}"
                >
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; font-family: var(--font-mono);">
                    Your current account chat ID is: <span style="color: #48cae4;">{{ $user->telegram_id ?: 'Not linked' }}</span>.
                </div>
            </div>

            {{-- Audience Live Preview Badge --}}
            <div id="audienceSummaryBar" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 4px; margin-bottom: 18px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-users-viewfinder" style="color: var(--red-primary);"></i>
                    <span style="font-family: var(--font-mono); font-size: 0.78rem; color: var(--text-secondary);">RECIPIENTS QUEUED:</span>
                    <span id="targetCountBadge" style="font-family: var(--font-mono); font-size: 0.84rem; font-weight: 800; color: #00ff66;">{{ $stats['total_subscribers'] }} subscribers</span>
                </div>
                <div id="sampleRecipientsContainer" style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted);">
                    Targeting all registered units with Telegram chat ID
                </div>
            </div>

            {{-- Message Body --}}
            <div class="cyber-form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label class="cyber-label" for="blastMessageText" style="margin-bottom: 0;">
                        <i class="fa-solid fa-comment-dots"></i> MESSAGE CONTENT *
                    </label>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <button type="button" class="cyber-btn cyber-btn-xs" onclick="insertFormatting('*', '*')" title="Bold">
                            <i class="fa-solid fa-bold"></i>
                        </button>
                        <button type="button" class="cyber-btn cyber-btn-xs" onclick="insertFormatting('_', '_')" title="Italic">
                            <i class="fa-solid fa-italic"></i>
                        </button>
                        <button type="button" class="cyber-btn cyber-btn-xs" onclick="insertFormatting('`', '`')" title="Inline Code">
                            <i class="fa-solid fa-code"></i>
                        </button>
                        <button type="button" class="cyber-btn cyber-btn-xs" onclick="insertLinkTemplate()" title="Insert Link">
                            <i class="fa-solid fa-link"></i>
                        </button>
                        <span id="charCounter" style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); margin-left: 8px;">0 / 4096</span>
                    </div>
                </div>

                <textarea 
                    name="message" 
                    id="blastMessageText" 
                    class="cyber-textarea" 
                    rows="7" 
                    placeholder="Type your broadcast message here...&#10;&#10;🎉 *XUNDEFINED SYSTEM NOTIFICATION*&#10;We are thrilled to announce a major update for our scripts and tools!&#10;&#10;Check out the new features and download the latest version in the portal."
                    required
                    maxlength="4096"
                    oninput="updateCharCount()"
                >{{ old('message') }}</textarea>
            </div>

            {{-- Optional Media & Action Button (Collapsible / Grid) --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-top: 14px; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                {{-- Image URL --}}
                <div class="cyber-form-group" style="margin-bottom: 0;">
                    <label class="cyber-label" for="blastImageUrl">
                        <i class="fa-solid fa-image"></i> BANNER / PHOTO URL (OPTIONAL)
                    </label>
                    <input 
                        type="url" 
                        name="image_url" 
                        id="blastImageUrl" 
                        class="cyber-input" 
                        placeholder="https://example.com/banner.png"
                        value="{{ old('image_url') }}"
                    >
                </div>

                {{-- Button Text --}}
                <div class="cyber-form-group" style="margin-bottom: 0;">
                    <label class="cyber-label" for="blastButtonText">
                        <i class="fa-solid fa-arrow-pointer"></i> BUTTON TEXT (OPTIONAL)
                    </label>
                    <input 
                        type="text" 
                        name="button_text" 
                        id="blastButtonText" 
                        class="cyber-input" 
                        placeholder="e.g. 🌐 View Website / Channel"
                        value="{{ old('button_text') }}"
                    >
                </div>

                {{-- Button URL --}}
                <div class="cyber-form-group" style="margin-bottom: 0;">
                    <label class="cyber-label" for="blastButtonUrl">
                        <i class="fa-solid fa-link"></i> BUTTON DESTINATION URL (OPTIONAL)
                    </label>
                    <input 
                        type="url" 
                        name="button_url" 
                        id="blastButtonUrl" 
                        class="cyber-input" 
                        placeholder="https://t.me/xundefinedchannel"
                        value="{{ old('button_url') }}"
                    >
                </div>
            </div>

            {{-- Form Footer Actions --}}
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid rgba(255, 23, 68, 0.2);">
                <button 
                    type="button" 
                    class="cyber-btn cyber-btn-secondary"
                    onclick="setTestModeAndSend()"
                    title="Send test message to your account before blasting"
                >
                    <i class="fa-solid fa-vial"></i>
                    <span>SEND TEST TO ME</span>
                </button>

                <div style="display: flex; gap: 10px;">
                    <button 
                        type="button" 
                        class="cyber-btn cyber-btn-primary" 
                        onclick="confirmAndDispatchBroadcast()"
                        style="padding: 10px 24px; font-weight: 700;"
                    >
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>DISPATCH BROADCAST NOW</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Panel: Transmission Logs & Blast History --}}
    <div class="cyber-panel">
        <div class="cyber-panel-header" style="justify-content: space-between;">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--red-primary);"></i>
                <span>BROADCAST HISTORY & TRANSMISSION LOGS</span>
            </div>

            {{-- Search Bar --}}
            <form method="GET" action="{{ route('admin.blast-message') }}" style="display: flex; gap: 6px; max-width: 320px;">
                <div class="search-input-wrapper" style="width: 220px;">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input 
                        type="text" 
                        name="q" 
                        class="tool-filter-input" 
                        placeholder="Search blasts..." 
                        value="{{ request('q') }}"
                        style="padding-left: 28px; font-size: 0.78rem;"
                    >
                </div>
                <button type="submit" class="cyber-btn cyber-btn-secondary" style="padding: 0 10px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>

        <div class="cyber-table-wrapper">
            <table class="cyber-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>CAMPAIGN / TITLE</th>
                        <th>TARGET</th>
                        <th>DELIVERIES</th>
                        <th>STATUS</th>
                        <th>DISPATCHED AT</th>
                        <th style="text-align: right; width: 140px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blasts as $index => $b)
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 700; font-family: var(--font-mono);">
                                {{ sprintf('%02d', ($blasts->currentPage() - 1) * $blasts->perPage() + $index + 1) }}
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #ffffff;">
                                    {{ $b->title ?: 'Untitled Broadcast' }}
                                </div>
                                <div style="font-size: 0.74rem; color: var(--text-muted); font-family: var(--font-mono); margin-top: 3px; max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ Str::limit($b->message, 80) }}
                                </div>
                            </td>
                            <td>
                                @if ($b->is_test)
                                    <span class="badge-status" style="border-color: #48cae4; background: rgba(72, 202, 228, 0.15); color: #48cae4;">
                                        <i class="fa-solid fa-vial"></i> TEST
                                    </span>
                                @else
                                    <span class="badge-status" style="background: rgba(255, 255, 255, 0.06); color: #ffffff;">
                                        {{ $b->target_audience_label }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div style="font-family: var(--font-mono); font-size: 0.82rem;">
                                    <span style="color: #00ff66; font-weight: 700;">{{ $b->successful_count }}</span>
                                    <span style="color: var(--text-muted);">/</span>
                                    <span>{{ $b->total_recipients }}</span>
                                    <span style="color: var(--text-muted); font-size: 0.72rem;">({{ $b->success_rate }}%)</span>
                                </div>
                                @if ($b->failed_count > 0)
                                    <div style="font-size: 0.7rem; color: #ff5252; font-family: var(--font-mono); margin-top: 2px;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> {{ $b->failed_count }} failed
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($b->status === 'completed')
                                    <span class="badge-status b-ok">
                                        <i class="fa-solid fa-check"></i> COMPLETED
                                    </span>
                                @elseif ($b->status === 'partial')
                                    <span class="badge-status" style="border-color: #ffd166; background: rgba(255, 209, 102, 0.12); color: #ffd166;">
                                        <i class="fa-solid fa-circle-exclamation"></i> PARTIAL
                                    </span>
                                @else
                                    <span class="badge-status b-err">
                                        <i class="fa-solid fa-xmark"></i> FAILED
                                    </span>
                                @endif
                            </td>
                            <td style="font-family: var(--font-mono); font-size: 0.76rem; color: var(--text-muted);">
                                <div>{{ $b->created_at->format('Y-m-d H:i') }}</div>
                                <div style="font-size: 0.7rem; color: var(--text-secondary);">by {{ $b->user?->name ?? 'System' }}</div>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <button 
                                        type="button" 
                                        class="cyber-btn cyber-btn-xs" 
                                        onclick="openBlastDetailsModal({{ $b->id }})" 
                                        title="View Detailed Transmission Log"
                                    >
                                        <i class="fa-solid fa-eye"></i> LOGS
                                    </button>

                                    <form action="{{ route('admin.blast-message.destroy', $b->id) }}" method="POST" onsubmit="return confirm('Delete this broadcast history entry?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cyber-btn cyber-btn-xs cyber-btn-danger" title="Delete record">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted); font-family: var(--font-mono);">
                                <i class="fa-solid fa-bullhorn" style="font-size: 2rem; color: rgba(255, 23, 68, 0.3); margin-bottom: 12px; display: block;"></i>
                                No broadcast transmissions recorded yet. Compose your first blast above!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($blasts->hasPages())
            <div style="padding: 16px 20px;">
                {{ $blasts->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Confirmation Modal before Dispatching --}}
<div class="cyber-modal-backdrop" id="blastConfirmModal" style="display: none;">
    <div class="cyber-modal-window" style="max-width: 520px;">
        <div class="cyber-corner top-left"></div>
        <div class="cyber-corner top-right"></div>
        <div class="cyber-corner bottom-left"></div>
        <div class="cyber-corner bottom-right"></div>

        <div class="cyber-modal-header">
            <div class="cyber-modal-title">
                <i class="fa-solid fa-triangle-exclamation" style="color: var(--red-primary);"></i>
                <span>CONFIRM TELEGRAM BROADCAST</span>
            </div>
            <button type="button" class="cyber-modal-close" onclick="closeBlastConfirmModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="cyber-modal-body">
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
                You are about to transmit a broadcast message to live Telegram users. Please verify details:
            </p>

            <div style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255, 255, 255, 0.1); padding: 14px; border-radius: 4px; margin-bottom: 16px; font-family: var(--font-mono); font-size: 0.78rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="color: var(--text-muted);">Target Audience:</span>
                    <span id="confirmTargetName" style="color: #ffffff; font-weight: 700;">-</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="color: var(--text-muted);">Est. Recipients:</span>
                    <span id="confirmRecipientsCount" style="color: #00ff66; font-weight: 800;">-</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="color: var(--text-muted);">Formatting Mode:</span>
                    <span id="confirmParseMode" style="color: #48cae4;">-</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);">Media / Button:</span>
                    <span id="confirmAttachments" style="color: #ffd166;">None</span>
                </div>
            </div>

            <div style="background: rgba(255, 23, 68, 0.08); border-left: 3px solid var(--red-primary); padding: 10px 12px; font-size: 0.76rem; color: #ff859b; margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i> Broadcast cannot be undone once dispatched. Messages will be delivered directly into users' Telegram chats.
            </div>
        </div>

        <div class="cyber-modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="cyber-btn cyber-btn-secondary" onclick="closeBlastConfirmModal()">
                CANCEL
            </button>
            <button type="button" class="cyber-btn cyber-btn-primary" id="confirmDispatchBtn" onclick="submitBlastForm()">
                <i class="fa-solid fa-paper-plane"></i>
                <span>YES, DISPATCH NOW</span>
            </button>
        </div>
    </div>
</div>

{{-- Blast Log Details Modal --}}
<div class="cyber-modal-backdrop" id="blastDetailsModal" style="display: none;">
    <div class="cyber-modal-window" style="max-width: 720px;">
        <div class="cyber-corner top-left"></div>
        <div class="cyber-corner top-right"></div>
        <div class="cyber-corner bottom-left"></div>
        <div class="cyber-corner bottom-right"></div>

        <div class="cyber-modal-header">
            <div class="cyber-modal-title">
                <i class="fa-solid fa-file-waveform" style="color: var(--red-primary);"></i>
                <span id="modalBlastTitle">TRANSMISSION LOG DETAILS</span>
            </div>
            <button type="button" class="cyber-modal-close" onclick="closeBlastDetailsModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="cyber-modal-body" style="max-height: 70vh; overflow-y: auto;">
            {{-- Quick meta grid --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-bottom: 16px;">
                <div style="background: rgba(255,255,255,0.04); padding: 10px; border-radius: 3px; font-family: var(--font-mono);">
                    <div style="font-size: 0.68rem; color: var(--text-muted);">TARGET</div>
                    <div id="modalTargetLabel" style="font-size: 0.85rem; font-weight: 700; color: #ffffff; margin-top: 2px;">-</div>
                </div>
                <div style="background: rgba(255,255,255,0.04); padding: 10px; border-radius: 3px; font-family: var(--font-mono);">
                    <div style="font-size: 0.68rem; color: var(--text-muted);">TOTAL RECIPIENTS</div>
                    <div id="modalTotalRecipients" style="font-size: 0.85rem; font-weight: 700; color: #ffffff; margin-top: 2px;">-</div>
                </div>
                <div style="background: rgba(255,255,255,0.04); padding: 10px; border-radius: 3px; font-family: var(--font-mono);">
                    <div style="font-size: 0.68rem; color: var(--text-muted);">DELIVERED</div>
                    <div id="modalSuccessCount" style="font-size: 0.85rem; font-weight: 700; color: #00ff66; margin-top: 2px;">-</div>
                </div>
                <div style="background: rgba(255,255,255,0.04); padding: 10px; border-radius: 3px; font-family: var(--font-mono);">
                    <div style="font-size: 0.68rem; color: var(--text-muted);">FAILED</div>
                    <div id="modalFailedCount" style="font-size: 0.85rem; font-weight: 700; color: #ff5252; margin-top: 2px;">-</div>
                </div>
            </div>

            {{-- Message Preview --}}
            <div style="margin-bottom: 18px;">
                <div style="font-size: 0.72rem; font-family: var(--font-mono); color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">
                    <i class="fa-solid fa-message"></i> Dispatched Message Payload
                </div>
                <div id="modalMessageContent" style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 4px; font-family: var(--font-mono); font-size: 0.78rem; white-space: pre-wrap; color: #ffffff; max-height: 180px; overflow-y: auto;">
                </div>
            </div>

            {{-- Per-Recipient Log Breakdown --}}
            <div>
                <div style="font-size: 0.72rem; font-family: var(--font-mono); color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">
                    <i class="fa-solid fa-list-check"></i> Recipient Transmission Log
                </div>
                <div style="max-height: 240px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.08); border-radius: 4px;">
                    <table class="cyber-table" style="margin-bottom: 0; font-size: 0.76rem;">
                        <thead>
                            <tr>
                                <th>CHAT ID / TARGET</th>
                                <th>RECIPIENT NAME</th>
                                <th>STATUS</th>
                                <th>DETAILS / ERROR</th>
                            </tr>
                        </thead>
                        <tbody id="modalRecipientDetailsBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="cyber-modal-footer" style="display: flex; justify-content: flex-end;">
            <button type="button" class="cyber-btn cyber-btn-secondary" onclick="closeBlastDetailsModal()">
                CLOSE
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Live character count
    function updateCharCount() {
        const text = document.getElementById('blastMessageText').value || '';
        const count = text.length;
        const counter = document.getElementById('charCounter');
        counter.textContent = `${count} / 4096`;
        if (count > 4000) {
            counter.style.color = 'var(--red-primary)';
        } else {
            counter.style.color = 'var(--text-muted)';
        }
    }

    // Insert formatting tags around selection
    function insertFormatting(startTag, endTag) {
        const textarea = document.getElementById('blastMessageText');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const selected = text.substring(start, end);
        const replacement = startTag + (selected || 'sample') + endTag;
        textarea.value = text.substring(0, start) + replacement + text.substring(end);
        textarea.focus();
        textarea.selectionStart = start + startTag.length;
        textarea.selectionEnd = start + startTag.length + (selected ? selected.length : 6);
        updateCharCount();
    }

    function insertLinkTemplate() {
        insertFormatting('[Link Text](', 'https://example.com)');
    }

    // Toggle custom or test boxes based on audience
    function handleAudienceChange() {
        const sel = document.getElementById('targetAudienceSelect').value;
        const customBox = document.getElementById('customChatIdsBox');
        const testBox = document.getElementById('testChatIdBox');
        const countBadge = document.getElementById('targetCountBadge');
        const sampleText = document.getElementById('sampleRecipientsContainer');

        if (sel === 'custom') {
            customBox.style.display = 'block';
            testBox.style.display = 'none';
            countBadge.textContent = 'Custom List';
            countBadge.style.color = '#ffd166';
            sampleText.textContent = 'Manual chat IDs list entered below';
        } else if (sel === 'test') {
            customBox.style.display = 'none';
            testBox.style.display = 'block';
            countBadge.textContent = '1 Test Recipient';
            countBadge.style.color = '#48cae4';
            sampleText.textContent = 'Single test message dispatch';
        } else {
            customBox.style.display = 'none';
            testBox.style.display = 'none';

            // Fetch live preview from server
            fetch(`{{ route('admin.blast-message.preview') }}?target=${sel}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        countBadge.textContent = `${data.count} subscribers`;
                        countBadge.style.color = '#00ff66';
                        if (data.sample && data.sample.length > 0) {
                            const names = data.sample.map(s => s.name || s.telegram).slice(0, 4).join(', ');
                            sampleText.textContent = `Includes: ${names}${data.count > 4 ? '...' : ''}`;
                        } else {
                            sampleText.textContent = 'No recipients currently in this group';
                        }
                    }
                })
                .catch(err => {
                    console.error('Preview error:', err);
                });
        }
    }

    // Quick Send Test to Admin
    function setTestModeAndSend() {
        const messageText = document.getElementById('blastMessageText').value.trim();
        if (!messageText) {
            alert('Please compose message content before sending a test.');
            document.getElementById('blastMessageText').focus();
            return;
        }

        const sel = document.getElementById('targetAudienceSelect');
        sel.value = 'test';
        handleAudienceChange();

        const testChatId = document.getElementById('testChatIdInput').value.trim();
        if (!testChatId) {
            const promptId = prompt('Enter Telegram Chat ID to send test message to:');
            if (promptId) {
                document.getElementById('testChatIdInput').value = promptId.trim();
            } else {
                return;
            }
        }

        document.getElementById('blastMessageForm').submit();
    }

    // Confirmation Modal
    function confirmAndDispatchBroadcast() {
        const messageText = document.getElementById('blastMessageText').value.trim();
        if (!messageText) {
            alert('Please enter a message to broadcast.');
            document.getElementById('blastMessageText').focus();
            return;
        }

        const sel = document.getElementById('targetAudienceSelect');
        const targetText = sel.options[sel.selectedIndex].text;
        const parseMode = document.getElementById('parseModeSelect').value;
        const imageUrl = document.getElementById('blastImageUrl').value.trim();
        const buttonText = document.getElementById('blastButtonText').value.trim();

        document.getElementById('confirmTargetName').textContent = targetText;
        document.getElementById('confirmRecipientsCount').textContent = document.getElementById('targetCountBadge').textContent;
        document.getElementById('confirmParseMode').textContent = parseMode;

        let attachDesc = [];
        if (imageUrl) attachDesc.push('Photo Attached');
        if (buttonText) attachDesc.push(`Button: "${buttonText}"`);
        document.getElementById('confirmAttachments').textContent = attachDesc.length > 0 ? attachDesc.join(', ') : 'None';

        document.getElementById('blastConfirmModal').style.display = 'flex';
    }

    function closeBlastConfirmModal() {
        document.getElementById('blastConfirmModal').style.display = 'none';
    }

    function submitBlastForm() {
        const btn = document.getElementById('confirmDispatchBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> TRANSMITTING...';
        document.getElementById('blastMessageForm').submit();
    }

    // View Details Modal
    function openBlastDetailsModal(blastId) {
        fetch(`{{ url('/xingzheng-panel/blast-message') }}/${blastId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const b = data.blast;
                document.getElementById('modalBlastTitle').textContent = `LOG: ${b.title || 'Untitled Broadcast'} (#${b.id})`;
                document.getElementById('modalTargetLabel').textContent = b.target_audience;
                document.getElementById('modalTotalRecipients').textContent = b.total_recipients;
                document.getElementById('modalSuccessCount').textContent = b.successful_count;
                document.getElementById('modalFailedCount').textContent = b.failed_count;
                document.getElementById('modalMessageContent').textContent = b.message;

                const tbody = document.getElementById('modalRecipientDetailsBody');
                tbody.innerHTML = '';

                if (b.details && b.details.length > 0) {
                    b.details.forEach(item => {
                        const tr = document.createElement('tr');
                        const isSent = item.status === 'sent';
                        tr.innerHTML = `
                            <td><code style="color: #48cae4;">${item.chat_id || '-'}</code></td>
                            <td>${item.name || '-'}</td>
                            <td>
                                <span class="badge-status ${isSent ? 'b-ok' : 'b-err'}">
                                    <i class="fa-solid ${isSent ? 'fa-check' : 'fa-xmark'}"></i> ${isSent ? 'SENT' : 'FAILED'}
                                </span>
                            </td>
                            <td style="color: ${isSent ? 'var(--text-muted)' : '#ff5252'}; font-family: var(--font-mono); font-size: 0.72rem;">
                                ${isSent ? ('Msg ID: ' + (item.message_id || 'OK')) : (item.error || 'Unknown error')}
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-muted);">No individual recipient logs recorded.</td></tr>';
                }

                document.getElementById('blastDetailsModal').style.display = 'flex';
            })
            .catch(err => {
                alert('Failed to load blast details: ' + err.message);
            });
    }

    function closeBlastDetailsModal() {
        document.getElementById('blastDetailsModal').style.display = 'none';
    }

    // Close modals on clicking backdrop
    window.addEventListener('click', function(e) {
        if (e.target === document.getElementById('blastConfirmModal')) {
            closeBlastConfirmModal();
        }
        if (e.target === document.getElementById('blastDetailsModal')) {
            closeBlastDetailsModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        updateCharCount();
    });
</script>
@endpush
@endsection
