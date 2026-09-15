<x-dashboard-layout
    title="System Administration"
    subtitle="{{ auth()->user()->roleLabel() }} · {{ auth()->user()->reg_no }}"
    active="dashboard"
>
    {{-- ── System health ── --}}
    <div class="stats-grid">
        <x-widgets.stat
            label="Total Users" icon="ti-users"
            :value="number_format($totalUsers)"
            :hint="$newUsersThisMonth.' joined this month'"
            :href="route('admin.users.index')"
        />
        <x-widgets.stat
            label="Items in System" icon="ti-box"
            :value="number_format($totalItems)"
            :hint="$openClaims.' claims awaiting an officer'"
        />
        <x-widgets.stat
            label="Recovery Rate" icon="ti-package-import"
            :value="$recoveryRate.'%'"
            :tone="$recoveryRate >= 50 ? 'good' : ($recoveryRate > 0 ? 'warn' : null)"
            hint="Found items returned to owners"
            :href="route('admin.analytics.index')"
        />
        <x-widgets.stat
            label="Audit Entries" icon="ti-history"
            :value="number_format($auditEntries)"
            hint="Immutable activity trail"
            :href="route('admin.audit.index')"
        />
    </div>

    {{-- ── Population + item lifecycle ── --}}
    <div class="info-grid">
        <x-widgets.panel
            title="Accounts by role"
            icon="ti-users-group"
            :link-url="route('admin.users.index')"
            link-label="Manage users"
        >
            <x-widgets.bar-chart :data="$usersByRole" :total="$totalUsers" />

            <p class="widget-note">
                {{ $suspendedUsers }} suspended ·
                <a href="{{ route('admin.users.index', ['status' => 'deleted']) }}" style="color:var(--primary);">
                    {{ $deletedUsers }} deleted
                </a>
                — deleted accounts keep all their records.
            </p>
        </x-widgets.panel>

        <x-widgets.panel
            title="Items by lifecycle status"
            icon="ti-timeline"
            :link-url="route('admin.analytics.index')"
            link-label="Full analytics"
        >
            <x-widgets.bar-chart
                :data="$itemsByStatus"
                :total="$totalItems"
                :colors="['Returned' => 'green', 'Closed' => 'grey', 'Claimed' => 'amber']"
                empty="No items have been reported yet."
            />
        </x-widgets.panel>
    </div>

    {{-- ── Claim outcomes + report trend ── --}}
    <div class="info-grid">
        <x-widgets.panel title="Claim outcomes" icon="ti-clipboard-check">
            <x-widgets.bar-chart
                :data="$claimOutcomes"
                :colors="['Pending' => 'amber', 'Verified' => 'green', 'Rejected' => 'red']"
                empty="No claims have been submitted yet."
            />
            <p class="widget-note">Approval rate across reviewed claims: <strong>{{ $approvalRate }}%</strong></p>
        </x-widgets.panel>

        <x-widgets.panel title="Reports per month (last 6)" icon="ti-chart-bar">
            <x-widgets.column-chart
                :series="$trend"
                :labels="['found' => ['Found', 'var(--secondary)'], 'lost' => ['Lost', '#D32F2F']]"
                :height="140"
            />
        </x-widgets.panel>
    </div>

    {{-- ── Activity + newest accounts ── --}}
    <div class="info-grid">
        <x-widgets.panel
            title="Latest activity"
            icon="ti-history"
            :link-url="route('admin.audit.index')"
            link-label="Audit log"
        >
            @if ($recentActivity->isEmpty())
                <div class="widget-empty">Nothing has been recorded yet.</div>
            @else
                <table class="widget-table">
                    <tbody>
                        @foreach ($recentActivity as $log)
                            <tr>
                                <td style="width:22px;"><i class="ti {{ $log->icon() }}" style="color:var(--primary);"></i></td>
                                <td>
                                    <span class="t-strong t-clip" style="display:block;">{{ $log->description }}</span>
                                    <span class="t-muted">{{ $log->actorName() }}</span>
                                </td>
                                <td class="t-muted">{{ $log->created_at->diffForHumans(short: true) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-widgets.panel>

        <x-widgets.panel
            title="Newest accounts"
            icon="ti-user-plus"
            :link-url="route('admin.users.index')"
            link-label="All users"
        >
            @if ($newestUsers->isEmpty())
                <div class="widget-empty">No accounts yet.</div>
            @else
                <table class="widget-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($newestUsers as $user)
                            <tr>
                                <td>
                                    <span class="t-strong t-clip" style="display:block;">{{ $user->name }}</span>
                                    <span class="t-muted">{{ $user->reg_no }}</span>
                                </td>
                                <td><span class="badge {{ $user->roleBadgeClass() }}">{{ $user->roleLabel() }}</span></td>
                                <td class="t-muted">{{ $user->created_at->diffForHumans(short: true) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-widgets.panel>
    </div>
</x-dashboard-layout>
