<x-dashboard-layout
    title="Browse & Search"
    subtitle="Search the found-item pool for something that might be yours"
    active="browse"
>
    @if (session('error'))
        <div class="auth-alert auth-alert-error" style="margin-bottom:20px;">
            <i class="ti ti-alert-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="search-container">
        <x-search-bar
            :action="route('student.items.index')"
            :suggest="route('student.items.suggest')"
            :hidden="['category' => request('category')]"
            placeholder="Search by item name, category, location or description..."
        />

        <div class="category-chips">
            <a href="{{ route('student.items.index', array_filter(['q' => request('q')])) }}"
               class="chip {{ ! request('category') ? 'active' : '' }}">
                <i class="ti ti-apps"></i> All
            </a>
            @foreach ($categories as $cat)
                <a href="{{ route('student.items.index', array_filter(['q' => request('q'), 'category' => $cat])) }}"
                   class="chip {{ request('category') === $cat ? 'active' : '' }}">
                    {{ $cat }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="empty-state">
            <i class="ti ti-search-off"></i>
            No found items match your search yet. Check back soon.
        </div>
    @else
        <div class="items-grid">
            @foreach ($items as $item)
                <div class="item-card">
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
                        <span class="badge badge-found">{{ $item->category }}</span>

                        @if ($item->user_id !== auth()->id())
                            <form method="POST" action="{{ route('student.claims.store', $item) }}" style="margin-top:12px;">
                                @csrf
                                <button type="submit" class="btn btn-primary" style="width:100%;"
                                        onclick="return confirm('Submit a claim for this item? An officer will verify it with you in person.')">
                                    <i class="ti ti-ticket"></i> Claim This Item
                                </button>
                            </form>
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
