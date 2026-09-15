<x-dashboard-layout
    title="User Management"
    subtitle="Search accounts, change roles, and suspend, reactivate or close access"
    active="users"
>
    @if (session('status'))
        <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
            <i class="ti ti-circle-check"></i> {{ session('status') }}
        </div>
    @endif

    <style>
        /* Row action menu — one kebab button per user instead of a row of controls. */
        .row-actions { position:relative; }
        .row-actions .kebab {
            width:34px; height:34px; padding:0; display:flex; align-items:center; justify-content:center;
            font-size:18px; line-height:1;
        }
        .action-menu {
            position:absolute; top:calc(100% + 6px); right:0; z-index:40; min-width:210px;
            background:var(--card-bg, var(--panel-bg)); border:1px solid var(--glass-border);
            border-radius:12px; padding:6px; box-shadow:0 12px 28px rgba(0,0,0,.18);
            display:none;
        }
        .action-menu.open { display:block; }
        /* The cards below this one would otherwise paint over the open menu. */
        .claim-item-card.menu-open { position:relative; z-index:50; }
        .action-menu .menu-label {
            font-size:10px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
            color:var(--muted); padding:8px 10px 4px;
        }
        .action-menu button.menu-item {
            width:100%; display:flex; align-items:center; gap:9px; padding:8px 10px;
            background:none; border:none; border-radius:8px; cursor:pointer;
            font-size:12.5px; font-weight:500; color:var(--text); text-align:left;
        }
        .action-menu button.menu-item:hover { background:var(--panel-bg); color:var(--primary); }
        .action-menu button.menu-item.is-current { color:var(--primary); font-weight:700; }
        .action-menu button.menu-item.danger { color:#D32F2F; }
        .action-menu button.menu-item.danger:hover { background:rgba(211,47,47,.08); color:#D32F2F; }
        .action-menu .menu-sep { height:1px; background:var(--glass-border); margin:6px 4px; }
        .action-menu .menu-item i { font-size:15px; width:16px; text-align:center; }
    </style>

    <div class="search-container">
        <x-search-bar
            :action="route('admin.users.index')"
            :suggest="route('admin.users.suggest')"
            :hidden="['role' => request('role'), 'status' => request('status')]"
            placeholder="Search by name, email, or reg. no..."
        />

        <div class="category-chips">
            <a href="{{ route('admin.users.index', array_filter(['q' => request('q')])) }}"
               class="chip {{ ! request('role') && ! $showDeleted ? 'active' : '' }}">
                <i class="ti ti-apps"></i> All
            </a>
            @foreach ($roles as $role)
                <a href="{{ route('admin.users.index', array_filter(['q' => request('q'), 'role' => $role])) }}"
                   class="chip {{ request('role') === $role && ! $showDeleted ? 'active' : '' }}">
                    {{ ucfirst(str_replace('_', ' ', $role)) }}
                </a>
            @endforeach
            <a href="{{ route('admin.users.index', array_filter(['q' => request('q'), 'role' => request('role'), 'status' => 'deleted'])) }}"
               class="chip {{ $showDeleted ? 'active' : '' }}">
                <i class="ti ti-archive"></i> Deleted ({{ $deletedCount }})
            </a>
        </div>
    </div>

    @if ($users->isEmpty())
        <div class="empty-state">
            <i class="ti ti-users"></i>
            @if (request('q') || request('role'))
                No accounts match your search.
            @else
                No accounts found.
            @endif
        </div>
    @else
        <div class="claims-list">
            @foreach ($users as $user)
                @php
                    $roleOptions = [
                        'student_staff:student' => ['Student', 'ti-school', $user->isStudentStaff() && $user->isStudent()],
                        'student_staff:staff'   => ['Staff', 'ti-briefcase', $user->isStudentStaff() && $user->isStaff()],
                        'officer:none'          => ['Officer', 'ti-shield-check', $user->isOfficer()],
                        'admin:none'            => ['Admin', 'ti-crown', $user->isAdmin()],
                    ];
                @endphp
                <div class="claim-item-card" style="align-items:flex-start; {{ $user->trashed() ? 'opacity:.6;' : '' }}">
                    <div class="claim-item-icon">
                        <i class="ti ti-user"></i>
                    </div>

                    <div class="claim-item-info">
                        <h3>{{ $user->name }}</h3>
                        <p>{{ $user->email }} &middot; {{ $user->reg_no }}</p>
                        <p style="margin-top:6px;">
                            <span class="badge {{ $user->roleBadgeClass() }}">{{ $user->roleLabel() }}</span>
                            @if ($user->trashed())
                                <span class="badge badge-lost">Deleted {{ $user->deleted_at->format('d M Y') }}</span>
                            @else
                                <span class="badge {{ $user->isActive() ? 'badge-found' : 'badge-lost' }}">{{ ucfirst($user->status) }}</span>
                            @endif
                        </p>
                    </div>

                    <div class="claim-item-action">
                        @if ($user->id === auth()->id())
                            <span class="hint">This is your account</span>
                        @elseif ($user->trashed())
                            <form method="POST" action="{{ route('admin.users.restore', $user) }}"
                                  onsubmit="return confirm('Restore {{ $user->name }}\'s account?')">
                                @csrf
                                <button type="submit" class="btn btn-ghost">
                                    <i class="ti ti-arrow-back-up"></i> Restore
                                </button>
                            </form>
                        @else
                            <div class="row-actions">
                                <button type="button" class="btn btn-ghost kebab"
                                        aria-haspopup="true" aria-expanded="false"
                                        title="Actions for {{ $user->name }}"
                                        onclick="toggleActionMenu(this)">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>

                                <div class="action-menu" role="menu">
                                    <div class="menu-label">Change role</div>
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}">
                                        @csrf
                                        @foreach ($roleOptions as $value => [$label, $icon, $isCurrent])
                                            <button type="submit" name="role_type" value="{{ $value }}" role="menuitem"
                                                    class="menu-item {{ $isCurrent ? 'is-current' : '' }}"
                                                    @disabled($isCurrent)>
                                                <i class="ti {{ $icon }}"></i> {{ $label }}
                                                @if ($isCurrent) <i class="ti ti-check" style="margin-left:auto;"></i> @endif
                                            </button>
                                        @endforeach
                                    </form>

                                    <div class="menu-sep"></div>

                                    <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}"
                                          onsubmit="return confirm('{{ $user->isActive() ? 'Suspend' : 'Reactivate' }} {{ $user->name }}?')">
                                        @csrf
                                        <button type="submit" role="menuitem"
                                                class="menu-item {{ $user->isActive() ? 'danger' : '' }}">
                                            <i class="ti {{ $user->isActive() ? 'ti-lock' : 'ti-lock-open' }}"></i>
                                            {{ $user->isActive() ? 'Suspend account' : 'Reactivate account' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                          onsubmit="return confirm('Delete {{ $user->name }}\'s account? They lose access immediately, but all their records stay in the system and you can restore the account later.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" role="menuitem" class="menu-item danger">
                                            <i class="ti ti-trash"></i> Delete account
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($users->hasPages())
            <div style="display:flex; justify-content:center; gap:10px; margin-top:24px;">
                @if ($users->previousPageUrl())
                    <a href="{{ $users->previousPageUrl() }}" class="btn btn-ghost">Previous</a>
                @endif
                @if ($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" class="btn btn-ghost">Next</a>
                @endif
            </div>
        @endif
    @endif

    <script>
        function toggleActionMenu(button) {
            const menu = button.nextElementSibling;
            const wasOpen = menu.classList.contains('open');
            closeActionMenus();
            if (!wasOpen) {
                menu.classList.add('open');
                button.setAttribute('aria-expanded', 'true');
                button.closest('.claim-item-card').classList.add('menu-open');
            }
        }

        function closeActionMenus() {
            document.querySelectorAll('.action-menu.open').forEach(m => m.classList.remove('open'));
            document.querySelectorAll('.claim-item-card.menu-open').forEach(c => c.classList.remove('menu-open'));
            document.querySelectorAll('.row-actions .kebab').forEach(b => b.setAttribute('aria-expanded', 'false'));
        }

        // Click anywhere else, or press Escape, to dismiss.
        document.addEventListener('click', e => {
            if (!e.target.closest('.row-actions')) closeActionMenus();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeActionMenus();
        });
    </script>
</x-dashboard-layout>
