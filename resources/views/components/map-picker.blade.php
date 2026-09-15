@props([
    // Name of the sibling text input holding the human-readable location name.
    'target' => 'location',
    // Existing coordinates, when editing a report that already has a pin.
    'lat' => null,
    'lng' => null,
    // Managed locations as [['name'=>…,'lat'=>…,'lng'=>…], …] so choosing a known
    // place from the datalist can drop the pin without the user touching the map.
    'known' => [],
])

@php
    $key    = config('services.google_maps.key');
    $center = config('services.google_maps.center');
    $zoom   = config('services.google_maps.zoom');
    $lat    = old('latitude', $lat);
    $lng    = old('longitude', $lng);
    // Only locations that actually carry coordinates are worth sending to the browser.
    $pinned = collect($known)->filter(fn ($l) => $l['lat'] !== null && $l['lng'] !== null)->values();
@endphp

{{-- Coordinates travel as plain hidden inputs, so the form still submits normally
     with JS disabled — it just submits empty, which validation treats as "no pin". --}}
<input type="hidden" name="latitude"  id="mp-lat" value="{{ $lat }}">
<input type="hidden" name="longitude" id="mp-lng" value="{{ $lng }}">

@if (! $key)
    {{-- No API key configured: stay silent rather than showing a broken grey box. --}}
@else
    <div class="form-group map-picker" id="map-picker">
        <label>
            Pinpoint on map <span class="hint">(optional — helps us narrow the search)</span>
        </label>

        <div class="map-toolbar">
            <input type="text" id="mp-search" placeholder="Search a place, or just click the map">
            <button type="button" class="btn btn-ghost btn-sm" id="mp-locate" title="Use my current location">
                <i class="ti ti-current-location"></i> My location
            </button>
            <button type="button" class="btn btn-ghost btn-sm" id="mp-clear" title="Remove the pin">
                <i class="ti ti-trash"></i> Clear
            </button>
        </div>

        <div id="mp-map" class="map-canvas"></div>

        <p class="map-status" id="mp-status">
            @if ($lat && $lng)
                <i class="ti ti-map-pin-filled"></i> Pinned at {{ $lat }}, {{ $lng }}
            @else
                <i class="ti ti-info-circle"></i> No pin yet — click the map to mark the exact spot.
            @endif
        </p>
    </div>

    <style>
        .map-picker .map-toolbar { display:flex; gap:8px; margin-bottom:10px; flex-wrap:wrap; }
        .map-picker .map-toolbar input { flex:1; min-width:180px; }
        .map-picker .btn-sm { padding:8px 12px; font-size:12px; white-space:nowrap; }
        .map-canvas {
            width:100%; height:280px; border-radius:12px;
            border:1px solid var(--glass-border); background:var(--panel-bg);
        }
        .map-status { font-size:12px; color:var(--muted); margin:8px 0 0; }
        .map-status.pinned { color:var(--primary); font-weight:600; }
        @media (max-width:600px) { .map-canvas { height:220px; } }
    </style>

    <script>
        // Coordinates of admin-managed locations, keyed by lowercased name.
        const MP_KNOWN = @json($pinned->keyBy(fn ($l) => mb_strtolower($l['name'])));

        function initLostyMapPicker() {
            const latEl    = document.getElementById('mp-lat');
            const lngEl    = document.getElementById('mp-lng');
            const statusEl = document.getElementById('mp-status');
            const nameEl   = document.getElementById(@json($target));

            const start = (latEl.value && lngEl.value)
                ? { lat: parseFloat(latEl.value), lng: parseFloat(lngEl.value) }
                : { lat: {{ $center['lat'] }}, lng: {{ $center['lng'] }} };

            const map = new google.maps.Map(document.getElementById('mp-map'), {
                center: start,
                zoom: {{ $zoom }},
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
            });

            let marker = null;

            function setPin(pos, recenter = false) {
                if (!marker) {
                    marker = new google.maps.Marker({ map, position: pos, draggable: true });
                    marker.addListener('dragend', e => setPin(e.latLng.toJSON()));
                } else {
                    marker.setPosition(pos);
                }
                latEl.value = pos.lat.toFixed(7);
                lngEl.value = pos.lng.toFixed(7);
                statusEl.className = 'map-status pinned';
                statusEl.innerHTML =
                    '<i class="ti ti-map-pin-filled"></i> Pinned at ' + latEl.value + ', ' + lngEl.value;
                if (recenter) map.panTo(pos);
            }

            function clearPin() {
                if (marker) { marker.setMap(null); marker = null; }
                latEl.value = lngEl.value = '';
                statusEl.className = 'map-status';
                statusEl.innerHTML =
                    '<i class="ti ti-info-circle"></i> No pin yet — click the map to mark the exact spot.';
            }

            if (latEl.value && lngEl.value) setPin(start);

            map.addListener('click', e => setPin(e.latLng.toJSON()));
            document.getElementById('mp-clear').addEventListener('click', clearPin);

            // Typing a known campus location drops the pin for free.
            if (nameEl) {
                nameEl.addEventListener('change', () => {
                    const hit = MP_KNOWN[nameEl.value.trim().toLowerCase()];
                    if (hit) setPin({ lat: parseFloat(hit.lat), lng: parseFloat(hit.lng) }, true);
                });
            }

            document.getElementById('mp-locate').addEventListener('click', () => {
                if (!navigator.geolocation) {
                    statusEl.textContent = 'Your browser will not share a location.';
                    return;
                }
                statusEl.innerHTML = '<i class="ti ti-loader"></i> Finding you…';
                navigator.geolocation.getCurrentPosition(
                    p => setPin({ lat: p.coords.latitude, lng: p.coords.longitude }, true),
                    () => { statusEl.innerHTML =
                        '<i class="ti ti-alert-circle"></i> Could not get your location — click the map instead.'; },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            });

            // Places search. Falls through harmlessly if the Places library is not enabled.
            const searchEl = document.getElementById('mp-search');
            if (google.maps.places && searchEl) {
                const ac = new google.maps.places.Autocomplete(searchEl, { fields: ['geometry', 'name'] });
                ac.bindTo('bounds', map);
                ac.addListener('place_changed', () => {
                    const place = ac.getPlace();
                    if (!place.geometry) return;
                    setPin(place.geometry.location.toJSON(), true);
                    map.setZoom(18);
                    // Only fill an empty name field — never overwrite what the user typed.
                    if (nameEl && !nameEl.value.trim() && place.name) nameEl.value = place.name;
                });
                // Enter in the search box should pick a place, not submit the report.
                searchEl.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
            }
        }
    </script>

    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key={{ $key }}&libraries=places&callback=initLostyMapPicker&loading=async">
    </script>
@endif
