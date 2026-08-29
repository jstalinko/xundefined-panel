@extends('layouts.dashboard')

@section('title', 'User Management - Admin')
@section('page-title', 'USER DIRECTORY')

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

@if (session('error'))
    <div class="cyber-alert" role="alert" style="border-color: var(--red-primary); background: rgba(255, 23, 68, 0.08); margin-bottom: 20px;">
        <i class="fa-solid fa-triangle-exclamation cyber-alert-icon" style="color: var(--red-primary);"></i>
        <div class="cyber-alert-content">
            <span class="cyber-alert-title" style="color: var(--red-primary);">ERROR</span>
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
            <span class="prompt-dir">users</span>
            <span class="prompt-symbol">&gt;</span>
            <span class="prompt-cmd">list --operatives</span>
        </div>
        <span class="terminal-badge">MAINFRAME ACCOUNTS: {{ $stats['total'] }}</span>
    </div>
    <div class="terminal-banner-body">
        <div class="welcome-copy">
            <h1 class="welcome-heading">OPERATIVE IDENTITY & ACCESS DIRECTORY</h1>
            <p class="welcome-subtext">
                Manage registered user credentials, clearance roles, connected domains, and account privileges.
            </p>
        </div>

        {{-- Filter & Action Controls --}}
        <div class="filter-controls">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input 
                    type="text" 
                    id="userClientSearch" 
                    class="tool-filter-input" 
                    placeholder="Search by name, email, or invite key..."
                    autocomplete="off"
                >
                <button type="button" id="clearUserSearchBtn" class="clear-search-btn" style="display: none;" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <button type="button" class="cyber-btn cyber-btn-primary" onclick="openCreateUserModal()">
                <i class="fa-solid fa-user-plus"></i>
                <span>NEW OPERATIVE</span>
            </button>
        </div>
    </div>
</div>

