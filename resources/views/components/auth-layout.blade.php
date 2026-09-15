@props(['title' => null, 'subtitle' => null, 'wide' => false])
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ? $title.' — CPRMS' : 'CPRMS' }}</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/customized.css') }}">
</head>
<body>

<div class="auth-page">
  <div class="auth-top">
    <a href="{{ route('home') }}" class="auth-back"><i class="ti ti-arrow-left"></i> Back to home</a>
    <button class="theme-btn" onclick="toggleTheme()" aria-label="Toggle theme">
      <i class="ti ti-moon" id="themeIcon"></i>
    </button>
  </div>

  <div class="auth-card{{ $wide ? ' wide' : '' }}">
    <a href="{{ route('home') }}" class="auth-brand">
      <img src="{{ asset('images/logo.png') }}" alt="University Recovery System" onerror="this.style.display='none'">
      <strong>CPRMS</strong>
      <span>University Recovery System</span>
    </a>

    @if ($title)
      <div class="auth-head">
        <h1>{{ $title }}</h1>
        @if ($subtitle)
          <p>{{ $subtitle }}</p>
        @endif
      </div>
    @endif

    {{ $slot }}
  </div>
</div>

<script>
  function toggleTheme() {
    const html = document.documentElement;
    const dark = html.getAttribute('data-theme') === 'dark';
    const next = dark ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    document.getElementById('themeIcon').className = dark ? 'ti ti-moon' : 'ti ti-sun';
  }

  // Respect the theme chosen elsewhere in the app — one preference across both
  (function () {
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('themeIcon').className = saved === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
    });
  })();

  // Show/hide password — delegated so it works for every .field-pw on the page
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.pw-toggle');
    if (!btn) return;
    const input = btn.previousElementSibling;
    const icon = btn.querySelector('i');
    const showing = input.type === 'password';
    input.type = showing ? 'text' : 'password';
    icon.className = showing ? 'ti ti-eye-off' : 'ti ti-eye';
  });
</script>
</body>
</html>
