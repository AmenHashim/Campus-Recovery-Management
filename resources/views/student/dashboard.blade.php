<x-dashboard-layout
    title="Student / Staff Dashboard"
    subtitle="{{ auth()->user()->roleLabel() }} · {{ auth()->user()->reg_no }}"
    active="dashboard"
>
    <style>
        .dash-actions { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:24px; }
    </style>

    {{-- ── My numbers ── --}}
    <div class="stats-grid">
        <x-widgets.stat
            label="Reputation Points" icon="ti-star"
            :value="auth()->user()->reputation_points"
            hint="+10 each time a find you reported is returned"
        />
        <x-widgets.stat
            label="Items Reported" icon="ti-flag"
            :value="$itemsReported"
            :href="route('student.items.mine')"
            hint="Lost and found reports you've filed"
        />
        <x-widgets.stat
            label="Active Claims" icon="ti-ticket"
            :value="$activeClaims"
            :tone="$pendingClaims > 0 ? 'warn' : null"
            :hint="$pendingClaims > 0 ? $pendingClaims.' awaiting verification' : 'Nothing pending'"
            :href="route('student.claims.index')"
        />
        <x-widgets.stat
            label="Items Recovered" icon="ti-package-import"
            :value="$itemsRecovered" tone="good"
            hint="Back with their owners"
            :href="route('student.notifications.index')"
        />
    </div>

    <div class="dash-actions">
        <a href="{{ route('student.items.create') }}" class="btn btn-primary">
            <i class="ti ti-flag"></i> Report an item
        </a>
        <a href="{{ route('student.items.index') }}" class="btn btn-ghost">
            <i class="ti ti-search"></i> Browse found items
            @if ($newFoundItems > 0)
                <span class="badge badge-found" style="margin-left:6px;">{{ $newFoundItems }} new this week</span>
            @endif
        </a>
        @if ($unreadNotifications > 0)
            <a href="{{ route('student.notifications.index') }}" class="btn btn-ghost">
                <i class="ti ti-bell"></i> {{ $unreadNotifications }} unread
            </a>
        @endif
    </div>

    {{-- ── My reports + my claims ── --}}
    <div class="info-grid">
        <x-widgets.panel
            title="My latest reports"
            icon="ti-clipboard-list"
            :link-url="route('student.items.mine')"
        >
            @if ($recentItems->isEmpty())
                <div class="widget-empty">
                    Nothing reported yet. Lost or found something? Report it — matching starts right away.
                </div>
            @else
                <table class="widget-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentItems as $item)
                            <tr>
                                <td>
                                    <span class="t-strong t-clip" style="display:block;">{{ $item->name }}</span>
                                    <span class="t-muted">{{ $item->location }} · {{ $item->date->format('M j') }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $item->isLost() ? 'badge-lost' : 'badge-found' }}">{{ ucfirst($item->type) }}</span>
                                </td>
                                <td><span class="badge {{ $item->statusBadgeClass() }}">{{ ucfirst($item->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-widgets.panel>

        <x-widgets.panel
            title="My latest claims"
            icon="ti-ticket"
            :link-url="route('student.claims.index')"
        >
            @if ($recentClaims->isEmpty())
                <div class="widget-empty">
                    No claims yet. Spot your item in the found pool, claim it, then bring the token to the office.
                </div>
            @else
                <table class="widget-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Token</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentClaims as $claim)
                            <tr>
                                <td>
                                    <span class="t-strong t-clip" style="display:block;">{{ $claim->item->name }}</span>
                                    <span class="t-muted">{{ $claim->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="t-muted">{{ $claim->token }}</td>
                                <td><span class="badge {{ $claim->statusBadgeClass() }}">{{ ucfirst($claim->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-widgets.panel>
    </div>

    {{-- ── Personal breakdowns ── --}}
    <div class="info-grid">
        <x-widgets.panel title="My reports by status" icon="ti-timeline">
            <x-widgets.bar-chart
                :data="$myReportsByStatus"
                :total="$itemsReported"
                :colors="['Returned' => 'green', 'Closed' => 'grey', 'Claimed' => 'amber']"
                empty="Once you report something, its progress shows up here."
            />
        </x-widgets.panel>

        <x-widgets.panel title="My claim outcomes" icon="ti-clipboard-check">
            <x-widgets.bar-chart
                :data="$totalMyClaims > 0 ? $myClaimOutcomes : []"
                :total="$totalMyClaims"
                :colors="['Pending' => 'amber', 'Verified' => 'green', 'Rejected' => 'red']"
                empty="You haven't claimed anything yet."
            />
        </x-widgets.panel>
    </div>

    {{-- ── Found pool ── --}}
    <div class="info-grid" style="grid-template-columns:1fr;">
        <x-widgets.panel
            title="Recently handed in"
            icon="ti-box"
            :link-url="route('student.items.index')"
            link-label="Browse all"
        >
            @if ($latestFound->isEmpty())
                <div class="widget-empty">No found items are waiting to be claimed right now.</div>
            @else
                <table class="widget-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Found at</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($latestFound as $item)
                            <tr>
                                <td class="t-strong t-clip">{{ $item->name }}</td>
                                <td class="t-muted">{{ $item->category }}</td>
                                <td class="t-muted t-clip">{{ $item->location }}</td>
                                <td class="t-muted">{{ $item->date->format('M j') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="widget-note">See one of yours? Open Browse &amp; Search to claim it — an officer verifies it with you in person.</p>
            @endif
        </x-widgets.panel>
    </div>
</x-dashboard-layout>
