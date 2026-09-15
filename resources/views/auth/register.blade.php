<x-auth-layout title="Create your account" subtitle="Registration is for University Recovery System students and staff." :wide="true">

    @if ($errors->any())
        <div class="auth-alert auth-alert-error"><i class="ti ti-alert-circle"></i> Please fix the errors below and try again.</div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Registration is for Students and Staff only.
             Officer / Super Admin accounts are provisioned, never self-registered (FR-A5, BR-07). --}}

        <!-- Name -->
        <div class="field">
            <label for="name">Full Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   required autofocus autocomplete="name">
            @error('name')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div class="field-row">
            <!-- I am a (user_type — University identity, not a permission) -->
            <div class="field">
                <label for="user_type">I am a</label>
                <select id="user_type" name="user_type" required>
                    <option value="">&mdash; Select &mdash;</option>
                    <option value="student" @selected(old('user_type') === 'student')>Student</option>
                    <option value="staff" @selected(old('user_type') === 'staff')>Staff</option>
                </select>
                @error('user_type')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <!-- Registration / Staff Number -->
            <div class="field">
                <label for="reg_no">Registration / Staff Number</label>
                <input id="reg_no" type="text" name="reg_no" value="{{ old('reg_no') }}"
                       required placeholder="e.g. UNI/BCOM/2024/042">
                @error('reg_no')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="field-row">
            <!-- Email Address -->
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       required autocomplete="username">
                @error('email')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <!-- Phone (optional) -->
            <div class="field">
                <label for="phone">Phone <span class="hint">(optional)</span></label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                       placeholder="+255 7XX XXX XXX">
                @error('phone')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <!-- Password -->
        <div class="field field-pw">
            <label for="password">Password</label>
            <input id="password" type="password" name="password"
                   required autocomplete="new-password">
            <button type="button" class="pw-toggle" aria-label="Show password"><i class="ti ti-eye"></i></button>
            @error('password')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <!-- Confirm Password -->
        <div class="field field-pw">
            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   required autocomplete="new-password">
            <button type="button" class="pw-toggle" aria-label="Show password"><i class="ti ti-eye"></i></button>
            @error('password_confirmation')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div class="auth-guest-note">
            <i class="ti ti-info-circle"></i>
            <span>Lost &amp; Found Officer and Admin accounts are provisioned by University Recovery System administration — they can't be created through this form.</span>
        </div>

        <button type="submit" class="auth-submit"><i class="ti ti-user-plus"></i> Create account</button>
    </form>

    <p class="auth-foot">
        Already registered? <a class="auth-link" href="{{ route('login') }}">Log in</a>
    </p>
</x-auth-layout>
