@props([
    'label',
    'value',
    'icon' => 'ti-point',
    'href' => null,         // makes the card a link
    'hint' => null,         // small line under the value, e.g. "3 waiting over 3 days"
    'tone' => null,         // 'good' | 'warn' | 'bad' — colours the value
])

<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif class="stat-card widget-stat">
    <div class="label"><i class="ti {{ $icon }}"></i> {{ $label }}</div>
    <div class="value {{ $tone ? 'tone-'.$tone : '' }}">{{ $value }}</div>
    @if ($hint)
        <div class="stat-hint">{{ $hint }}</div>
    @endif
</{{ $href ? 'a' : 'div' }}>

@once
    <style>
        .widget-stat { text-decoration:none; }
        .widget-stat .value.tone-good { color:#16a34a; }
        .widget-stat .value.tone-warn { color:#F57F17; }
        .widget-stat .value.tone-bad { color:#D32F2F; }
        .widget-stat .stat-hint { font-size:10.5px; color:var(--muted); margin-top:6px; font-weight:600; }
    </style>
@endonce
