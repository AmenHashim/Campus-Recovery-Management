<x-dashboard-layout
    title="Lost & Found Office"
    subtitle="{{ auth()->user()->roleLabel() }} · {{ auth()->user()->reg_no }}"
    active="dashboard"
>
    {{-- ── What needs attention ── --}}
    <div class="stats-grid">
        <x-widgets.stat
            label="Pending Claims" icon="ti-ticket"
            :value="$pendingClaims"
            :tone="$overdueClaims > 0 ? 'bad' : null"
            :hint="$overdueClaims > 0 ? $overdueClaims.' waiting over '.$slaDays.' days' : 'Nothing overdue'"
            :href="route('officer.claims.index', ['status' => 'pending'])"
        />
        <x-widgets.stat
            label="Items in Storage" icon="ti-box"
            :value="$itemsInStorage"
            :tone="$agingItems > 0 ? 'warn' : null"
            :hint="$agingItems > 0 ? $agingItems.' held over '.$agingDays.' days' : 'None past review age'"
            :href="route('officer.intake.index')"
        />
        <x-widgets.stat
            label="Returned This Month" icon="ti-package-import"
            :value="$returnedThisMonth" tone="good"
            hint="Items handed back to owners"
            :href="route('officer.intake.index', ['status' => 'returned'])"
        />
        <x-widgets.stat
            label="Notifications" icon="ti-bell"
            :value="$unreadNotifications"
            :hint="$guestReportsFiled.' guest reports on file'"
            :href="route('officer.notifications.index')"
        />
    </div>

    {{-- ── Verification queue ── --}}
    <div class="info-grid" style="grid-template-columns:1fr;">
        <x-widgets.panel
            title="Claims awaiting verification"
            icon="ti-shield-check"
            :link-url="route('officer.claims.index', ['status' => 'pending'])"
            link-label="Open queue"
        >
            @if ($claimQueue->isEmpty())
                <div class="widget-empty">Nothing waiting — the verification queue is clear.</div>
            @else
                <table class="widget-table">
                    <thead>
                        <tr>
                            <th>Token</th>
                            <th>Item</th>
                            <th>Claimant</th>
                            <th>Waiting</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($claimQueue as $claim)
                            @php $waitingDays = (int) $claim->created_at->diffInDays(now()); @endphp
                            <tr class="{{ $waitingDays >= $slaDays ? 'is-urgent' : '' }}">
                                <td class="t-strong">{{ $claim->token }}</td>
                                <td class="t-clip">{{ $claim->item->name }}</td>
                                <td>
                                    <span class="t-strong">{{ $claim->claimant->name }}</span><br>
                                    <span class="t-muted">{{ $claim->claimant->reg_no }}</span>
                                </td>
                                <td>
                                    @if ($waitingDays >= $slaDays)
                                        <span class="badge badge-lost">{{ $waitingDays }} days</span>
                                    @else
                                        <span class="t-muted">{{ $claim->created_at->diffForHumans() }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="widget-note">Rows highlighted in red have been waiting more than {{ $slaDays }} days.</p>
            @endif
        </x-widgets.panel>
    </div>

    {{-- ── Storage aging + category mix ── --}}
    <div class="info-grid">
        <x-widgets.panel
            title="Longest held in storage"
            icon="ti-clock-exclamation"
            :link-url="route('officer.intake.index')"
            link-label="Intake board"
        >
            @if ($oldestInStorage->isEmpty())
                <div class="widget-empty">No found items are currently in storage.</div>
            @else
                <table class="widget-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Found at</th>
                            <th>Held</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($oldestInStorage as $item)
                            @php $heldDays = (int) $item->date->diffInDays(now()); @endphp
                            <tr class="{{ $heldDays >= $agingDays ? 'is-urgent' : '' }}">
                                <td class="t-strong t-clip">{{ $item->name }}</td>
                                <td class="t-muted t-clip">{{ $item->location }}</td>
                                <td class="t-muted">{{ $heldDays }} days</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="widget-note">Items held over {{ $agingDays }} days are due a retention review — close them with a reason from the intake board.</p>
            @endif
        </x-widgets.panel>

        <x-widgets.panel title="Storage by category" icon="ti-category">
            <x-widgets.bar-chart
                :data="$storageByCategory"
                :total="$itemsInStorage"
                empty="Nothing in storage to break down yet."
            />
        </x-widgets.panel>
    </div>

    {{-- ── Intake trend ── --}}
    <div class="info-grid" style="grid-template-columns:1fr;">
        <x-widgets.panel title="Reports filed this week" icon="ti-chart-bar">
            <x-widgets.column-chart
                :series="$weeklyIntake"
                :labels="['found' => ['Found', 'var(--secondary)'], 'lost' => ['Lost', '#D32F2F']]"
            />
        </x-widgets.panel>
    </div>
</x-dashboard-layout>
