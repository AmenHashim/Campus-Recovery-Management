<x-dashboard-layout
    title="My Claims"
    subtitle="Track the status of items you've claimed"
    active="claims"
>
    @if (session('error'))
        <div class="auth-alert auth-alert-error" style="margin-bottom:20px;">
            <i class="ti ti-alert-circle"></i> {{ session('error') }}
        </div>
    @endif

    @if (session('status'))
        <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
            <i class="ti ti-circle-check"></i> {{ session('status') }}
        </div>
    @endif

    <style>
        .claim-token {
            font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12px; font-weight:700;
            letter-spacing:1px; color:var(--primary); background:var(--primary-glow);
            padding:3px 9px; border-radius:6px; display:inline-block;
        }
        .claim-outcome { font-size:11px; color:var(--muted); margin-top:6px; }
        .claim-outcome.rejected { color:#D32F2F; }
        .claim-next-step {
            font-size:11px; margin-top:6px; color:var(--primary); display:flex; align-items:center; gap:6px;
        }
    </style>

    @if (session('new_claim_token'))
        <div class="ticket-container" style="margin-bottom:24px;">
            <div class="ticket-header">
                <div class="ticket-logo">UNI</div>
                <h4>Claim Submitted</h4>
                <p>Bring this token and a valid ID to the Lost &amp; Found office</p>
            </div>
            <div class="ticket-body">
                <div class="ticket-row">
                    <span class="ticket-label">Item</span>
                    <span class="ticket-val">{{ session('new_claim_item') }}</span>
                </div>
                <div class="ticket-token-box" style="text-align:center;">{{ session('new_claim_token') }}</div>
            </div>
            <div class="ticket-footer">Keep this token — the office will look up your claim by it.</div>
        </div>
    @endif

    @if ($counts['all'] > 0)
        <div class="category-chips" style="margin-bottom:20px;">
            @foreach ([
                '' => ['All', 'ti-apps'],
                'pending' => ['Pending', 'ti-clock'],
                'verified' => ['Verified', 'ti-circle-check'],
                'rejected' => ['Rejected', 'ti-circle-x'],
            ] as $key => [$label, $icon])
                <a href="{{ route('student.claims.index', array_filter(['status' => $key])) }}"
                   class="chip {{ request('status', '') === $key ? 'active' : '' }}">
                    <i class="ti {{ $icon }}"></i> {{ $label }} ({{ $counts[$key ?: 'all'] }})
                </a>
            @endforeach
        </div>
    @endif

    @if ($claims->isEmpty())
        <div class="empty-state">
            <i class="ti ti-ticket-off"></i>
            @if (request('status'))
                You have no {{ request('status') }} claims.
            @else
                You haven't submitted any claims yet. Browse found items to get started.
            @endif
        </div>
    @else
        <div class="claims-list">
            @foreach ($claims as $claim)
                <div class="claim-item-card" style="align-items:flex-start;">
                    <div class="claim-item-icon">
                        <i class="ti {{ $claim->item->categoryIcon() }}"></i>
                    </div>
                    <div class="claim-item-info">
                        <h3>{{ $claim->item->name }}</h3>
                        <p>{{ $claim->item->location }} &middot; Filed {{ $claim->created_at->format('M j, Y') }}</p>
                        <p style="margin-top:6px;">
                            Collection token: <span class="claim-token">{{ $claim->token }}</span>
                        </p>

                        @if ($claim->status === 'pending')
                            <p class="claim-next-step">
                                <i class="ti ti-info-circle"></i>
                                Bring this token and your student/staff ID to the Lost &amp; Found office.
                            </p>
                        @elseif ($claim->status === 'verified')
                            <p class="claim-outcome">
                                <i class="ti ti-circle-check"></i>
                                Verified{{ $claim->verified_at ? ' on '.$claim->verified_at->format('M j, Y') : '' }}
                                @if ($claim->verifier) by {{ $claim->verifier->name }} @endif
                                — collect the item from the office.
                            </p>
                        @elseif ($claim->status === 'rejected')
                            <p class="claim-outcome rejected">
                                <i class="ti ti-circle-x"></i>
                                Rejected{{ $claim->verified_at ? ' on '.$claim->verified_at->format('M j, Y') : '' }}
                                @if ($claim->notes) — {{ $claim->notes }} @endif
                            </p>
                        @endif
                    </div>
                    <div class="claim-item-action">
                        <span class="badge {{ $claim->statusBadgeClass() }}">{{ ucfirst($claim->status) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-dashboard-layout>
