<x-dashboard-layout
    title="Verify Claims"
    subtitle="Check the physical item and ID against each claim, then verify or reject"
    active="claims"
>
    @if (session('status'))
        <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
            <i class="ti ti-circle-check"></i> {{ session('status') }}
        </div>
    @endif

    <style>
        /* Decision controls: two buttons by default. The rejection reason is a
           second step that only appears once "Reject" is chosen — an officer
           approving a claim should never see an empty reason box. */
        .decision { min-width:230px; }
        .decision-buttons { display:flex; gap:8px; }
        .decision-buttons .btn { flex:1; justify-content:center; }
        .reject-panel {
            display:none; border:1px solid rgba(211,47,47,.35); background:rgba(211,47,47,.05);
            border-radius:12px; padding:10px; margin-top:2px;
        }
        .reject-panel.open { display:block; }
        .reject-panel label {
            display:block; font-size:11px; font-weight:700; color:#D32F2F; margin-bottom:6px;
        }
        .reject-panel textarea { width:100%; font-size:12px; resize:vertical; }
        .reject-panel .panel-actions { display:flex; gap:8px; margin-top:8px; }
        .reject-panel .panel-actions .btn { flex:1; justify-content:center; }
        .reject-panel .hint-note { font-size:10px; color:var(--muted); margin-top:6px; }
    </style>

    <div class="search-container" style="margin-bottom:20px;">
        <x-search-bar
            :action="route('officer.claims.index')"
            :suggest="route('officer.claims.suggest')"
            :hidden="['status' => request('status')]"
            placeholder="Search by token, claimant name, or reg. no..."
        />

        <div class="category-chips">
            @foreach ([
                '' => ['All', 'ti-apps'],
                'pending' => ['Pending', 'ti-clock'],
                'verified' => ['Verified', 'ti-circle-check'],
                'rejected' => ['Rejected', 'ti-circle-x'],
            ] as $key => [$label, $icon])
                <a href="{{ route('officer.claims.index', array_filter(['q' => request('q'), 'status' => $key])) }}"
                   class="chip {{ request('status', '') === $key ? 'active' : '' }}">
                    <i class="ti {{ $icon }}"></i> {{ $label }} ({{ $counts[$key ?: 'all'] }})
                </a>
            @endforeach
        </div>
    </div>

    @if ($claims->isEmpty())
        <div class="empty-state">
            <i class="ti ti-ticket-off"></i>
            @if (request('q'))
                No claims match "{{ request('q') }}".
            @else
                No claims have been submitted yet.
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
                        <p>Claimed by {{ $claim->claimant->name }} ({{ $claim->claimant->reg_no }})</p>
                        <p>Token: <code>{{ $claim->token }}</code> &middot; Submitted {{ $claim->created_at->diffForHumans() }}</p>

                        @if ($claim->status !== 'pending')
                            <p style="margin-top:6px;">
                                <span class="badge {{ $claim->statusBadgeClass() }}">{{ ucfirst($claim->status) }}</span>
                                @if ($claim->notes)
                                    &mdash; {{ $claim->notes }}
                                @endif
                            </p>
                        @endif
                    </div>

                    <div class="claim-item-action decision" style="flex-direction:column; align-items:stretch; gap:8px;">
                        @if ($claim->status === 'pending')
                            {{-- Re-open the panel if the reason came back empty from the server. --}}
                            @php $rejectFailed = $errors->has('notes') && (int) old('claim_id') === $claim->id; @endphp

                            <div class="decision-buttons" @if ($rejectFailed) style="display:none;" @endif>
                                <form method="POST" action="{{ route('officer.claims.verify', $claim) }}" style="flex:1;"
                                      onsubmit="return confirm('Confirm you have checked the item and the claimant\'s ID in person?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="width:100%;">
                                        <i class="ti ti-shield-check"></i> Verify
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger" style="flex:1;"
                                        onclick="openRejectPanel(this)">
                                    <i class="ti ti-shield-x"></i> Reject
                                </button>
                            </div>

                            <form method="POST" action="{{ route('officer.claims.reject', $claim) }}"
                                  class="reject-panel {{ $rejectFailed ? 'open' : '' }}">
                                @csrf
                                <input type="hidden" name="claim_id" value="{{ $claim->id }}">
                                <label for="notes-{{ $claim->id }}">Why is this claim being rejected?</label>
                                <textarea id="notes-{{ $claim->id }}" name="notes" rows="3" required
                                          placeholder="e.g. the claimant could not describe the item's contents">{{ $rejectFailed ? old('notes') : '' }}</textarea>
                                @if ($rejectFailed)
                                    <span class="field-error">{{ $errors->first('notes') }}</span>
                                @endif
                                <p class="hint-note">The claimant is notified with this reason, so keep it clear and factual.</p>
                                <div class="panel-actions">
                                    <button type="button" class="btn btn-ghost" onclick="closeRejectPanel(this)">Cancel</button>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="ti ti-shield-x"></i> Confirm rejection
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($claims->hasPages())
            <div style="display:flex; justify-content:center; gap:10px; margin-top:24px;">
                @if ($claims->previousPageUrl())
                    <a href="{{ $claims->previousPageUrl() }}" class="btn btn-ghost">Previous</a>
                @endif
                @if ($claims->hasMorePages())
                    <a href="{{ $claims->nextPageUrl() }}" class="btn btn-ghost">Next</a>
                @endif
            </div>
        @endif
    @endif

    <script>
        function openRejectPanel(button) {
            const buttons = button.closest('.decision-buttons');
            const panel = buttons.nextElementSibling;
            buttons.style.display = 'none';
            panel.classList.add('open');
            panel.querySelector('textarea').focus();
        }

        function closeRejectPanel(button) {
            const panel = button.closest('.reject-panel');
            panel.classList.remove('open');
            panel.querySelector('textarea').value = '';
            panel.previousElementSibling.style.display = '';
        }
    </script>
</x-dashboard-layout>
