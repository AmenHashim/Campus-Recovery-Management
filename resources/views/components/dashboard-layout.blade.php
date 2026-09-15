@props(['title' => null, 'subtitle' => null, 'active' => null])

@php
    $user = auth()->user();

    if ($user->isStudentStaff()) {
        $menu = [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ti-layout-dashboard', 'route' => 'student.dashboard'],
            ['key' => 'report', 'label' => 'Report an Item', 'icon' => 'ti-flag', 'route' => 'student.items.create'],
            ['key' => 'browse', 'label' => 'Browse & Search', 'icon' => 'ti-search', 'route' => 'student.items.index'],
            ['key' => 'my-reports', 'label' => 'My Reports', 'icon' => 'ti-clipboard-list', 'route' => 'student.items.mine'],
            ['key' => 'claims', 'label' => 'My Claims', 'icon' => 'ti-ticket', 'route' => 'student.claims.index'],
            ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'ti-bell', 'route' => 'student.notifications.index'],
        ];
    } elseif ($user->isOfficer()) {
        $menu = [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ti-layout-dashboard', 'route' => 'officer.dashboard'],
            ['key' => 'guest-reports', 'label' => 'File Guest Report', 'icon' => 'ti-user-plus', 'route' => 'officer.guest-reports.create'],
            ['key' => 'intake', 'label' => 'Item Intake', 'icon' => 'ti-box', 'route' => 'officer.intake.index'],
            ['key' => 'claims', 'label' => 'Verify Claims', 'icon' => 'ti-shield-check', 'route' => 'officer.claims.index'],
            ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'ti-bell', 'route' => 'officer.notifications.index'],
        ];
    } else {
        $menu = [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ti-layout-dashboard', 'route' => 'admin.dashboard'],
            ['key' => 'users', 'label' => 'User Management', 'icon' => 'ti-users', 'route' => 'admin.users.index'],
            ['key' => 'reference', 'label' => 'Categories & Locations', 'icon' => 'ti-category', 'route' => 'admin.reference.index'],
            ['key' => 'analytics', 'label' => 'Analytics', 'icon' => 'ti-chart-bar', 'route' => 'admin.analytics.index'],
            ['key' => 'audit', 'label' => 'Audit Logs', 'icon' => 'ti-history', 'route' => 'admin.audit.index'],
        ];
    }
@endphp
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

<div class="app-wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('home') }}" class="brand">
                <img src="{{ asset('images/logo.png') }}" alt="University Recovery System" onerror="this.style.display='none'">
                <span>CPRMS</span>
            </a>
            <button class="toggle-sidebar-btn" onclick="toggleSidebarCollapse()" aria-label="Collapse sidebar">
                <i class="ti ti-chevron-left" id="collapseIcon"></i>
            </button>
        </div>

        <ul class="sidebar-menu">
            @foreach ($menu as $item)
                <li class="menu-item {{ $active === $item['key'] ? 'active' : '' }}">
                    @if (! empty($item['route']))
                        <a href="{{ route($item['route']) }}">
                            <i class="ti {{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @else
                        <a href="#" onclick="return false;" style="opacity:.55;cursor:not-allowed;">
                            <i class="ti {{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                            <span class="nav-badge">Soon</span>
                        </a>
                    @endif
                </li>
            @endforeach

            <li class="menu-item {{ $active === 'profile' ? 'active' : '' }}">
                <a href="{{ route('profile.edit') }}">
                    <i class="ti ti-user-circle"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="menu-item">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">
                        <i class="ti ti-logout"></i>
                        <span>Log out</span>
                    </button>
                </form>
            </li>
        </ul>
    </aside>

    <div class="mobile-sidebar-overlay" id="sidebarOverlay" onclick="closeMobileSidebar()"></div>

    <div class="main-area">
        <header class="main-header">
            <div class="header-left">
                <button class="mobile-menu-btn" onclick="openMobileSidebar()" aria-label="Open menu">
                    <i class="ti ti-menu-2"></i>
                </button>
                <div class="header-text-group">
                    <h1>{{ $title }}</h1>
                    @if ($subtitle)
                        <p>{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            <div class="header-right">
                <a href="{{ route('profile.edit') }}" class="user-badge" style="text-decoration:none;" title="My profile">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="" style="width:22px;height:22px;border-radius:50%;object-fit:cover;margin:-2px 0;">
                    @else
                        <i class="ti ti-user"></i>
                    @endif
                    <span>{{ $user->name }}</span>
                </a>
                <button class="theme-btn" onclick="toggleTheme()" aria-label="Toggle theme">
                    <i class="ti ti-moon" id="themeIcon"></i>
                </button>
            </div>
        </header>

        <main class="content-body">
            {{ $slot }}
        </main>
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

    (function () {
        const saved = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', saved);
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('themeIcon').className = saved === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
        });
    })();

    function toggleSidebarCollapse() {
        const sidebar = document.getElementById('sidebar');
        const collapsed = sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
        document.getElementById('collapseIcon').className = collapsed ? 'ti ti-chevron-right' : 'ti ti-chevron-left';
    }

    (function () {
        if (localStorage.getItem('sidebarCollapsed') === '1') {
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('collapseIcon').className = 'ti ti-chevron-right';
            });
        }
    })();

    function openMobileSidebar() {
        document.getElementById('sidebar').classList.add('open');
        document.body.classList.add('mobile-sidebar-active');
    }

    function closeMobileSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.body.classList.remove('mobile-sidebar-active');
    }
</script>
</body>
</html>
