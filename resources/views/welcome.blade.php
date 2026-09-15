<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CPRMS — University Recovery System Campus Lost &amp; Found</title>
  <meta name="description" content="Report, match, and recover lost property with University Recovery System.">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/customized.css') }}">
</head>
<body>

<nav class="nav">
  <div class="nav-inner">
    <a href="{{ route('home') }}" class="brand">
      <img src="{{ asset('assets/images/MwangaTech.png') }}" alt="University Recovery System">
      <span class="brand-text">
        <strong>CPRMS</strong>
        <span>University Recovery System</span>
      </span>
    </a>
    <div class="nav-actions">
      <button class="theme-btn" onclick="toggleTheme()" aria-label="Toggle theme">
        <i class="ti ti-moon" id="themeIcon"></i>
      </button>

      @auth
        {{-- Already logged in — send them to their own portal (FR-A4) --}}
        <a href="{{ route(auth()->user()->homeRoute()) }}" class="btn btn-primary">
          <i class="ti ti-layout-dashboard"></i> My Dashboard
        </a>
      @else
        <a href="{{ route('login') }}" class="btn btn-ghost">Log in</a>
        <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
      @endauth
    </div>
  </div>
</nav>

<div class="wrap">

  {{-- ───────── HERO ───────── --}}
  <header class="hero">
    <span class="pill"><i class="ti ti-shield-check"></i> Official University Recovery System Lost &amp; Found Service</span>
    <h1>Lost something on campus?<br>Let's get it <em>back to you</em>.</h1>
    <p class="lede">
      CPRMS connects lost reports with found items automatically so recovering your
      property doesn't depend on luck, noticeboards, or knocking on the right door.
    </p>

    <div class="hero-cta">
      @auth
        <a href="{{ route(auth()->user()->homeRoute()) }}" class="btn btn-primary btn-lg">
          <i class="ti ti-layout-dashboard"></i> Go to My Dashboard
        </a>
      @else
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
          <i class="ti ti-user-plus"></i> Create an Account
        </a>
        <a href="{{ route('login') }}" class="btn btn-ghost btn-lg">
          <i class="ti ti-login"></i> I already have one
        </a>
      @endauth
    </div>

    {{-- Guests have no account by design (BR-02) — tell them what to do instead --}}
    <p class="hero-note">
      Not a student or staff member? Visit the Lost &amp; Found Office and they'll file a report for you.
    </p>

    <div class="strip">
      <div class="strip-item"><div class="k">1</div><div class="v">Central campus record</div></div>
      <div class="strip-item"><div class="k">Auto</div><div class="v">Smart item matching</div></div>
      <div class="strip-item"><div class="k">ID</div><div class="v">Verified handover</div></div>
      <div class="strip-item"><div class="k">100%</div><div class="v">Audited actions</div></div>
    </div>
  </header>

  {{-- ───────── HOW IT WORKS ───────── --}}
  <section>
    <div class="sec-head">
      <h2>How it works</h2>
      <p>Four steps from the moment something goes missing to the moment it's back in your hands.</p>
    </div>
    
    <div class="steps">
      <div class="step">
        <div class="step-num">1</div>
        <h3>Report it</h3>
        <p>File a lost or found report in under a minute — category, location, date, photo. Found something? Report that too.</p>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <h3>We match it</h3>
        <p>Our matching engine scores every new report against the pool and alerts you when something looks like yours.</p>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <h3>Claim it</h3>
        <p>Recognise your item? Submit a claim and receive a reference token — on screen, by email, no app needed.</p>
      </div>
      <div class="step">
        <div class="step-num">4</div>
        <h3>Collect it</h3>
        <p>Bring your student, staff, or national ID to the Lost &amp; Found Office. An officer verifies you, and it's yours.</p>
      </div>
    </div>
    
  </section>

  {{-- ───────── FEATURES ───────── --}}
  <section>
    <div class="sec-head">
      <h2>Built for a campus, not a warehouse</h2>
      <p>Every feature exists to solve a problem the paper logbook couldn't.</p>
    </div>
    <div class="features">
      <div class="feature">
        <i class="ti ti-target-arrow"></i>
        <h3>Confidence-scored matching</h3>
        <p>Category, location, date, and description are weighed together to rank likely matches — no manual searching.</p>
      </div>
      <div class="feature">
        <i class="ti ti-search"></i>
        <h3>Search the whole pool</h3>
        <p>Browse and filter every item currently held by the Office, any time, from anywhere.</p>
      </div>
      <div class="feature">
        <i class="ti ti-id-badge-2"></i>
        <h3>ID-verified handover</h3>
        <p>Nothing is released without an officer checking physical ID. Lost your phone? Your claim still works.</p>
      </div>
      <div class="feature">
        <i class="ti ti-bell-ringing"></i>
        <h3>You get notified</h3>
        <p>No more checking back every week. If a match appears or your claim moves, you hear about it.</p>
      </div>
      <div class="feature">
        <i class="ti ti-award"></i>
        <h3>Honest finders rewarded</h3>
        <p>Hand something in and earn reputation points when it's verified back to its owner.</p>
      </div>
      <div class="feature">
        <i class="ti ti-shield-lock"></i>
        <h3>Fully audited</h3>
        <p>Every report, claim, and approval is logged with who did what and when. Disputes have a paper trail.</p>
      </div>
    </div>
  </section>

  {{-- ───────── ROLES ───────── --}}
  <section>
    <div class="sec-head">
      <h2>Who uses CPRMS</h2>
      <p>Different people, different needs — one system.</p>
    </div>
    <div class="roles">
      <div class="role">
        <span class="role-badge">Students &amp; Staff</span>
        <h3>Report, search, claim</h3>
        <ul>
          <li>File lost &amp; found reports</li>
          <li>Get matched automatically</li>
          <li>Track your claims</li>
          <li>Build finder reputation</li>
        </ul>
      </div>
      <div class="role">
        <span class="role-badge">Campus Guests</span>
        <h3>No account needed</h3>
        <ul>
          <li>Visit the Lost &amp; Found Office</li>
          <li>An officer files it for you</li>
          <li>Reached by phone or email</li>
          <li>Same ID-verified handover</li>
        </ul>
      </div>
      <div class="role">
        <span class="role-badge">Lost &amp; Found Office</span>
        <h3>Verify and release</h3>
        <ul>
          <li>Manage item intake</li>
          <li>File reports for guests</li>
          <li>Verify claimant identity</li>
          <li>Approve or reject claims</li>
        </ul>
      </div>
    </div>
  </section>

  {{-- ───────── CTA ───────── --}}
  @guest
  <section>
    <div class="cta-band">
      <h2>Ready to get your property back?</h2>
      <p>Register with your University Recovery System registration or staff number. It takes a minute.</p>
      <div class="hero-cta">
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Create an Account</a>
        <a href="{{ route('login') }}" class="btn btn-ghost btn-lg">Log in</a>
      </div>
    </div>
  </section>
  @endguest

  <footer>
    <p>
      Campus Property Recovery Management System
      <span class="sep">&middot;</span>
      University Recovery System, Dar es Salaam
      <span class="sep">&middot;</span>
      &copy; {{ date('Y') }}
    </p>
  </footer>
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

  // Respect the theme chosen inside the app — one preference across both
  (function () {
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('themeIcon').className = saved === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
    });
  })();
</script>
</body>
</html>