{{-- Quick Stats Cards --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 20px 0;">
    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">TOTAL USERS</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: #ffffff; margin-top: 4px;">{{ $stats['total'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary);">Registered Units</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(72, 202, 228, 0.12); border-color: rgba(72, 202, 228, 0.35); color: #48cae4;">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>

    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">ADMINISTRATORS</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: var(--red-primary); margin-top: 4px;">{{ $stats['admins'] }}</div>
            <div style="font-size: 0.75rem; color: var(--red-primary);">Root Clearance</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(255, 23, 68, 0.12); border-color: rgba(255, 23, 68, 0.35); color: var(--red-primary);">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
    </div>

    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">OPERATIVES</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: #00ff66; margin-top: 4px;">{{ $stats['members'] }}</div>
            <div style="font-size: 0.75rem; color: var(--status-online);">Active Members</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(0, 255, 102, 0.12); border-color: rgba(0, 255, 102, 0.35); color: var(--status-online);">
            <i class="fa-solid fa-user-check"></i>
        </div>
    </div>

    <div class="cyber-panel" style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">ACTIVE NODES</div>
            <div style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 800; color: #ffd166; margin-top: 4px;">{{ $stats['with_domains'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary);">Users with Domains</div>
        </div>
        <div class="cyber-avatar-box" style="background: rgba(255, 209, 102, 0.12); border-color: rgba(255, 209, 102, 0.35); color: #ffd166;">
            <i class="fa-solid fa-globe"></i>
        </div>
    </div>
</div>

{{-- Users Table Panel --}}
<div class="cyber-panel">
    <div class="cyber-panel-header">
        <div class="cyber-panel-title">
            <i class="fa-solid fa-users"></i>
            OPERATIVE DIRECTORY MATRIX
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge-status b-ok">
                <span class="status-dot online"></span>
                <span id="userCountBadge">{{ $users->total() }} OPERATIVES FOUND</span>
            </span>
            <button type="button" class="cyber-btn cyber-btn-primary cyber-btn-sm" onclick="openCreateUserModal()">
                <i class="fa-solid fa-plus"></i> NEW USER
            </button>
        </div>
    </div>

    @if ($users->isEmpty())
        <div class="empty-state" style="padding: 48px 20px;">
            <i class="fa-solid fa-user-xmark empty-icon"></i>
            <div class="empty-title">NO OPERATIVES FOUND</div>
            <p class="empty-desc">No accounts found matching your query criteria.</p>
        </div>
    @else
        <div class="cyber-table-container">
            <table class="cyber-data-table" id="usersTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>OPERATIVE IDENTITY</th>
                        <th>CLEARANCE ROLE</th>
                        <th>INVITE KEY</th>
                        <th>DOMAINS</th>
                        <th>ORDERS</th>
                        <th>JOINED DATE</th>
                        <th style="text-align: right;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $index => $u)
                        <tr class="user-row-item" data-name="{{ strtolower($u->name) }}" data-email="{{ strtolower($u->email) }}" data-invite="{{ strtolower($u->invite_key ?? '') }}">
                            <td style="color: var(--text-muted); font-weight: 700;">
                                {{ sprintf('%02d', $index + 1) }}
                            </td>
                            <td>
                                <div class="domain-name-cell">
                                    <i class="fa-solid fa-user-astronaut" style="color: {{ (int) $u->role === 1 ? 'var(--red-primary)' : '#48cae4' }}; font-size: 0.95rem;"></i>
                                    <a href="{{ route('user.show', $u->id) }}" style="font-weight: 700; color: #ffffff; text-decoration: none;">
                                        {{ $u->name }}
                                    </a>
                                    <button 
                                        type="button" 
                                        class="cyber-copy-btn" 
                                        style="padding: 2px 6px; font-size: 0.65rem;" 
                                        onclick="copyText('{{ $u->email }}', this)" 
                                        title="Copy email address"
                                    >
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                                <div style="color: #00ff66; font-family: var(--font-mono); font-size: 0.74rem; margin-top: 2px;">
                                    <i class="fa-solid fa-envelope" style="font-size: 0.68rem;"></i> {{ $u->email }}
                                </div>
                            </td>
                            <td>
                                @if ((int) $u->role === 1)
                                    <span class="badge-status b-err" style="border-color: var(--red-primary); background: rgba(255,23,68,0.15); color: #ffffff;">
                                        <i class="fa-solid fa-shield-halved" style="color: var(--red-primary);"></i> ADMIN
                                    </span>
                                @else
                                    <span class="badge-status b-ok">
                                        <i class="fa-solid fa-user"></i> MEMBER
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($u->invite_key)
                                    <code style="font-family: var(--font-mono); font-size: 0.78rem; font-weight: 700; color: #ffd166; background: rgba(255,209,102,0.1); border: 1px solid rgba(255,209,102,0.3); padding: 2px 6px; border-radius: 2px;">
                                        {{ $u->invite_key }}
                                    </code>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.74rem;">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge-status" style="font-size: 0.72rem; background: rgba(255,255,255,0.06); color: #ffffff;">
                                    <i class="fa-solid fa-globe" style="color: var(--red-primary);"></i> {{ $u->domains_count }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-status" style="font-size: 0.72rem; background: rgba(255,255,255,0.06); color: #ffffff;">
                                    <i class="fa-solid fa-cart-shopping" style="color: #ffd166;"></i> {{ $u->orders_count }}
                                </span>
                            </td>
                            <td style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.76rem;">
                                {{ $u->created_at ? $u->created_at->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                    <a 
                                        href="{{ route('user.show', $u->id) }}" 
                                        class="cyber-btn cyber-btn-secondary cyber-btn-xs" 
                                        title="View Profile Details"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <button 
                                        type="button" 
                                        class="cyber-btn cyber-btn-primary cyber-btn-xs" 
                                        onclick="openEditUserModal({{ json_encode($u) }})"
                                        title="Edit User"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i> EDIT
                                    </button>

                                    @if ($u->id !== auth()->id())
                                        <form action="{{ route('user.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to terminate user: {{ $u->name }} ({{ $u->email }})?');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cyber-btn cyber-btn-xs" style="background: rgba(255, 23, 68, 0.15); border: 1px solid var(--red-primary); color: var(--red-primary);" title="Terminate user">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $users->links() }}
        </div>
    @endif
</div>

{{-- Interactive User Modal (Create & Edit) --}}
<div class="cyber-modal-backdrop" id="userModalBackdrop">
    <div class="cyber-modal-window" style="max-width: 520px;">
        <div class="cyber-corner top-left"></div>
        <div class="cyber-corner top-right"></div>
        <div class="cyber-corner bottom-left"></div>
        <div class="cyber-corner bottom-right"></div>

        <div class="cyber-modal-header">
            <div class="cyber-modal-title">
                <i class="fa-solid fa-user-shield" style="color: var(--red-primary);"></i>
                <span id="userModalHeaderTitle">NEW OPERATIVE ACCOUNT</span>
            </div>
            <button type="button" class="cyber-modal-close" onclick="closeUserModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('user.store') }}" method="POST" id="userForm">
            @csrf
            <div id="userMethodSpoofContainer"></div>

            <div class="cyber-modal-body">
                {{-- Handle / Name --}}
                <div class="cyber-form-group">
                    <label class="cyber-label" for="modalUserName">
                        <i class="fa-solid fa-user"></i> OPERATIVE HANDLE / NAME *
                    </label>
                    <input 
                        type="text" 
                        id="modalUserName" 
                        name="name" 
                        class="cyber-input" 
                        placeholder="e.g. Agent_Cipher" 
                        required
                    >
                </div>

                {{-- Email --}}
                <div class="cyber-form-group" style="margin-top: 14px;">
                    <label class="cyber-label" for="modalUserEmail">
                        <i class="fa-solid fa-envelope"></i> IDENTITY EMAIL *
                    </label>
                    <input 
                        type="email" 
                        id="modalUserEmail" 
                        name="email" 
                        class="cyber-input" 
                        placeholder="e.g. operative@xundefined.local" 
                        required
                    >
                </div>

                {{-- Password (Optional on Edit) --}}
                <div class="cyber-form-group" style="margin-top: 14px;">
                    <label class="cyber-label" for="modalUserPass">
                        <i class="fa-solid fa-lock"></i> <span id="modalPassLabel">SECURITY PASSCODE *</span>
                    </label>
                    <input 
                        type="password" 
                        id="modalUserPass" 
                        name="password" 
                        class="cyber-input" 
                        placeholder="••••••••••••"
                    >
                    <div id="modalPassHint" style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; display: none;">
                        Leave blank to retain current passcode.
                    </div>
                </div>

                {{-- Role & Invite Key in 2 Columns --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px;">
                    {{-- Role --}}
                    <div class="cyber-form-group">
                        <label class="cyber-label" for="modalUserRole">
                            <i class="fa-solid fa-shield-halved"></i> CLEARANCE ROLE *
                        </label>
                        <select id="modalUserRole" name="role" class="cyber-input" required>
                            <option value="2">Cyber Operative (Member)</option>
                            <option value="1">System Administrator</option>
                        </select>
                    </div>

                    {{-- Invite Key --}}
                    <div class="cyber-form-group">
                        <label class="cyber-label" for="modalUserInvite">
                            <i class="fa-solid fa-ticket"></i> INVITE KEY
                        </label>
                        <input 
                            type="text" 
                            id="modalUserInvite" 
                            name="invite_key" 
                            class="cyber-input" 
                            placeholder="XU-XXXX-XXXX"
                            style="text-transform: uppercase;"
                        >
                    </div>
                </div>
            </div>

            <div class="cyber-modal-footer">
                <button type="button" class="cyber-btn cyber-btn-secondary cyber-btn-md" onclick="closeUserModal()">
                    CANCEL
                </button>
                <button type="submit" class="cyber-btn cyber-btn-primary cyber-btn-md" id="modalUserSubmitBtn">
                    <i class="fa-solid fa-check"></i> SAVE OPERATIVE
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live Search Filter for Users Table
    const userSearchInput = document.getElementById('userClientSearch');
    const clearUserSearchBtn = document.getElementById('clearUserSearchBtn');
    const userRows = document.querySelectorAll('.user-row-item');
    const userCountBadge = document.getElementById('userCountBadge');

    if (userSearchInput) {
        userSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let matches = 0;

            if (clearUserSearchBtn) {
                clearUserSearchBtn.style.display = query.length > 0 ? 'flex' : 'none';
            }

            userRows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const invite = row.getAttribute('data-invite') || '';

                if (name.includes(query) || email.includes(query) || invite.includes(query)) {
                    row.style.display = '';
                    matches++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (userCountBadge) {
                userCountBadge.textContent = matches + ' OPERATIVES FOUND';
            }
        });
    }

    if (clearUserSearchBtn) {
        clearUserSearchBtn.addEventListener('click', function () {
            userSearchInput.value = '';
            userSearchInput.dispatchEvent(new Event('input'));
            userSearchInput.focus();
        });
    }

    // Modal Handlers
    const userBackdrop = document.getElementById('userModalBackdrop');
    const userForm = document.getElementById('userForm');
    const userHeaderTitle = document.getElementById('userModalHeaderTitle');
    const userSubmitBtn = document.getElementById('modalUserSubmitBtn');
    const userNameInput = document.getElementById('modalUserName');
    const userEmailInput = document.getElementById('modalUserEmail');
    const userPassInput = document.getElementById('modalUserPass');
    const userPassLabel = document.getElementById('modalPassLabel');
    const userPassHint = document.getElementById('modalPassHint');
    const userRoleInput = document.getElementById('modalUserRole');
    const userInviteInput = document.getElementById('modalUserInvite');
    const userMethodContainer = document.getElementById('userMethodSpoofContainer');

    function openCreateUserModal() {
        if (!userBackdrop || !userForm) return;
        userForm.action = "{{ route('user.store') }}";
        userMethodContainer.innerHTML = '';
        userHeaderTitle.textContent = 'NEW OPERATIVE ACCOUNT';
        userSubmitBtn.innerHTML = '<i class="fa-solid fa-check"></i> SAVE OPERATIVE';

        userNameInput.value = '';
        userEmailInput.value = '';
        userPassInput.value = '';
        userPassInput.required = true;
        userPassLabel.textContent = 'SECURITY PASSCODE *';
        userPassHint.style.display = 'none';
        userRoleInput.value = '2';
        userInviteInput.value = 'XU-GENESIS';

        userBackdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            if (userNameInput) userNameInput.focus();
        }, 100);
    }

    function openEditUserModal(data) {
        if (!userBackdrop || !userForm) return;
        userForm.action = `/admin/user/${data.id}`;
        userMethodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        userHeaderTitle.textContent = `EDIT OPERATIVE: ${data.name}`;
        userSubmitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> SAVE CHANGES';

        userNameInput.value = data.name || '';
        userEmailInput.value = data.email || '';
        userPassInput.value = '';
        userPassInput.required = false;
        userPassLabel.textContent = 'CHANGE PASSCODE (OPTIONAL)';
        userPassHint.style.display = 'block';
        userRoleInput.value = data.role !== undefined ? data.role : 2;
        userInviteInput.value = data.invite_key || '';

        userBackdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            if (userNameInput) userNameInput.focus();
        }, 100);
    }

    function closeUserModal() {
        if (userBackdrop) {
            userBackdrop.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Expose functions globally
    window.openCreateUserModal = openCreateUserModal;
    window.openEditUserModal = openEditUserModal;
    window.closeUserModal = closeUserModal;

    if (userBackdrop) {
        userBackdrop.addEventListener('click', function (e) {
            if (e.target === userBackdrop) {
                closeUserModal();
            }
        });
    }

    // Close on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && userBackdrop && userBackdrop.classList.contains('active')) {
            closeUserModal();
        }
    });
</script>
@endpush
