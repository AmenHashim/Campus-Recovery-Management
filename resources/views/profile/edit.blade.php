<x-dashboard-layout
    title="My Profile"
    subtitle="View and update your account details"
    active="profile"
>
    @php
        $statusMessages = [
            'profile-updated' => 'Profile updated.',
            'password-updated' => 'Password updated.',
            'verification-link-sent' => 'A new verification link has been sent.',
        ];
    @endphp

    @if (session('status'))
        <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
            <i class="ti ti-circle-check"></i>
            {{ $statusMessages[session('status')] ?? session('status') }}
        </div>
    @endif

    <style>
        .profile-avatar, .profile-avatar-fallback {
            width:88px; height:88px; border-radius:50%; flex-shrink:0;
            border:2px solid var(--glass-border); object-fit:cover;
        }
        .profile-avatar-fallback {
            background:var(--primary); color:#fff; display:flex; align-items:center;
            justify-content:center; font-size:30px; font-weight:700; letter-spacing:1px;
        }
        .profile-head { display:flex; gap:20px; align-items:center; flex-wrap:wrap; }
        .profile-head h2 { font-size:20px; font-weight:800; color:var(--text); margin-bottom:6px; }
        .profile-head .meta { font-size:12px; color:var(--muted); margin-top:8px; display:flex; flex-wrap:wrap; gap:4px 16px; }
        .profile-section h4 {
            font-size:13px; font-weight:700; color:var(--primary); margin-bottom:4px;
            display:flex; align-items:center; gap:8px;
        }
        .profile-section .hint-text { font-size:11px; color:var(--muted); margin-bottom:18px; }
        .avatar-editor { display:flex; align-items:center; gap:16px; margin-bottom:20px; }
        .avatar-editor .preview, .avatar-editor .preview-fallback {
            width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid var(--glass-border);
        }
        .avatar-editor .preview-fallback {
            background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center;
            font-size:24px; font-weight:700;
        }
        .readonly-field {
            padding:10px 12px; background:var(--panel-bg); border:1px solid var(--glass-border);
            border-radius:10px; font-size:13px; color:var(--muted); display:flex; align-items:center; gap:8px;
        }
    </style>

    {{-- ─────────── Header ─────────── --}}
    <div class="glass-card" style="margin-bottom:24px;">
        <div class="profile-head">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="profile-avatar">
            @else
                <div class="profile-avatar-fallback">{{ $user->initials() }}</div>
            @endif
            <div>
                <h2>{{ $user->name }}</h2>
                <span class="badge {{ $user->roleBadgeClass() }}">{{ $user->roleLabel() }}</span>
                <span class="badge {{ $user->isActive() ? 'badge-found' : 'badge-lost' }}">{{ ucfirst($user->status) }}</span>
                <div class="meta">
                    <span><i class="ti ti-id-badge-2"></i> {{ $user->reg_no }}</span>
                    <span><i class="ti ti-mail"></i> {{ $user->email }}</span>
                    <span><i class="ti ti-calendar"></i> Member since {{ $user->created_at->format('M Y') }}</span>
                    @if ($user->isStudentStaff())
                        <span><i class="ti ti-star"></i> {{ $user->reputation_points }} reputation points</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────── Profile information ─────────── --}}
    <div class="glass-card profile-section" style="margin-bottom:24px; max-width:640px;">
        <h4><i class="ti ti-user-edit"></i> Profile Information</h4>
        <p class="hint-text">Update your photo and personal details.</p>

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('patch')

            <div class="avatar-editor">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="" class="preview" id="avatar-preview">
                @else
                    <div class="preview-fallback" id="avatar-preview-fallback">{{ $user->initials() }}</div>
                    <img src="" alt="" class="preview" id="avatar-preview" style="display:none;">
                @endif
                <div>
                    <label for="avatar" class="btn btn-ghost" style="cursor:pointer;">
                        <i class="ti ti-camera"></i> Choose photo
                    </label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                    <p class="hint-text" style="margin:8px 0 0;">JPG or PNG, up to 2 MB.</p>
                    @if ($user->avatarUrl())
                        <label style="font-size:11px;color:#D32F2F;display:flex;align-items:center;gap:6px;margin-top:6px;cursor:pointer;">
                            <input type="checkbox" name="remove_avatar" value="1" style="width:auto;"> Remove current photo
                        </label>
                    @endif
                    @error('avatar') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus>
                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="phone">Phone <span class="hint">(optional)</span></label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+255…">
                    @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Registration / Staff No.</label>
                    <div class="readonly-field"><i class="ti ti-lock"></i> {{ $user->reg_no }}</div>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <div class="readonly-field"><i class="ti ti-lock"></i> {{ $user->roleLabel() }}</div>
                </div>
            </div>
            <p class="hint-text" style="margin-top:-6px;">Your registration number and role are managed by the Lost &amp; Found office.</p>

            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save changes</button>
        </form>
    </div>

    {{-- ─────────── Change password ─────────── --}}
    <div class="glass-card profile-section" style="margin-bottom:24px; max-width:640px;">
        <h4><i class="ti ti-lock-cog"></i> Change Password</h4>
        <p class="hint-text">Use a long, unique password to keep your account secure.</p>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('put')

            <div class="form-group">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                @error('current_password', 'updatePassword') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">New password</label>
                    <input type="password" id="password" name="password" autocomplete="new-password">
                    @error('password', 'updatePassword') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm new password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                    @error('password_confirmation', 'updatePassword') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="ti ti-key"></i> Update password</button>
        </form>
    </div>

    {{-- ─────────── Account closure (admin-managed) ─────────── --}}
    <div class="glass-card profile-section" style="max-width:640px;">
        <h4><i class="ti ti-shield-lock"></i> Closing Your Account</h4>
        <p class="hint-text" style="margin-bottom:0;">
            Your account is an institutional record — it is linked to the items you reported,
            the claims you made, and the office's audit trail. For that reason accounts can only
            be closed by an administrator, and the records behind them are always kept.
            To request closure, contact the Lost &amp; Found Office.
        </p>
    </div>

    <script>
        function previewAvatar(input) {
            if (!input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById('avatar-preview');
                const fallback = document.getElementById('avatar-preview-fallback');
                img.src = e.target.result;
                img.style.display = '';
                if (fallback) fallback.style.display = 'none';
                const remove = document.querySelector('input[name="remove_avatar"]');
                if (remove) remove.checked = false;
            };
            reader.readAsDataURL(input.files[0]);
        }
    </script>
</x-dashboard-layout>
