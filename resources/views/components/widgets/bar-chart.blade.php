@props([
    'data' => [],            // ['Label' => count, …]
    'colors' => [],          // ['Label' => 'green'|'red'|'amber'] — defaults to brand blue
    'empty' => 'No data yet.',
    'total' => null,         // show each bar's share of this instead of the peak value
])

@php
    $values = array_values($data);
    $peak = $values ? max($values) : 0;
@endphp

@forelse ($data as $label => $count)
    <div class="wbar-row">
        <span class="wbar-label" title="{{ $label }}">{{ $label }}</span>
        <span class="wbar-track">
            <span class="wbar-fill {{ $colors[$label] ?? '' }}"
                  style="width: {{ $peak > 0 ? round($count / $peak * 100) : 0 }}%;"></span>
        </span>
        <span class="wbar-val">
            {{ number_format($count) }}
            @if ($total)
                <small>{{ $total > 0 ? round($count / $total * 100) : 0 }}%</small>
            @endif
        </span>
    </div>
@empty
    <div class="widget-empty">{{ $empty }}</div>
@endforelse

@once
    <style>
        .wbar-row { display:flex; align-items:center; gap:12px; padding:7px 0; font-size:12px; }
        .wbar-label {
            width:118px; flex-shrink:0; color:var(--muted); font-weight:600;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .wbar-track { flex:1; background:var(--panel-bg); border-radius:8px; height:10px; overflow:hidden; }
        .wbar-fill { display:block; height:100%; border-radius:8px; background:var(--primary); min-width:2px; }
        .wbar-fill.green { background:#16a34a; }
        .wbar-fill.red { background:#D32F2F; }
        .wbar-fill.amber { background:#F9A825; }
        .wbar-fill.grey { background:#94a3b8; }
        .wbar-val { min-width:62px; text-align:right; font-weight:700; color:var(--text); flex-shrink:0; }
        .wbar-val small { font-weight:600; color:var(--muted); font-size:10px; margin-left:4px; }
    </style>
@endonce
