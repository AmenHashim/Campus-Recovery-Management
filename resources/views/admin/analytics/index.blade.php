<x-dashboard-layout
    title="Analytics"
    subtitle="Recovery performance and claim trends across the system"
    active="analytics"
>
    @php
        // Peak values used to scale each bar chart to its own maximum.
        $catMax = max(array_values($byCategory) ?: [1]);
        $locMax = max(array_values($byLocation) ?: [1]);
        $foundMax = max(array_values($foundByStatus) ?: [1]);
        $claimMax = max(array_values($claimOutcomes) ?: [1]);
        $trendMax = max(array_map(fn ($m) => max($m['lost'], $m['found']), $trend) ?: [1]);
    @endphp

    <style>
        .analytics .bar-row { display:flex; align-items:center; gap:12px; padding:7px 0; font-size:12px; }
        .analytics .bar-label { width:120px; flex-shrink:0; color:var(--muted); font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .analytics .bar-track { flex:1; background:var(--panel-bg); border-radius:8px; height:10px; overflow:hidden; }
        .analytics .bar-fill { height:100%; border-radius:8px; background:var(--primary); min-width:2px; transition:width .3s; }
        .analytics .bar-fill.green { background:#16a34a; }
        .analytics .bar-fill.red { background:#D32F2F; }
        .analytics .bar-fill.amber { background:#F9A825; }
        .analytics .bar-val { width:46px; text-align:right; font-weight:700; color:var(--text); flex-shrink:0; }

        .analytics .trend { display:flex; align-items:flex-end; gap:14px; height:170px; padding:10px 0 0; }
        .analytics .trend-col { flex:1; display:flex; flex-direction:column; align-items:center; gap:8px; height:100%; }
        .analytics .trend-bars { display:flex; align-items:flex-end; justify-content:center; gap:4px; height:100%; width:100%; }
        .analytics .trend-bar { width:16px; border-radius:5px 5px 0 0; min-height:2px; }
        .analytics .trend-bar.found { background:var(--primary); }
        .analytics .trend-bar.lost { background:#D32F2F; }
        .analytics .trend-month { font-size:10px; color:var(--muted); font-weight:700; text-transform:uppercase; }
        .analytics .legend { display:flex; gap:18px; margin-top:14px; font-size:11px; color:var(--muted); font-weight:600; }
        .analytics .legend span { display:inline-flex; align-items:center; gap:6px; }
        .analytics .dot { width:10px; height:10px; border-radius:3px; display:inline-block; }
    </style>

    <div class="analytics">
        {{-- ── KPI cards ── --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label"><i class="ti ti-flag"></i> Total Reports</div>
                <div class="value">{{ number_format($totalItems) }}</div>
            </div>
            <div class="stat-card">
                <div class="label"><i class="ti ti-package-import"></i> Recovery Rate</div>
                <div class="value">{{ $recoveryRate }}%</div>
            </div>
            <div class="stat-card">
                <div class="label"><i class="ti ti-ticket"></i> Total Claims</div>
                <div class="value">{{ number_format($totalClaims) }}</div>
            </div>
            <div class="stat-card">
                <div class="label"><i class="ti ti-shield-check"></i> Claim Approval Rate</div>
                <div class="value">{{ $approvalRate }}%</div>
            </div>
        </div>

        {{-- ── Found-item recovery + Claim outcomes ── --}}
        <div class="info-grid">
            <div class="glass-card info-panel">
                <h4><i class="ti ti-package"></i> Found-item recovery ({{ number_format($foundCount) }} found)</h4>
                @foreach ($foundByStatus as $label => $count)
                    <div class="bar-row">
                        <span class="bar-label">{{ $label }}</span>
                        <span class="bar-track">
                            <span class="bar-fill {{ $label === 'Returned' ? 'green' : '' }}"
                                  style="width: {{ $foundMax > 0 ? round($count / $foundMax * 100) : 0 }}%;"></span>
                        </span>
                        <span class="bar-val">{{ number_format($count) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="glass-card info-panel">
                <h4><i class="ti ti-clipboard-check"></i> Claim outcomes ({{ number_format($totalClaims) }} total)</h4>
                @foreach ($claimOutcomes as $label => $count)
                    <div class="bar-row">
                        <span class="bar-label">{{ $label }}</span>
                        <span class="bar-track">
                            <span class="bar-fill {{ ['Pending' => 'amber', 'Verified' => 'green', 'Rejected' => 'red'][$label] }}"
                                  style="width: {{ $claimMax > 0 ? round($count / $claimMax * 100) : 0 }}%;"></span>
                        </span>
                        <span class="bar-val">{{ number_format($count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── Category + Location breakdowns ── --}}
        <div class="info-grid">
            <div class="glass-card info-panel">
                <h4><i class="ti ti-category"></i> Reports by category</h4>
                @forelse ($byCategory as $category => $count)
                    <div class="bar-row">
                        <span class="bar-label">{{ $category }}</span>
                        <span class="bar-track">
                            <span class="bar-fill" style="width: {{ round($count / $catMax * 100) }}%;"></span>
                        </span>
                        <span class="bar-val">{{ number_format($count) }}</span>
                    </div>
                @empty
                    <div class="bar-row"><span class="bar-label">No data yet</span></div>
                @endforelse
            </div>

            <div class="glass-card info-panel">
                <h4><i class="ti ti-map-pin"></i> Reports by location</h4>
                @forelse ($byLocation as $location => $count)
                    <div class="bar-row">
                        <span class="bar-label">{{ $location }}</span>
                        <span class="bar-track">
                            <span class="bar-fill" style="width: {{ round($count / $locMax * 100) }}%;"></span>
                        </span>
                        <span class="bar-val">{{ number_format($count) }}</span>
                    </div>
                @empty
                    <div class="bar-row"><span class="bar-label">No data yet</span></div>
                @endforelse
            </div>
        </div>

        {{-- ── Monthly trend ── --}}
        <div class="glass-card info-panel" style="margin-bottom:24px;">
            <h4><i class="ti ti-chart-bar"></i> Reports per month (last 6 months)</h4>
            <div class="trend">
                @foreach ($trend as $month => $counts)
                    <div class="trend-col">
                        <div class="trend-bars">
                            <span class="trend-bar found" title="Found: {{ $counts['found'] }}"
                                  style="height: {{ $trendMax > 0 ? round($counts['found'] / $trendMax * 100) : 0 }}%;"></span>
                            <span class="trend-bar lost" title="Lost: {{ $counts['lost'] }}"
                                  style="height: {{ $trendMax > 0 ? round($counts['lost'] / $trendMax * 100) : 0 }}%;"></span>
                        </div>
                        <span class="trend-month">{{ \Illuminate\Support\Carbon::parse($month.'-01')->format('M') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="legend">
                <span><i class="dot" style="background:var(--secondary);"></i> Found</span>
                <span><i class="dot" style="background:#D32F2F;"></i> Lost</span>
            </div>
        </div>

        {{-- ── Matching engine ── --}}
        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
            <div class="stat-card">
                <div class="label"><i class="ti ti-target-arrow"></i> Matches Generated</div>
                <div class="value">{{ number_format($totalMatches) }}</div>
            </div>
            <div class="stat-card">
                <div class="label"><i class="ti ti-percentage"></i> Avg. Match Confidence</div>
                <div class="value">{{ $avgConfidence }}%</div>
            </div>
            <div class="stat-card">
                <div class="label"><i class="ti ti-flame"></i> High-confidence (≥75%)</div>
                <div class="value">{{ number_format($highConfidence) }}</div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
