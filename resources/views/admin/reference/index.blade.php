<x-dashboard-layout
    title="Categories & Locations"
    subtitle="Manage the lists that populate the item-report forms"
    active="reference"
>
    @if (session('status'))
        <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
            <i class="ti ti-circle-check"></i> {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="auth-alert auth-alert-error" style="margin-bottom:20px;">
            <i class="ti ti-alert-circle"></i> {{ session('error') }}
        </div>
    @endif

    @php $mapsKey = config('services.google_maps.key'); @endphp

    <style>
        /* Two panels on wide screens, stacked below — the old fixed 1fr/1fr squeezed
           every row's controls into half a screen, which is what made this feel cramped. */
        .ref-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(460px, 1fr)); gap:24px; align-items:start; }

        .ref-panel { padding:0; overflow:hidden; }
        .ref-head {
            display:flex; align-items:center; gap:10px;
            padding:18px 20px; border-bottom:1px solid var(--glass-border);
        }
        .ref-head i { font-size:18px; color:var(--primary); }
        .ref-head h4 { margin:0; flex:1; font-size:15px; }
        .ref-pill {
            font-size:11px; font-weight:700; padding:3px 10px; border-radius:999px;
            background:var(--primary-glow); color:var(--primary); white-space:nowrap;
        }

        /* ── add form ── */
        .ref-add { padding:16px 20px; border-bottom:1px solid var(--glass-border); }
        .ref-add .fields { display:flex; gap:8px; flex-wrap:wrap; }
        .ref-add .fields input { flex:1 1 160px; min-width:0; }
        .ref-add .fields input.narrow { flex:0 1 140px; }
        .ref-add .btn { flex:0 0 auto; }
        .ref-add .coords { display:flex; gap:8px; margin-top:8px; flex-wrap:wrap; }
        .ref-add .coords input { flex:1 1 120px; min-width:0; }

        /* ── rows ── */
        .ref-list { padding:6px 8px 10px; }
        .ref-row { padding:10px 12px; border-radius:10px; transition:background .15s; }
        .ref-row + .ref-row { border-top:1px solid var(--glass-border); }
        .ref-row:hover { background:var(--primary-glow); }
        .ref-row.inactive { opacity:.55; }

        .ref-view { display:flex; align-items:center; gap:12px; }
        .ref-icon {
            width:34px; height:34px; flex:0 0 34px; border-radius:9px;
            display:grid; place-items:center; font-size:16px;
            background:var(--primary-glow); color:var(--primary);
        }
        .ref-body { flex:1; min-width:0; }
        .ref-name {
            font-weight:600; font-size:13.5px;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .ref-meta { font-size:11px; color:var(--muted); margin-top:2px; display:flex; gap:8px; flex-wrap:wrap; }
        .ref-meta .pinned { color:var(--primary); }
        .ref-actions { display:flex; gap:4px; flex:0 0 auto; }
        .ref-actions .btn { padding:7px 9px; line-height:1; }
        .ref-actions form { display:inline; }

        /* ── inline edit ── */
        .ref-edit { display:none; }
        .ref-row.editing .ref-view { display:none; }
        .ref-row.editing .ref-edit { display:block; }
        .ref-row.editing { background:var(--primary-glow); }
        .ref-edit .fields { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .ref-edit .fields input { flex:1 1 150px; min-width:0; }
        .ref-edit .fields input.narrow { flex:0 1 130px; }
        .ref-edit .coords { display:flex; gap:8px; margin-top:8px; flex-wrap:wrap; }
        .ref-edit .coords input { flex:1 1 110px; min-width:0; }
        .ref-edit .edit-actions { display:flex; gap:6px; margin-top:10px; }

        .ref-empty { padding:28px 20px; text-align:center; color:var(--muted); font-size:12.5px; }
        .ref-empty i { display:block; font-size:26px; margin-bottom:8px; opacity:.5; }

        /* ── map modal ── */
        .map-modal {
            position:fixed; inset:0; z-index:900; display:none;
            background:rgba(0,0,0,.5); place-items:center; padding:20px;
        }
        .map-modal.open { display:grid; }
        .map-modal-box {
            background:var(--card-bg); border:1px solid var(--glass-border);
            border-radius:14px; width:min(720px,100%); overflow:hidden;
        }
        .map-modal-head {
            display:flex; align-items:center; gap:10px;
            padding:14px 18px; border-bottom:1px solid var(--glass-border);
        }
        .map-modal-head h4 { margin:0; flex:1; font-size:14px; }
        .map-modal-body { padding:14px 18px; }
        .map-modal-body #admin-map { width:100%; height:340px; border-radius:10px; border:1px solid var(--glass-border); }
        .map-modal-body .search { margin-bottom:10px; width:100%; }
        .map-modal-foot {
            display:flex; justify-content:flex-end; gap:8px;
            padding:12px 18px; border-top:1px solid var(--glass-border);
        }
        .map-hint { font-size:11.5px; color:var(--muted); margin:8px 0 0; }

        @media (max-width:520px) {
            .ref-actions { flex-wrap:wrap; justify-content:flex-end; }
            .ref-view { flex-wrap:wrap; }
            .ref-body { flex:1 1 100%; order:2; }
        }
    </style>

    <div class="ref-grid">
        {{-- ─────────────────── Categories ─────────────────── --}}
        <section class="glass-card ref-panel">
            <header class="ref-head">
                <i class="ti ti-category"></i>
                <h4>Item Categories</h4>
                <span class="ref-pill">{{ $categories->count() }} total</span>
            </header>

            <div class="ref-add">
                <form method="POST" action="{{ route('admin.reference.categories.store') }}">
                    @csrf
                    <div class="fields">
                        <input type="text" name="name" placeholder="New category name" required>
                        <input type="text" name="icon" class="narrow" placeholder="Icon e.g. ti-key">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-plus"></i> Add</button>
                    </div>
                </form>
                @error('name') <span class="field-error" style="display:block;margin-top:8px;">{{ $message }}</span> @enderror
            </div>

            <div class="ref-list">
                @forelse ($categories as $category)
                    @php $used = $category->itemCount(); @endphp
                    <div class="ref-row {{ $category->is_active ? '' : 'inactive' }}" id="cat-{{ $category->id }}">
                        <div class="ref-view">
                            <span class="ref-icon"><i class="ti {{ $category->icon ?: 'ti-box' }}"></i></span>
                            <div class="ref-body">
                                <div class="ref-name">{{ $category->name }}</div>
                                <div class="ref-meta">
                                    <span>{{ $used }} {{ Str::plural('item', $used) }}</span>
                                    @unless ($category->is_active) <span>· hidden from forms</span> @endunless
                                </div>
                            </div>
                            <div class="ref-actions">
                                <button type="button" class="btn btn-ghost" title="Rename"
                                        onclick="refEdit('cat-{{ $category->id }}', true)">
                                    <i class="ti ti-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.reference.categories.toggle', $category) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost"
                                            title="{{ $category->is_active ? 'Hide from forms' : 'Show in forms' }}">
                                        <i class="ti {{ $category->is_active ? 'ti-eye' : 'ti-eye-off' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.reference.categories.destroy', $category) }}"
                                      onsubmit="return confirm('Delete “{{ $category->name }}”?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Delete"><i class="ti ti-trash"></i></button>
                                </form>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.reference.categories.update', $category) }}" class="ref-edit">
                            @csrf @method('PUT')
                            <div class="fields">
                                <input type="text" name="name" value="{{ $category->name }}" required>
                                <input type="text" name="icon" class="narrow" value="{{ $category->icon }}" placeholder="ti-…">
                            </div>
                            <div class="edit-actions">
                                <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Save</button>
                                <button type="button" class="btn btn-ghost" onclick="refEdit('cat-{{ $category->id }}', false)">Cancel</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="ref-empty"><i class="ti ti-category-2"></i> No categories yet — add the first one above.</p>
                @endforelse
            </div>
        </section>

        {{-- ─────────────────── Locations ─────────────────── --}}
        <section class="glass-card ref-panel">
            <header class="ref-head">
                <i class="ti ti-map-pin"></i>
                <h4>Campus Locations</h4>
                <span class="ref-pill">{{ $locations->count() }} total</span>
            </header>

            <div class="ref-add">
                <form method="POST" action="{{ route('admin.reference.locations.store') }}" id="loc-add-form">
                    @csrf
                    <div class="fields">
                        <input type="text" name="name" placeholder="New location name" required>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-plus"></i> Add</button>
                    </div>
                    <div class="coords">
                        <input type="text" name="latitude" placeholder="Latitude (optional)" inputmode="decimal">
                        <input type="text" name="longitude" placeholder="Longitude (optional)" inputmode="decimal">
                        @if ($mapsKey)
                            <button type="button" class="btn btn-ghost" onclick="openMapFor('loc-add-form')">
                                <i class="ti ti-map-2"></i> Pick on map
                            </button>
                        @endif
                    </div>
                </form>
                @if ($mapsKey)
                    <p class="map-hint">Coordinates are optional. Setting them lets the report form drop a pin automatically when someone picks this location.</p>
                @endif
            </div>

            <div class="ref-list">
                @forelse ($locations as $location)
                    @php $used = $location->itemCount(); @endphp
                    <div class="ref-row {{ $location->is_active ? '' : 'inactive' }}" id="loc-{{ $location->id }}">
                        <div class="ref-view">
                            <span class="ref-icon">
                                <i class="ti {{ $location->hasPin() ? 'ti-map-pin-filled' : 'ti-map-pin' }}"></i>
                            </span>
                            <div class="ref-body">
                                <div class="ref-name">{{ $location->name }}</div>
                                <div class="ref-meta">
                                    <span>{{ $used }} {{ Str::plural('item', $used) }}</span>
                                    @if ($location->hasPin())
                                        <span class="pinned">· pinned on map</span>
                                    @else
                                        <span>· no coordinates</span>
                                    @endif
                                    @unless ($location->is_active) <span>· hidden from forms</span> @endunless
                                </div>
                            </div>
                            <div class="ref-actions">
                                <button type="button" class="btn btn-ghost" title="Edit"
                                        onclick="refEdit('loc-{{ $location->id }}', true)">
                                    <i class="ti ti-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.reference.locations.toggle', $location) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost"
                                            title="{{ $location->is_active ? 'Hide from forms' : 'Show in forms' }}">
                                        <i class="ti {{ $location->is_active ? 'ti-eye' : 'ti-eye-off' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.reference.locations.destroy', $location) }}"
                                      onsubmit="return confirm('Delete “{{ $location->name }}”?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Delete"><i class="ti ti-trash"></i></button>
                                </form>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.reference.locations.update', $location) }}"
                              class="ref-edit" id="loc-form-{{ $location->id }}">
                            @csrf @method('PUT')
                            <div class="fields">
                                <input type="text" name="name" value="{{ $location->name }}" required>
                            </div>
                            <div class="coords">
                                <input type="text" name="latitude" value="{{ $location->latitude }}"
                                       placeholder="Latitude" inputmode="decimal">
                                <input type="text" name="longitude" value="{{ $location->longitude }}"
                                       placeholder="Longitude" inputmode="decimal">
                                @if ($mapsKey)
                                    <button type="button" class="btn btn-ghost" onclick="openMapFor('loc-form-{{ $location->id }}')">
                                        <i class="ti ti-map-2"></i> Pick on map
                                    </button>
                                @endif
                            </div>
                            <div class="edit-actions">
                                <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Save</button>
                                <button type="button" class="btn btn-ghost" onclick="refEdit('loc-{{ $location->id }}', false)">Cancel</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="ref-empty"><i class="ti ti-map-off"></i> No locations yet — add the first one above.</p>
                @endforelse
            </div>
        </section>
    </div>

    @if ($mapsKey)
        {{-- One shared map dialog, reused by every row rather than one map per location. --}}
        <div class="map-modal" id="map-modal">
            <div class="map-modal-box">
                <header class="map-modal-head">
                    <i class="ti ti-map-2" style="color:var(--primary);"></i>
                    <h4>Pick the location</h4>
                    <button type="button" class="btn btn-ghost" onclick="closeMap()"><i class="ti ti-x"></i></button>
                </header>
                <div class="map-modal-body">
                    <input type="text" id="admin-map-search" class="search" placeholder="Search for a place…">
                    <div id="admin-map"></div>
                    <p class="map-hint" id="admin-map-status">Click anywhere on the map to place the pin.</p>
                </div>
                <footer class="map-modal-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeMap()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyMap()">
                        <i class="ti ti-check"></i> Use this spot
                    </button>
                </footer>
            </div>
        </div>
    @endif

    <script>
        function refEdit(rowId, on) {
            document.getElementById(rowId).classList.toggle('editing', on);
        }
    </script>

    @if ($mapsKey)
        <script>
            let adminMap = null, adminMarker = null, adminTargetForm = null, adminPos = null;

            function openMapFor(formId) {
                adminTargetForm = document.getElementById(formId);
                document.getElementById('map-modal').classList.add('open');

                const lat = parseFloat(adminTargetForm.querySelector('[name=latitude]').value);
                const lng = parseFloat(adminTargetForm.querySelector('[name=longitude]').value);
                const start = (!isNaN(lat) && !isNaN(lng))
                    ? { lat, lng }
                    : { lat: {{ config('services.google_maps.center.lat') }}, lng: {{ config('services.google_maps.center.lng') }} };

                adminPos = (!isNaN(lat) && !isNaN(lng)) ? start : null;

                if (!adminMap) return; // maps script still loading — reopening will work
                google.maps.event.trigger(adminMap, 'resize');
                adminMap.setCenter(start);
                adminPos ? placeAdminPin(start) : clearAdminPin();
            }

            function placeAdminPin(pos) {
                adminPos = pos;
                if (!adminMarker) {
                    adminMarker = new google.maps.Marker({ map: adminMap, position: pos, draggable: true });
                    adminMarker.addListener('dragend', e => placeAdminPin(e.latLng.toJSON()));
                } else {
                    adminMarker.setPosition(pos);
                    adminMarker.setMap(adminMap);
                }
                document.getElementById('admin-map-status').textContent =
                    'Pin at ' + pos.lat.toFixed(7) + ', ' + pos.lng.toFixed(7);
            }

            function clearAdminPin() {
                adminPos = null;
                if (adminMarker) adminMarker.setMap(null);
                document.getElementById('admin-map-status').textContent = 'Click anywhere on the map to place the pin.';
            }

            function applyMap() {
                if (!adminPos || !adminTargetForm) return closeMap();
                adminTargetForm.querySelector('[name=latitude]').value  = adminPos.lat.toFixed(7);
                adminTargetForm.querySelector('[name=longitude]').value = adminPos.lng.toFixed(7);
                closeMap();
            }

            function closeMap() {
                document.getElementById('map-modal').classList.remove('open');
            }

            document.getElementById('map-modal').addEventListener('click', e => {
                if (e.target.id === 'map-modal') closeMap();
            });
            document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMap(); });

            function initAdminMap() {
                adminMap = new google.maps.Map(document.getElementById('admin-map'), {
                    center: { lat: {{ config('services.google_maps.center.lat') }}, lng: {{ config('services.google_maps.center.lng') }} },
                    zoom: {{ config('services.google_maps.zoom') }},
                    mapTypeControl: false,
                    streetViewControl: false,
                });
                adminMap.addListener('click', e => placeAdminPin(e.latLng.toJSON()));

                const searchEl = document.getElementById('admin-map-search');
                if (google.maps.places && searchEl) {
                    const ac = new google.maps.places.Autocomplete(searchEl, { fields: ['geometry', 'name'] });
                    ac.addListener('place_changed', () => {
                        const place = ac.getPlace();
                        if (!place.geometry) return;
                        const pos = place.geometry.location.toJSON();
                        adminMap.setCenter(pos);
                        adminMap.setZoom(18);
                        placeAdminPin(pos);
                    });
                    searchEl.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
                }
            }
        </script>
        <script async defer
            src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback=initAdminMap&loading=async">
        </script>
    @endif
</x-dashboard-layout>
