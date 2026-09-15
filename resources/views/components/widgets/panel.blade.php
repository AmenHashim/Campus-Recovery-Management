@props([
    'title',
    'icon' => 'ti-chart-bar',
    'linkUrl' => null,      // optional "view all" target
    'linkLabel' => 'View all',
    'wide' => false,        // span both columns of an info-grid
])

<div {{ $attributes->merge(['class' => 'glass-card info-panel widget-panel'.($wide ? ' widget-wide' : '')]) }}>
    <div class="widget-head">
        <span><i class="ti {{ $icon }}"></i> {{ $title }}</span>
        @if ($linkUrl)
            <a href="{{ $linkUrl }}">{{ $linkLabel }} <i class="ti ti-arrow-right"></i></a>
        @endif
    </div>

    {{ $slot }}
</div>

@once
    <style>
        .widget-panel { margin-bottom:0; }
        .widget-wide { grid-column:1 / -1; }
        .widget-head {
            font-size:12px; font-weight:700; color:var(--primary); margin-bottom:14px;
            display:flex; align-items:center; justify-content:space-between; gap:10px;
            border-bottom:1px solid var(--glass-border); padding-bottom:8px;
        }
        .widget-head > span { display:flex; align-items:center; gap:8px; }
        .widget-head a {
            font-size:11px; font-weight:600; color:var(--primary); text-decoration:none;
            display:inline-flex; align-items:center; gap:4px; white-space:nowrap;
        }
        .widget-head a:hover { text-decoration:underline; }

        /* Compact table used by the dashboard list widgets. */
        .widget-table { width:100%; border-collapse:collapse; font-size:12px; }
        .widget-table th {
            text-align:left; font-size:9.5px; font-weight:700; letter-spacing:.6px;
            text-transform:uppercase; color:var(--muted); padding:0 10px 8px 0; white-space:nowrap;
        }
        .widget-table td {
            padding:9px 10px 9px 0; border-top:1px solid var(--glass-border);
            color:var(--text); vertical-align:middle;
        }
        .widget-table td:last-child, .widget-table th:last-child { padding-right:0; text-align:right; }
        .widget-table .t-strong { font-weight:600; }
        .widget-table .t-muted { color:var(--muted); font-size:11px; }
        .widget-table .t-clip { max-width:190px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .widget-table tr.is-urgent td { background:rgba(211,47,47,.05); }

        .widget-empty { font-size:11.5px; color:var(--muted); padding:16px 0; text-align:center; }
        .widget-note { font-size:10.5px; color:var(--muted); margin-top:12px; }
    </style>
@endonce
