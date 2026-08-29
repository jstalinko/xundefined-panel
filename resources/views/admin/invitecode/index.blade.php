@extends('layouts.dashboard')

@section('title', 'Invite Codes Management - Admin')
@section('page-title', 'INVITE CODES')

@section('content')
{{-- Flash Status Notifications --}}
@if (session('status'))
    <div class="cyber-alert" role="alert" style="border-color: var(--status-online); background: rgba(0, 255, 102, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-circle-check cyber-alert-icon" style="color: var(--status-online);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--status-online);">SUCCESS</span>
            <span class="cyber-alert-msg">{{ session('status') }}</span>
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
            <span class="prompt-dir">invitecode</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">list --tokens</span>
        </div>
        <span class="terminal-badge">TOTAL CODES: {{ $stats['total'] }}</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">INVITE CODE MATRIX</h1>
            <p class="welcome-subtext">
                Generate registration clearance keys randomly or with custom alphanumeric identifiers, and manage validity schedules.
            </p>
        </div>

        {{-- Filter & Action Controls --}}
        <div class="filter-controls">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input 
                    type="text" 
                    id="inviteClientSearch" 
                    class="tool-filter-input" 
                    placeholder="Search codes (e.g. XU-8F3A, VIP)..."
                    autocomplete="off"
                >
                <button type="button" id="clearInviteSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <button type="button" class="cyber-btn cyber-btn-primary" onclick="openCreateInviteModal()">
                <i class="fa-solid fa-plus"></i>
                <span>GENERATE CODE</span>
            </button>
        </div>
    </div>
</div>

