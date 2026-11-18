@extends('admin.layout')

@section('title', 'Markets')

@section('page_title', 'Manage markets & geofence map')

@push('head')
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
/>
@endpush

@push('styles')
<style>
    .markets-grid {
        display: grid;
        grid-template-columns: 1.05fr 1.35fr;
        gap: 18px;
        align-items: start;
        margin-bottom: 20px;
    }
    @media (max-width: 1120px) {
        .markets-grid { grid-template-columns: 1fr; }
    }
    .form-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }
    .form-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }
    @media (max-width: 640px) {
        .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
    }
    .map-canvas {
        width: 100%;
        height: 410px;
        border-radius: 14px;
        border: 1px solid var(--border);
        overflow: hidden;
        background: #0B1220;
    }
    .map-toolbar {
        display: flex;
        gap: 8px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .map-toolbar input {
        flex: 1;
        min-width: 210px;
    }
    .search-results {
        margin-bottom: 10px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #0B1220;
        max-height: 190px;
        overflow-y: auto;
        display: none;
    }
    .search-result-item {
        display: block;
        width: 100%;
        text-align: left;
        background: transparent;
        border: 0;
        border-bottom: 1px solid var(--border);
        color: var(--text);
        padding: 9px 12px;
        font-size: 12px;
        cursor: pointer;
    }
    .search-result-item:last-child { border-bottom: 0; }
    .search-result-item:hover { background: rgba(34, 197, 94, 0.1); }
    .search-result-sub { color: var(--muted); font-size: 11px; margin-top: 2px; }
    .map-mode-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 13px;
        border-radius: 12px;
        margin-bottom: 12px;
        border: 1px solid rgba(34, 197, 94, 0.3);
        background: rgba(34, 197, 94, 0.09);
        font-size: 12px;
        font-weight: 800;
    }
    .map-mode-banner.is-editing {
        border-color: rgba(245, 158, 11, 0.45);
        background: rgba(245, 158, 11, 0.12);
        color: #FDE68A;
    }
    .radius-presets {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .chip-btn {
        background: #0B1220;
        border: 1px solid var(--border);
        color: var(--muted);
        padding: 4px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
    }
    .chip-btn:hover, .chip-btn.active {
        border-color: rgba(34, 197, 94, 0.5);
        color: #86EFAC;
        background: rgba(34, 197, 94, 0.12);
    }
    .status-verified {
        border-color: rgba(34, 197, 94, 0.4);
        background: rgba(34, 197, 94, 0.12);
        color: #86EFAC;
    }
    .status-unverified {
        border-color: rgba(245, 158, 11, 0.45);
        background: rgba(245, 158, 11, 0.12);
        color: #FDE68A;
    }
    .market-row-highlight td {
        background: rgba(245, 158, 11, 0.08);
    }
    .summary-strip {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .coord-input {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12px;
    }
</style>
@endpush

@section('content')
@php
    $verifiedCount = $markets->filter(fn ($m) => $m->latitude !== null && $m->longitude !== null)->count();
    $unverifiedCount = $markets->count() - $verifiedCount;
@endphp

<p class="page-hint">
    Pinpoint each market on the map so the mobile app can verify user proximity (Haversine distance within the market's geofence radius).
    You can search any town/state in Nigeria, click the map or drag the pin, and adjust the geofence radius per market.
</p>

<div class="summary-strip">
    <span class="pill status-verified">✓ {{ $verifiedCount }} Verified on Map</span>
    @if ($unverifiedCount > 0)
        <span class="pill status-unverified">⚠ {{ $unverifiedCount }} Missing Map Pin (Cannot be selected in app yet)</span>
    @endif
    <span class="pill">Default Geofence: 750 m</span>
</div>

<div class="markets-grid" id="map-workspace">
    <!-- Left Column: Add / Edit Market Form -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h2 style="margin:0;font-size:16px;font-weight:900;" id="form-card-title">Add New Market</h2>
            <span class="pill" id="coord-status-pill">Click map to set pin</span>
        </div>

        <form method="post" action="{{ route('admin.markets.store') }}" id="create-market-form">
            @csrf
            <div class="form-grid-2">
                <div>
                    <label for="new-name">Market Name *</label>
                    <input id="new-name" name="name" value="{{ old('name') }}" placeholder="e.g. Utako Ultra Modern Market" required>
                </div>
                <div>
                    <label for="new-area">Area / District</label>
                    <input id="new-area" name="area" value="{{ old('area') }}" placeholder="e.g. Utako">
                </div>
            </div>

            <div class="form-grid-2">
                <div>
                    <label for="new-city">City / LGA</label>
                    <input id="new-city" name="city" value="{{ old('city', 'Abuja') }}" placeholder="Abuja">
                </div>
                <div>
                    <label for="new-state">State</label>
                    <input id="new-state" name="state" value="{{ old('state', 'FCT') }}" placeholder="FCT">
                </div>
            </div>

            <div class="form-grid-3">
                <div>
                    <label for="new-lat">Latitude *</label>
                    <input id="new-lat" class="coord-input" name="latitude" type="number" step="any" value="{{ old('latitude') }}" placeholder="9.068600" required>
                </div>
                <div>
                    <label for="new-lng">Longitude *</label>
                    <input id="new-lng" class="coord-input" name="longitude" type="number" step="any" value="{{ old('longitude') }}" placeholder="7.443300" required>
                </div>
                <div>
                    <label for="new-radius">Geofence Radius (m)</label>
                    <input id="new-radius" name="geofence_radius_m" type="number" min="100" max="5000" step="50" value="{{ old('geofence_radius_m', 750) }}">
                </div>
            </div>

            <div style="margin-bottom:14px;">
                <label>Quick Radius Presets</label>
                <div class="radius-presets">
                    <button type="button" class="chip-btn" data-radius="400">400 m (Small)</button>
                    <button type="button" class="chip-btn active" data-radius="750">750 m (Standard)</button>
                    <button type="button" class="chip-btn" data-radius="1000">1,000 m (1 km)</button>
                    <button type="button" class="chip-btn" data-radius="1500">1,500 m (Large complex)</button>
                    <button type="button" class="chip-btn" data-radius="2500">2,500 m (Regional)</button>
                </div>
            </div>

            <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
                <button type="button" class="btn" id="btn-find-from-form">
                    🔍 Find typed Name/Area on Map
                </button>
                <button class="btn btn-primary" type="submit" style="padding:10px 16px;">
                    + Save Market with Verified Pin
                </button>
            </div>
        </form>
    </div>

    <!-- Right Column: Interactive Map Picker -->
    <div class="card">
        <div class="map-mode-banner" id="map-mode-banner">
            <span id="map-mode-text">📍 Mode: Pinpointing location for <strong>New Market</strong> (click map or drag marker)</span>
            <div style="display:flex;gap:8px;align-items:center;">
                <button type="button" class="btn btn-primary" id="btn-save-active-row" style="display:none;padding:6px 12px;">
                    ✓ Save Adjusted Pin
                </button>
                <button type="button" class="btn" id="btn-reset-mode" style="display:none;padding:6px 10px;">
                    Switch to New Market
                </button>
            </div>
        </div>

        <div class="map-toolbar">
            <input
                type="text"
                id="map-search-input"
                placeholder="Search market, street, area, or state in Nigeria (e.g. Utako Market Abuja, Ikorodu Lagos)..."
            >
            <button type="button" class="btn btn-primary" id="map-search-btn">Search Map</button>
        </div>

        <div id="map-search-results" class="search-results"></div>

        <div id="admin-market-map" class="map-canvas"></div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;font-size:12px;color:var(--muted);flex-wrap:wrap;gap:8px;">
            <span id="map-live-readout">Pin: Not selected yet — click anywhere on the map or search above.</span>
            <span>Green circle = allowed user reporting distance</span>
        </div>
    </div>
</div>

<!-- Existing Markets Table -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
        <h2 style="margin:0;font-size:16px;font-weight:900;">Existing Markets ({{ $markets->count() }})</h2>
        <span style="font-size:12px;color:var(--muted);">
            Click <strong>📍 Adjust on Map</strong> on any row to visually place or move its pin and geofence radius.
        </span>
    </div>

    <div style="overflow:auto;">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Area</th>
                    <th>City / State</th>
                    <th>Map Verification</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Radius (m)</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($markets as $market)
                @php
                    $hasCoords = $market->latitude !== null && $market->longitude !== null;
                    $radiusM = $market->geofenceRadiusMeters();
                @endphp
                <tr id="market-row-{{ $market->id }}" data-market-id="{{ $market->id }}">
                    <form method="post" action="{{ route('admin.markets.update', $market->id) }}" id="market-form-{{ $market->id }}">
                        @csrf
                        <td style="min-width:170px;">
                            <input name="name" id="row-name-{{ $market->id }}" value="{{ $market->name }}" required>
                        </td>
                        <td style="min-width:120px;">
                            <input name="area" id="row-area-{{ $market->id }}" value="{{ $market->area }}" placeholder="Area">
                        </td>
                        <td style="min-width:145px;">
                            <div style="display:flex;gap:6px;">
                                <input name="city" id="row-city-{{ $market->id }}" value="{{ $market->city ?: 'Abuja' }}" placeholder="City" style="width:58%;">
                                <input name="state" id="row-state-{{ $market->id }}" value="{{ $market->state ?: 'FCT' }}" placeholder="State" style="width:42%;">
                            </div>
                        </td>
                        <td style="min-width:130px;">
                            @if ($hasCoords)
                                <span class="pill status-verified" id="row-badge-{{ $market->id }}">✓ Verified</span>
                            @else
                                <span class="pill status-unverified" id="row-badge-{{ $market->id }}">⚠ Needs Pin</span>
                            @endif
                        </td>
                        <td style="min-width:118px;">
                            <input
                                class="coord-input"
                                name="latitude"
                                id="row-lat-{{ $market->id }}"
                                type="number"
                                step="any"
                                value="{{ $market->latitude !== null ? number_format((float) $market->latitude, 6, '.', '') : '' }}"
                                placeholder="9.068600"
                            >
                        </td>
                        <td style="min-width:118px;">
                            <input
                                class="coord-input"
                                name="longitude"
                                id="row-lng-{{ $market->id }}"
                                type="number"
                                step="any"
                                value="{{ $market->longitude !== null ? number_format((float) $market->longitude, 6, '.', '') : '' }}"
                                placeholder="7.443300"
                            >
                        </td>
                        <td style="min-width:96px;">
                            <input
                                name="geofence_radius_m"
                                id="row-radius-{{ $market->id }}"
                                type="number"
                                min="100"
                                max="5000"
                                step="50"
                                value="{{ $radiusM }}"
                            >
                        </td>
                        <td style="width:60px;text-align:center;">
                            <input type="checkbox" name="is_active" value="1" @checked($market->is_active) style="width:auto;">
                        </td>
                        <td style="min-width:185px;white-space:nowrap;">
                            <button
                                type="button"
                                class="btn btn-pick-map"
                                data-id="{{ $market->id }}"
                            >
                                📍 Adjust on Map
                            </button>
                            <button class="btn btn-primary" type="submit">Save</button>
                        </td>
                    </form>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
></script>
<script>
(function () {
    const ABUJA_CENTER = [9.0765, 7.3986];
    const existingMarkets = @json($markets->map(fn ($m) => [
        'id' => $m->id,
        'name' => $m->name,
        'area' => $m->area,
        'city' => $m->city,
        'state' => $m->state,
        'lat' => $m->latitude,
        'lng' => $m->longitude,
        'radius' => $m->geofenceRadiusMeters(),
    ])->values());

    // Active target: null means "New Market" form; number means editing existing row ID
    let activeRowId = null;

    const newLatInput = document.getElementById('new-lat');
    const newLngInput = document.getElementById('new-lng');
    const newRadiusInput = document.getElementById('new-radius');
    const newNameInput = document.getElementById('new-name');
    const newAreaInput = document.getElementById('new-area');
    const newCityInput = document.getElementById('new-city');
    const newStateInput = document.getElementById('new-state');

    const modeBanner = document.getElementById('map-mode-banner');
    const modeText = document.getElementById('map-mode-text');
    const resetModeBtn = document.getElementById('btn-reset-mode');
    const saveActiveRowBtn = document.getElementById('btn-save-active-row');
    const liveReadout = document.getElementById('map-live-readout');
    const coordStatusPill = document.getElementById('coord-status-pill');
    const searchInput = document.getElementById('map-search-input');
    const searchBtn = document.getElementById('map-search-btn');
    const searchResultsBox = document.getElementById('map-search-results');

    const map = L.map('admin-market-map').setView(ABUJA_CENTER, 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let activeMarker = null;
    let activeCircle = null;

    // Render existing verified markets as subtle reference markers
    existingMarkets.forEach((m) => {
        if (m.lat != null && m.lng != null) {
            const refMarker = L.circleMarker([m.lat, m.lng], {
                radius: 6,
                color: '#22C55E',
                fillColor: '#22C55E',
                fillOpacity: 0.75,
                weight: 2
            }).addTo(map);
            refMarker.bindTooltip(`${m.name} (${m.radius}m)`, { direction: 'top' });
            refMarker.on('click', () => selectRowForMapEditing(m.id, false));
        }
    });

    function getCurrentRadius() {
        if (activeRowId !== null) {
            const el = document.getElementById('row-radius-' + activeRowId);
            return Math.max(100, Math.min(5000, parseInt(el?.value || '750', 10) || 750));
        }
        return Math.max(100, Math.min(5000, parseInt(newRadiusInput.value || '750', 10) || 750));
    }

    function setActivePin(lat, lng, pan = true) {
        const cleanLat = Number(lat).toFixed(6);
        const cleanLng = Number(lng).toFixed(6);
        const radius = getCurrentRadius();

        if (!activeMarker) {
            activeMarker = L.marker([cleanLat, cleanLng], { draggable: true }).addTo(map);
            activeMarker.on('dragend', function (ev) {
                const pos = ev.target.getLatLng();
                setActivePin(pos.lat, pos.lng, false);
            });
        } else {
            activeMarker.setLatLng([cleanLat, cleanLng]);
        }

        if (!activeCircle) {
            activeCircle = L.circle([cleanLat, cleanLng], {
                radius: radius,
                color: '#22C55E',
                fillColor: '#22C55E',
                fillOpacity: 0.16,
                weight: 2
            }).addTo(map);
        } else {
            activeCircle.setLatLng([cleanLat, cleanLng]);
            activeCircle.setRadius(radius);
        }

        if (pan) {
            map.setView([cleanLat, cleanLng], Math.max(map.getZoom(), 15));
        }

        if (activeRowId !== null) {
            const latEl = document.getElementById('row-lat-' + activeRowId);
            const lngEl = document.getElementById('row-lng-' + activeRowId);
            const badgeEl = document.getElementById('row-badge-' + activeRowId);
            if (latEl) latEl.value = cleanLat;
            if (lngEl) lngEl.value = cleanLng;
            if (badgeEl) {
                badgeEl.className = 'pill status-verified';
                badgeEl.textContent = '✓ Pin Ready (Save)';
            }
            const nameVal = document.getElementById('row-name-' + activeRowId)?.value || ('Market #' + activeRowId);
            liveReadout.textContent = `Editing "${nameVal}" → Lat: ${cleanLat}, Lng: ${cleanLng} (Radius: ${radius}m)`;
        } else {
            newLatInput.value = cleanLat;
            newLngInput.value = cleanLng;
            coordStatusPill.className = 'pill status-verified';
            coordStatusPill.textContent = `✓ Pin set (${cleanLat}, ${cleanLng})`;
            liveReadout.textContent = `New Market Pin → Lat: ${cleanLat}, Lng: ${cleanLng} (Radius: ${radius}m)`;
        }
    }

    function updateCircleRadiusOnly(radiusMeters) {
        const r = Math.max(100, Math.min(5000, parseInt(radiusMeters, 10) || 750));
        if (activeCircle) {
            activeCircle.setRadius(r);
        }
        if (activeRowId !== null) {
            const rowRadius = document.getElementById('row-radius-' + activeRowId);
            if (rowRadius) rowRadius.value = r;
        } else {
            newRadiusInput.value = r;
        }
        document.querySelectorAll('.chip-btn[data-radius]').forEach((btn) => {
            btn.classList.toggle('active', parseInt(btn.dataset.radius, 10) === r);
        });
    }

    map.on('click', function (e) {
        setActivePin(e.latlng.lat, e.latlng.lng, false);
    });

    // Sync manual lat/lng/radius edits on New Market form
    [newLatInput, newLngInput].forEach((inp) => {
        inp.addEventListener('change', () => {
            if (activeRowId === null && newLatInput.value && newLngInput.value) {
                setActivePin(parseFloat(newLatInput.value), parseFloat(newLngInput.value), true);
            }
        });
    });

    newRadiusInput.addEventListener('input', () => {
        updateCircleRadiusOnly(newRadiusInput.value);
    });

    document.querySelectorAll('.chip-btn[data-radius]').forEach((btn) => {
        btn.addEventListener('click', () => {
            updateCircleRadiusOnly(btn.dataset.radius);
        });
    });

    // Nominatim search for Nigerian markets/locations
    async function runPlaceSearch(queryText) {
        const q = (queryText || '').trim();
        if (!q) return;

        searchResultsBox.style.display = 'block';
        searchResultsBox.innerHTML = '<div style="padding:10px 12px;font-size:12px;color:var(--muted);">Searching Nigeria map…</div>';

        try {
            const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&countrycodes=ng&addressdetails=1&limit=6&q=' + encodeURIComponent(q);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });
            const items = await res.json();

            if (!Array.isArray(items) || items.length === 0) {
                searchResultsBox.innerHTML = '<div style="padding:10px 12px;font-size:12px;color:#FCA5A5;">No exact match found by that name. Try searching the district/area or city (e.g. "Utako Abuja" or "Ikorodu Lagos"), then click the exact spot on the map.</div>';
                return;
            }

            searchResultsBox.innerHTML = '';
            items.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'search-result-item';
                btn.innerHTML = `
                    <div style="font-weight:800;">${item.name || item.display_name.split(',')[0]}</div>
                    <div class="search-result-sub">${item.display_name} (${Number(item.lat).toFixed(5)}, ${Number(item.lon).toFixed(5)})</div>
                `;
                btn.addEventListener('click', () => {
                    setActivePin(parseFloat(item.lat), parseFloat(item.lon), true);
                    searchResultsBox.style.display = 'none';
                });
                searchResultsBox.appendChild(btn);
            });
        } catch (err) {
            searchResultsBox.innerHTML = '<div style="padding:10px 12px;font-size:12px;color:#FCA5A5;">Map search unavailable right now. You can still zoom and click directly on the map.</div>';
        }
    }

    searchBtn.addEventListener('click', () => runPlaceSearch(searchInput.value));
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            runPlaceSearch(searchInput.value);
        }
    });

    document.getElementById('btn-find-from-form').addEventListener('click', () => {
        switchToNewMarketMode();
        const parts = [newNameInput.value, newAreaInput.value, newCityInput.value, newStateInput.value]
            .map((s) => (s || '').trim())
            .filter(Boolean);
        const query = parts.join(', ') || 'Abuja';
        searchInput.value = query;
        runPlaceSearch(query);
    });

    function switchToNewMarketMode() {
        activeRowId = null;
        document.querySelectorAll('tr[data-market-id]').forEach((tr) => tr.classList.remove('market-row-highlight'));
        modeBanner.classList.remove('is-editing');
        modeText.innerHTML = '📍 Mode: Pinpointing location for <strong>New Market</strong> (click map or drag marker)';
        resetModeBtn.style.display = 'none';
        saveActiveRowBtn.style.display = 'none';
        if (newLatInput.value && newLngInput.value) {
            setActivePin(parseFloat(newLatInput.value), parseFloat(newLngInput.value), false);
        }
    }

    function selectRowForMapEditing(marketId, scrollToMap = true) {
        activeRowId = marketId;
        document.querySelectorAll('tr[data-market-id]').forEach((tr) => {
            tr.classList.toggle('market-row-highlight', Number(tr.dataset.marketId) === Number(marketId));
        });

        const nameVal = document.getElementById('row-name-' + marketId)?.value || ('Market #' + marketId);
        const areaVal = document.getElementById('row-area-' + marketId)?.value || '';
        const cityVal = document.getElementById('row-city-' + marketId)?.value || 'Abuja';
        const stateVal = document.getElementById('row-state-' + marketId)?.value || '';
        const latVal = document.getElementById('row-lat-' + marketId)?.value || '';
        const lngVal = document.getElementById('row-lng-' + marketId)?.value || '';
        const radiusVal = document.getElementById('row-radius-' + marketId)?.value || '750';

        modeBanner.classList.add('is-editing');
        modeText.innerHTML = `✏️ Adjusting existing market: <strong>${nameVal}</strong> — click map or drag pin, then click Save`;
        resetModeBtn.style.display = 'inline-block';
        saveActiveRowBtn.style.display = 'inline-block';
        saveActiveRowBtn.textContent = `✓ Save "${nameVal}"`;

        newRadiusInput.value = radiusVal;
        updateCircleRadiusOnly(radiusVal);

        if (scrollToMap) {
            document.getElementById('map-workspace')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        if (latVal !== '' && lngVal !== '') {
            setActivePin(parseFloat(latVal), parseFloat(lngVal), true);
        } else {
            const q = [nameVal, areaVal, cityVal, stateVal].filter(Boolean).join(', ');
            searchInput.value = q;
            runPlaceSearch(q);
        }
    }

    resetModeBtn.addEventListener('click', switchToNewMarketMode);
    saveActiveRowBtn.addEventListener('click', () => {
        if (activeRowId !== null) {
            const form = document.getElementById('market-form-' + activeRowId);
            if (form) form.submit();
        }
    });

    document.querySelectorAll('.btn-pick-map').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = parseInt(btn.dataset.id, 10);
            selectRowForMapEditing(id, true);
        });
    });

    // Also update map circle live when editing a row's radius input directly
    document.querySelectorAll('input[id^="row-radius-"]').forEach((inp) => {
        inp.addEventListener('input', () => {
            const rowId = parseInt(inp.id.replace('row-radius-', ''), 10);
            if (activeRowId === rowId && activeCircle) {
                activeCircle.setRadius(Math.max(100, Math.min(5000, parseInt(inp.value, 10) || 750)));
            }
        });
    });
})();
</script>
@endpush
