<x-dashboard-layout
    title="My Reported Items"
    subtitle="Everything you've reported as lost or found"
    active="my-reports"
>
    <div class="search-container">
        <x-search-bar
            :action="route('student.items.mine')"
            :suggest="route('student.items.mine.suggest')"
            :hidden="['type' => request('type')]"
            placeholder="Search your reports by name, category or location..."
        />

        <div class="category-chips">
            <a href="{{ route('student.items.mine', array_filter(['q' => request('q')])) }}"
               class="chip {{ ! request('type') ? 'active' : '' }}">
                <i class="ti ti-apps"></i> All ({{ $counts['all'] }})
            </a>
            <a href="{{ route('student.items.mine', array_filter(['q' => request('q'), 'type' => 'lost'])) }}"
               class="chip {{ request('type') === 'lost' ? 'active' : '' }}">
                <i class="ti ti-flag"></i> Lost ({{ $counts['lost'] }})
            </a>
            <a href="{{ route('student.items.mine', array_filter(['q' => request('q'), 'type' => 'found'])) }}"
               class="chip {{ request('type') === 'found' ? 'active' : '' }}">
                <i class="ti ti-box"></i> Found ({{ $counts['found'] }})
            </a>
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="empty-state">
            <i class="ti ti-flag-off"></i>
            @if (request('q') || request('type'))
                None of your reports match this search.
            @else
                You haven't reported any items yet.
            @endif
        </div>
        @if (! request('q') && ! request('type'))
            <div style="display:flex;justify-content:center;margin-top:16px;">
                <a href="{{ route('student.items.create') }}" class="btn btn-primary">
                    <i class="ti ti-flag"></i> Report an item
                </a>
            </div>
        @endif
    @else
        <div class="items-grid">
            @foreach ($items as $item)
                <div class="item-card" style="cursor:default;">
                    <div class="item-img">
                        @if ($item->imageUrl())
                            <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}">
                        @else
                            <i class="ti {{ $item->categoryIcon() }}"></i>
                        @endif
                    </div>
                    <div class="item-body">
                        <h3>{{ $item->name }}</h3>
                        <p><i class="ti ti-map-pin"></i> {{ $item->location }} &middot; {{ $item->date->format('M j') }}</p>
                        <span class="badge {{ $item->isLost() ? 'badge-lost' : 'badge-found' }}">{{ ucfirst($item->type) }}</span>
                        <span class="badge {{ $item->statusBadgeClass() }}">{{ ucfirst($item->status) }}</span>

                        @if ($item->claims_count > 0)
                            <p style="margin-top:10px;font-size:11px;color:var(--muted);">
                                <i class="ti ti-ticket"></i>
                                {{ $item->claims_count }} {{ Str::plural('claim', $item->claims_count) }} on this item
                            </p>
                        @endif
                    </div>
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
</x-dashboard-layout>
