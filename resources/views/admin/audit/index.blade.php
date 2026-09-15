<x-dashboard-layout
    title="Audit Log"
    subtitle="Immutable record of significant actions across the system"
    active="audit"
>
    <style>
        .audit .search-container { margin-bottom:20px; }
        .audit .log-row { display:flex; align-items:flex-start; gap:14px; padding:14px 0; border-bottom:1px solid var(--glass-border); }
        .audit .log-row:last-child { border-bottom:none; }
        .audit .log-icon { width:36px; height:36px; border-radius:10px; background:var(--panel-bg); color:var(--primary);
            display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
        .audit .log-body { flex:1; min-width:0; }
        .audit .log-desc { font-size:13px; color:var(--text); font-weight:500; }
        .audit .log-meta { font-size:11px; color:var(--muted); margin-top:3px; display:flex; flex-wrap:wrap; gap:4px 12px; }
        .audit .log-meta code { font-size:10px; }
        .audit .action-tag { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
            padding:2px 8px; border-radius:20px; background:rgba(23,88,131,0.1); color:var(--primary); white-space:nowrap; }
    </style>

    <div class="audit">
        <div class="search-container">
            <x-search-bar
                :action="route('admin.audit.index')"
                :suggest="route('admin.audit.suggest')"
                :hidden="['group' => request('group')]"
                placeholder="Search actions or descriptions..."
            />

            <div class="category-chips">
                <a href="{{ route('admin.audit.index', array_filter(['q' => request('q')])) }}"
                   class="chip {{ ! request('group') ? 'active' : '' }}">
                    <i class="ti ti-list"></i> All
                </a>
                @foreach ($groups as $group)
                    <a href="{{ route('admin.audit.index', array_filter(['q' => request('q'), 'group' => $group])) }}"
                       class="chip {{ request('group') === $group ? 'active' : '' }}">
                        {{ ucfirst($group) }}
                    </a>
                @endforeach
            </div>
        </div>

        @if ($logs->isEmpty())
            <div class="empty-state">
                <i class="ti ti-history-off"></i>
                @if (request('q') || request('group'))
                    No audit entries match your filter.
                @else
                    No audit entries yet.
                @endif
            </div>
        @else
            <div class="glass-card">
                @foreach ($logs as $log)
                    <div class="log-row">
                        <span class="log-icon"><i class="ti {{ $log->icon() }}"></i></span>
                        <div class="log-body">
                            <div class="log-desc">{{ $log->description }}</div>
                            <div class="log-meta">
                                <span><i class="ti ti-user"></i> {{ $log->actorName() }}</span>
                                <span class="action-tag">{{ $log->action }}</span>
                                <span>{{ $log->created_at?->diffForHumans() }}</span>
                                @if ($log->ip_address)
                                    <span><code>{{ $log->ip_address }}</code></span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; font-size:12px; color:var(--muted);">
                <span>Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}</span>
                <span style="display:flex; gap:10px;">
                    @if ($logs->previousPageUrl())
                        <a href="{{ $logs->previousPageUrl() }}" class="btn btn-ghost">Previous</a>
                    @endif
                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="btn btn-ghost">Next</a>
                    @endif
                </span>
            </div>
        @endif
    </div>
</x-dashboard-layout>
