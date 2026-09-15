<x-auth-layout title="Welcome back" subtitle="Log in to access your CPRMS dashboard.">

    @if (session('status'))
        <div class="auth-alert auth-alert-success"><i class="ti ti-check"></i> {{ session('status') }}</div>
    @endif

    @error('login')
        <div class="auth-alert auth-alert-error"><i class="ti ti-alert-circle"></i> {{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email OR Registration/Staff Number (FR-A2) -->
        <div class="field">
            <label for="login">Email or Registration/Staff Number</label>
            <input id="login" type="text" name="login" value="{{ old('login') }}"
                   required autofocus autocomplete="username"
                   placeholder="you@university.ac.tz or UNI/BCOM/2024/042">
        </div>

        <!-- Password -->
        <div class="field field-pw">
            <label for="password">Password</label>
            <input id="password" type="password" name="password"
                   required autocomplete="current-password" placeholder="••••••••">
            <button type="button" class="pw-toggle" aria-label="Show password"><i class="ti ti-eye"></i></button>
            @error('password')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div class="auth-meta">
            <label class="checkbox">
                <input type="checkbox" name="remember">
                <span>Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}">Forgot your password?</a>
            @endif
        </div>

        <button type="submit" class="auth-submit"><i class="ti ti-login"></i> Log in</button>
    </form>

    <p class="auth-foot">
        Don't have an account? <a class="auth-link" href="{{ route('register') }}">Register</a>
    </p>
</x-auth-layout>