{{-- Quick Stats Cards --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 20px 0;">
    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">TOTAL CODES</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: #ffffff; margin-top: 4px;">{{ $stats['total'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary);">Generated Tokens</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(255, 23, 68, 0.1); border-color: rgba(255, 23, 68, 0.3); color: var(--red-primary);">
            <i class="fa-solid fa-key"></i>
        </div>
    </div>

    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">ACTIVE CODES</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: #00ff66; margin-top: 4px;">{{ $stats['active'] }}</div>
            <div style="font-size: 0.75rem; color: var(--status-online);">Ready to register</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(0, 255, 102, 0.1); border-color: rgba(0, 255, 102, 0.3); color: var(--status-online);">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>

    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">CLAIMED CODES</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: #ffd166; margin-top: 4px;">{{ $stats['used'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary);">Claimed by Operatives</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(255, 209, 102, 0.1); border-color: rgba(255, 209, 102, 0.3); color: #ffd166;">
            <i class="fa-solid fa-user-check"></i>
        </div>
    </div>

    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">EXPIRED CODES</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: #ff5252; margin-top: 4px;">{{ $stats['expired'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Passed deadline</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(255, 82, 82, 0.1); border-color: rgba(255, 82, 82, 0.3); color: #ff5252;">
            <i class="fa-solid fa-clock"></i>
        </div>
    </div>
</div>

{{-- Invite Codes Table Panel --}}
<div class="cyber-panel">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-key"></i>
            INVITE CODES DIRECTORY
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge-status b-ok">
                <span class="status-dot online"></span>
                <span id="inviteCountBadge">{{ $inviteCodes->total() }} CODES FOUND</span>
            </span>
            <button type="button" class="cyber-btn cyber-btn-primary cyber-btn-sm" onclick="openCreateInviteModal()">
                <i class="fa-solid fa-plus"></i> NEW CODE
            </button>
        </div>
    </div>

    @if ($inviteCodes->isEmpty())
        <div class="empty-state" style="padding: 48px 20px;">
            <i class="fa-solid fa-key empty-icon"></i>
            <div class="empty-title">NO INVITE CODES CREATED</div>
            <p class="empty-desc">
                Generate your first registration code randomly or with a custom string.
            </p>
            <button type="button" class="cyber-btn cyber-btn-primary cyber-btn-md" onclick="openCreateInviteModal()" style="margin-top: 10px;">
                <i class="fa-solid fa-plus"></i> GENERATE FIRST CODE
            </button>
        </div>
    @else
        <div class="cyber-table-container">
            <table class="cyber-data-table" id="inviteCodesTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>INVITE CODE</th>
                        <th>EXPIRES AT</th>
                        <th>CLAIMED BY</th>
                        <th>GENERATED VIA</th>
                        <th>STATUS</th>
                        <th>CREATED DATE</th>
                        <th style="text-align: right;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inviteCodes as $index => $code)
                        @php
                            $isExpired = $code->isExpired();
                            $isUsed = $code->used;
                            $isActive = $code->isValid();
                        @endphp
                        <tr class="invite-row-item" data-code="{{ strtolower($code->code) }}">
                            <td style="color: var(--text-muted); font-weight: 700;">
                                {{ sprintf('%02d', $index + 1) }}
                            </td>
                            <td>
                                <div class="domain-name-cell">
                                    <i class="fa-solid fa-key" style="color: var(--red-primary); font-size: 0.85rem;"></i>
                                    <code style="font-family: var(--font-mono); font-size: 0.88rem; font-weight: 800; color: #ffffff; letter-spacing: 0.08em; background: rgba(255, 23, 68, 0.1); border: 1px solid rgba(255, 23, 68, 0.3); padding: 2px 8px; border-radius: var(--radius-sm);">
                                        {{ $code->code }}
                                    </code>
                                    <button 
                                        type="button" 
                                        class="cyber-copy-btn" 
                                        style="padding: 2px 6px; font-size: 0.65rem;" 
                                        onclick="copyText('{{ $code->code }}', this)" 
                                        title="Copy invite code"
                                    >
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                @if ($code->expired_at)
                                    <span style="font-family: var(--font-mono); font-size: 0.78rem; color: {{ $isExpired ? '#ff5252' : 'var(--text-secondary)' }};">
                                        <i class="fa-solid fa-calendar-xmark" style="font-size: 0.7rem;"></i>
                                        {{ $code->expired_at->format('Y-m-d H:i') }}
                                        @if ($isExpired)
                                            <span style="color: #ff5252; font-weight: 700; font-size: 0.68rem;">[EXPIRED]</span>
                                        @endif
                                    </span>
                                @else
                                    <span style="font-family: var(--font-mono); font-size: 0.76rem; color: #00ff66;">
                                        <i class="fa-solid fa-infinity"></i> Never (Lifetime)
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($isUsed)
                                    @if ($code->user)
                                        <div style="color: #ffffff; font-weight: 600; font-size: 0.84rem;">
                                            {{ $code->user->name }}
                                        </div>
                                        <div style="color: #00ff66; font-family: var(--font-mono); font-size: 0.72rem;">
                                            {{ $code->user->email }}
                                        </div>
                                    @else
                                        <span class="badge-status" style="background: rgba(255, 209, 102, 0.15); border: 1px solid #ffd166; color: #ffd166;">
                                            Claimed (User #{{ $code->used_by_user_id }})
                                        </span>
                                    @endif
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.78rem;">- Unclaimed -</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge-status" style="font-size: 0.72rem; text-transform: uppercase;">
                                    {{ $code->generate_via ?? 'ADMIN' }}
                                </span>
                            </td>
                            <td>
                                @if ($isActive)
                                    <span class="badge-status b-ok">
                                        <span class="status-dot online"></span>
                                        ACTIVE
                                    </span>
                                @elseif ($isUsed)
                                    <span class="badge-status" style="background: rgba(255, 209, 102, 0.15); border: 1px solid #ffd166; color: #ffd166;">
                                        <span class="status-dot" style="background: #ffd166;"></span>
                                        CLAIMED
                                    </span>
                                @elseif ($isExpired)
                                    <span class="badge-status b-err">
                                        <span class="status-dot offline"></span>
                                        EXPIRED
                                    </span>
                                @else
                                    <span class="badge-status b-err">
                                        <span class="status-dot offline"></span>
                                        INACTIVE
                                    </span>
                                @endif
                            </td>
                            <td style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.76rem;">
                                {{ $code->created_at ? $code->created_at->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                    <button 
                                        type="button" 
                                        class="cyber-btn cyber-btn-primary cyber-btn-xs" 
                                        onclick="openEditInviteModal({{ json_encode($code) }})"
                                        title="Edit Code"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i> EDIT
                                    </button>

                                    <form action="{{ route('invitecode.destroy', $code->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete invite code: {{ $code->code }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cyber-btn cyber-btn-xs" style="background: rgba(255, 23, 68, 0.15); border: 1px solid var(--red-primary); color: var(--red-primary);" title="Delete code">
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
            {{ $inviteCodes->links() }}
        </div>
    @endif
</div>

{{-- Interactive Invite Code Modal (Create & Edit) --}}
<div class="cyber-modal-backdrop" id="inviteModalBackdrop">
    <div class="cyber-modal-window" style="max-width: 520px;">
        <div class="cyber-corner top-left"></div>
        <div class="cyber-corner top-right"></div>
        <div class="cyber-corner bottom-left"></div>
        <div class="cyber-corner bottom-right"></div>

        <div class="cyber-modal-header">
            <div class="cyber-modal-title">
                <i class="fa-solid fa-key" style="color: var(--red-primary);"></i>
                <span id="modalHeaderTitle">GENERATE INVITE CODE</span>
            </div>
            <button type="button" class="cyber-modal-close" onclick="closeInviteModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('invitecode.store') }}" method="POST" id="inviteCodeForm">
            @csrf
            <div id="methodSpoofContainer"></div>

            <div class="cyber-modal-body">
                {{-- Code Input with Random Generate Button --}}
                <div class="cyber-form-group">
                    <label class="cyber-label" for="modalCodeInput">
                        <i class="fa-solid fa-ticket"></i> INVITE CODE STRING *
                    </label>
                    <div style="display: flex; gap: 8px;">
                        <input 
                            type="text" 
                            id="modalCodeInput" 
                            name="code" 
                            class="cyber-input" 
                            placeholder="e.g. XU-8F3A-9B2C or CUSTOM-KEY" 
                            style="font-family: var(--font-mono); font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #ffffff;"
                            required
                        >
                        <button 
                            type="button" 
                            class="cyber-btn cyber-btn-secondary" 
                            onclick="generateNewRandomCode()"
                            style="white-space: nowrap; padding: 0 14px; font-size: 0.78rem;"
                            title="Generate Random Code"
                        >
                            <i class="fa-solid fa-arrows-rotate"></i> RANDOM
                        </button>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">
                        Type your own custom code or click <strong>RANDOM</strong> to generate one.
                    </div>
                </div>

                {{-- Expiry Date & Presets --}}
                <div class="cyber-form-group" style="margin-top: 14px;">
                    <label class="cyber-label" for="modalExpiresInput">
                        <i class="fa-solid fa-calendar-clock"></i> EXPIRES AT (OPTIONAL)
                    </label>
                    <input 
                        type="datetime-local" 
                        id="modalExpiresInput" 
                        name="expires_at" 
                        class="cyber-input"
                    >
                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;">
                        <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-xs" onclick="setExpiryPreset(1)">+1 Day</button>
                        <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-xs" onclick="setExpiryPreset(7)">+7 Days</button>
                        <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-xs" onclick="setExpiryPreset(30)">+30 Days</button>
                        <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-xs" onclick="clearExpiryPreset()">No Expiry (Never)</button>
                    </div>
                </div>

                {{-- Claimed Status Switch (Only in Edit Mode) --}}
                <div class="cyber-form-group" id="usedStatusGroup" style="margin-top: 14px; display: none;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #ffffff; font-family: var(--font-mono); font-size: 0.84rem;">
                        <input type="checkbox" id="modalUsedInput" name="used" value="1" style="width: 16px; height: 16px; accent-color: var(--red-primary);">
                        <span>CLAIMED / USED (Mark code as claimed)</span>
                    </label>
                </div>
            </div>

            <div class="cyber-modal-footer">
                <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-md" onclick="closeInviteModal()">
                    CANCEL
                </button>
                <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-md" id="modalSubmitBtn">
                    <i class="fa-solid fa-check"></i> SAVE INVITE CODE
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live Search Filter for Invite Codes Table
    const inviteSearchInput = document.getElementById('inviteClientSearch');
    const clearInviteSearchBtn = document.getElementById('clearInviteSearchBtn');
    const inviteRows = document.querySelectorAll('.invite-row-item');
    const inviteCountBadge = document.getElementById('inviteCountBadge');

    if (inviteSearchInput) {
        inviteSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let matches = 0;

            if (clearInviteSearchBtn) {
                clearInviteSearchBtn.style.display = query.length > 0 ? 'flex' : 'none';
            }

            inviteRows.forEach(row => {
                const code = row.getAttribute('data-code') || '';

                if (code.includes(query)) {
                    row.style.display = '';
                    matches++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (inviteCountBadge) {
                inviteCountBadge.textContent = matches + ' CODES FOUND';
            }
        });
    }

    if (clearInviteSearchBtn) {
        clearInviteSearchBtn.addEventListener('click', function () {
            inviteSearchInput.value = '';
            inviteSearchInput.dispatchEvent(new Event('input'));
            inviteSearchInput.focus();
        });
    }

    // Modal Handlers
    const backdrop = document.getElementById('inviteModalBackdrop');
    const form = document.getElementById('inviteCodeForm');
    const headerTitle = document.getElementById('modalHeaderTitle');
    const submitBtn = document.getElementById('modalSubmitBtn');
    const codeInput = document.getElementById('modalCodeInput');
    const expiresInput = document.getElementById('modalExpiresInput');
    const usedGroup = document.getElementById('usedStatusGroup');
    const usedInput = document.getElementById('modalUsedInput');
    const methodContainer = document.getElementById('methodSpoofContainer');

    function openCreateInviteModal() {
        if (!backdrop || !form) return;
        form.action = "{{ route('invitecode.store') }}";
        methodContainer.innerHTML = '';
        headerTitle.textContent = 'GENERATE INVITE CODE';
        submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> SAVE INVITE CODE';

        generateNewRandomCode();
        expiresInput.value = '';
        usedGroup.style.display = 'none';
        usedInput.checked = false;

        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            if (codeInput) codeInput.focus();
        }, 100);
    }

    function openEditInviteModal(data) {
        if (!backdrop || !form) return;
        form.action = `/admin/invitecode/${data.id}`;
        methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        headerTitle.textContent = `EDIT INVITE CODE: ${data.code}`;
        submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> SAVE CHANGES';

        codeInput.value = data.code;
        const expiryVal = data.expired_at || data.expires_at;
        if (expiryVal) {
            const d = new Date(expiryVal);
            const tzOffset = d.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(d.getTime() - tzOffset)).toISOString().slice(0, 16);
            expiresInput.value = localISOTime;
        } else {
            expiresInput.value = '';
        }

        usedGroup.style.display = 'block';
        usedInput.checked = Boolean(data.used);

        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            if (codeInput) codeInput.focus();
        }, 100);
    }

    function closeInviteModal() {
        if (backdrop) {
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Expose to window for global access
    window.openCreateInviteModal = openCreateInviteModal;
    window.openEditInviteModal = openEditInviteModal;
    window.closeInviteModal = closeInviteModal;
    window.generateNewRandomCode = generateNewRandomCode;
    window.setExpiryPreset = setExpiryPreset;
    window.clearExpiryPreset = clearExpiryPreset;

    if (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) {
                closeInviteModal();
            }
        });
    }

    // Close on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && backdrop && backdrop.classList.contains('active')) {
            closeInviteModal();
        }
    });

    // Random Code Generator in JavaScript
    function generateNewRandomCode() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let part1 = '';
        let part2 = '';
        for (let i = 0; i < 4; i++) {
            part1 += chars.charAt(Math.floor(Math.random() * chars.length));
            part2 += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        if (codeInput) {
            codeInput.value = `XU-${part1}-${part2}`;
        }
    }

    // Expiry Presets
    function setExpiryPreset(days) {
        const now = new Date();
        now.setDate(now.getDate() + days);
        const tzOffset = now.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(now.getTime() - tzOffset)).toISOString().slice(0, 16);
        if (expiresInput) {
            expiresInput.value = localISOTime;
        }
    }

    function clearExpiryPreset() {
        if (expiresInput) {
            expiresInput.value = '';
        }
    }
</script>
@endpush
