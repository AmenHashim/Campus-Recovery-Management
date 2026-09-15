@props([
    'series' => [],          // ['Mon' => ['found' => 3, 'lost' => 1], …] — one column per key
    'labels' => [],          // ['found' => ['Found', 'var(--secondary)'], 'lost' => ['Lost', '#D32F2F']]
    'height' => 150,
    'empty' => 'No data yet.',
])

@php
    $peak = 0;
    foreach ($series as $columns) {
        foreach ($columns as $value) {
            $peak = max($peak, $value);
        }
    }
@endphp

@if (empty($series))
    <div class="widget-empty">{{ $empty }}</div>
@else
    <div class="wcol-chart" style="height:{{ $height }}px;">
        @foreach ($series as $column => $values)
            <div class="wcol">
                <div class="wcol-bars">
                    @foreach ($values as $key => $value)
                        <span class="wcol-bar"
                              style="height:{{ $peak > 0 ? round($value / $peak * 100) : 0 }}%;
                                     background:{{ $labels[$key][1] ?? 'var(--primary)' }};"
                              title="{{ $labels[$key][0] ?? ucfirst($key) }}: {{ $value }}"></span>
                    @endforeach
                </div>
                <span class="wcol-label">{{ $column }}</span>
            </div>
        @endforeach
    </div>

    <div class="wcol-legend">
        @foreach ($labels as [$label, $color])
            <span><i class="wcol-dot" style="background:{{ $color }};"></i> {{ $label }}</span>
        @endforeach
    </div>
@endif

@once
    <style>
        .wcol-chart { display:flex; align-items:flex-end; gap:10px; padding-top:8px; }
        .wcol { flex:1; display:flex; flex-direction:column; align-items:center; gap:8px; height:100%; }
        .wcol-bars { display:flex; align-items:flex-end; justify-content:center; gap:4px; height:100%; width:100%; }
        .wcol-bar { width:15px; border-radius:5px 5px 0 0; min-height:2px; }
        .wcol-label { font-size:10px; color:var(--muted); font-weight:700; text-transform:uppercase; }
        .wcol-legend { display:flex; gap:18px; margin-top:14px; font-size:11px; color:var(--muted); font-weight:600; }
        .wcol-legend span { display:inline-flex; align-items:center; gap:6px; }
        .wcol-dot { width:10px; height:10px; border-radius:3px; display:inline-block; }
    </style>
@endonce
