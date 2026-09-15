<x-dashboard-layout
    title="Item Intake"
    subtitle="Found items currently held by the office and their storage status"
    active="intake"
>
    @if (session('status'))
        <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
            <i class="ti ti-circle-check"></i> {{ session('status') }}
        </div>
    @endif

    <style>
        /* Same decision pattern as claim verification: the two outcomes sit side by
           side, and the reason for the negative one only appears once it's chosen. */
        .decision { min-width:230px; }
        .decision-buttons { display:flex; gap:8px; }
        .decision-buttons .btn { flex:1; justify-content:center; }
        .close-panel {
            display:none; border:1px solid rgba(211,47,47,.35); background:rgba(211,47,47,.05);
            border-radius:12px; padding:10px; margin-top:2px;
        }
        .close-panel.open { display:block; }
        .close-panel label {
            display:block; font-size:11px; font-weight:700; color:#D32F2F; margin-bottom:6px;
        }
        .close-panel textarea { width:100%; font-size:12px; resize:vertical; }
        .close-panel .panel-actions { display:flex; gap:8px; margin-top:8px; }
        .close-panel .panel-actions .btn { flex:1; justify-content:center; }
        .close-panel .hint-note { font-size:10px; color:var(--muted); margin-top:6px; }
    </style>

    <div class="category-chips" style="margin-bottom:20px;">
        @foreach ([
            'in_storage' => 'In Storage',
            'claimed' => 'Claim Pending',
            'returned' => 'Returned',
            'closed' => 'Closed',
        ] as $key => $label)
            <a href="{{ route('officer.intake.index', ['status' => $key]) }}"
               class="chip {{ $status === $key ? 'active' : '' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($items->isEmpty())
        <div class="empty-state">
            <i class="ti ti-box-off"></i>
            No items in this category.
        </div>
    @else
        <div class="claims-list">
            @foreach ($items as $item)
                <div class="claim-item-card" style="align-items:flex-start;">
                    <div class="claim-item-icon">
                        <i class="ti {{ $item->categoryIcon() }}"></i>
                    </div>

                    <div class="claim-item-info">
                        <h3>{{ $item->name }}</h3>
                        <p>{{ $item->category }} &middot; {{ $item->location }} &middot; found {{ $item->date->format('M j, Y') }}</p>
                        <p>Reported by {{ $item->reporterName() }}
                            @if ($item->guestReporter && $item->guestReporter->phone)
                                ({{ $item->guestReporter->phone }})
                            @elseif ($item->reporter)
                                ({{ $item->reporter->reg_no }})
                            @endif
                        </p>
                        <p style="margin-top:6px;">
                            <span class="badge {{ $item->statusBadgeClass() }}">{{ ucfirst($item->status) }}</span>
                        </p>
                    </div>

                    @if (in_array($item->status, ['open', 'matched'], true))
                        @php $closeFailed = $errors->has('reason') && (int) old('item_id') === $item->id; @endphp

                        <div class="claim-item-action decision" style="flex-direction:column; align-items:stretch; gap:8px;">
                            <div class="decision-buttons" @if ($closeFailed) style="display:none;" @endif>
                                <form method="POST" action="{{ route('officer.intake.return', $item) }}" style="flex:1;"
                                      onsubmit="return confirm('Confirm the owner has collected this item in person?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="width:100%;">
                                        <i class="ti ti-check"></i> Returned
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger" style="flex:1;"
                                        onclick="openClosePanel(this)">
                                    <i class="ti ti-archive-off"></i> Close
                                </button>
                            </div>

                            <form method="POST" action="{{ route('officer.intake.close', $item) }}"
                                  class="close-panel {{ $closeFailed ? 'open' : '' }}">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <label for="reason-{{ $item->id }}">Why is this item being closed?</label>
                                <textarea id="reason-{{ $item->id }}" name="reason" rows="3" required
                                          placeholder="e.g. unclaimed past the 90-day retention period">{{ $closeFailed ? old('reason') : '' }}</textarea>
                                @if ($closeFailed)
                                    <span class="field-error">{{ $errors->first('reason') }}</span>
                                @endif
                                <p class="hint-note">Saved to the audit trail — the item stops appearing in storage but its record is kept.</p>
                                <div class="panel-actions">
                                    <button type="button" class="btn btn-ghost" onclick="closeClosePanel(this)">Cancel</button>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="ti ti-archive-off"></i> Confirm close
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($items->hasPages())
            <div style="display:flex; justify-content:center; gap:10px; margin-top:24px;">
                @if ($items->previousPageUrl())
                    <a href="{{ $items->previousPageUrl() }}" class="btn btn-ghost">Previous</a>
                @endif
                @if ($items->hasMorePages())
                    <a href="{{ $items->nextPageUrl() }}" class="btn btn-ghost">Next</a>
                @endif
            </div>
        @endif
    @endif

    <script>
        function openClosePanel(button) {
            const buttons = button.closest('.decision-buttons');
            const panel = buttons.nextElementSibling;
            buttons.style.display = 'none';
            panel.classList.add('open');
            panel.querySelector('textarea').focus();
        }

        function closeClosePanel(button) {
            const panel = button.closest('.close-panel');
            panel.classList.remove('open');
            panel.querySelector('textarea').value = '';
            panel.previousElementSibling.style.display = '';
        }
    </script>
</x-dashboard-layout>
