@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Add New ODP') }}</h5>
                <a href="{{ route('odps.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('odps.store') }}" method="POST">
                    @csrf
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="odc_id" class="form-label">{{ __('ODC Origin') }}</label>
                            <select class="form-select @error('odc_id') is-invalid @enderror" id="odc_id" name="odc_id" required>
                                <option value="">{{ __('Select ODC') }}</option>
                                @foreach($odcs as $odc)
                                    <option value="{{ $odc->id }}" {{ old('odc_id') == $odc->id ? 'selected' : '' }}>
                                        {{ $odc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('odc_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="region_id" class="form-label">{{ __('Region (Desa)') }}</label>
                            <select class="form-select @error('region_id') is-invalid @enderror" id="region_id" name="region_id" required>
                                <option value="">{{ __('Select Region') }}</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}" data-abbr="{{ $region->abbreviation ?? strtoupper(substr($region->name, 0, 3)) }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('region_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="odp_area" class="form-label">{{ __('ODP Area Code') }}</label>
                            <input type="text" class="form-control @error('odp_area') is-invalid @enderror" id="odp_area" name="odp_area" value="{{ old('odp_area') }}" placeholder="{{ __('e.g. CIB') }}">
                            @error('odp_area')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="odp_cable" class="form-label">{{ __('ODP Cable No') }}</label>
                            <input type="text" class="form-control @error('odp_cable') is-invalid @enderror" id="odp_cable" name="odp_cable" value="{{ old('odp_cable') }}" placeholder="{{ __('e.g. 01') }}">
                            @error('odp_cable')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="kampung" class="form-label">{{ __('Kampung / Area') }}</label>
                            <input type="text" class="form-control @error('kampung') is-invalid @enderror" id="kampung" name="kampung" value="{{ old('kampung') }}" required placeholder="{{ __('e.g. CIBADAK') }}">
                            @error('kampung')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="color" class="form-label">{{ __('Color') }}</label>
                            <input type="text" class="form-control @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color') }}" required placeholder="{{ __('e.g. BLUE') }}">
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ __('ODP Name (Auto-generated)') }}</label>
                            <input type="text" class="form-control bg-light" id="name" name="name" value="{{ old('name') }}" readonly placeholder="{{ __('Auto-generated on save') }}">
                            <div class="form-text">{{ __('Format: ODP-[AREA]-[CABLE]-[COLOR]/[SEQ]') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label for="capacity" class="form-label">{{ __('Capacity (Ports)') }}</label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', 8) }}" min="1">
                            <div class="form-text text-muted">{{ __('Leave empty for unlimited capacity.') }}</div>
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Location') }}</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude') }}" placeholder="Latitude" required>
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude') }}" placeholder="Longitude" required>
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-text text-muted">{{ __('Click on the map below to select location.') }}</div>
                    </div>

                    <div id="map-picker" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;" class="mb-3"></div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description (Optional)') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-outline-secondary">{{ __('Reset') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Save ODP') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Name Auto-generation Preview
        const odcSelect = document.getElementById('odc_id');
        const regionSelect = document.getElementById('region_id');
        const areaInput = document.getElementById('odp_area');
        const cableInput = document.getElementById('odp_cable');
        const colorInput = document.getElementById('color');
        const nameInput = document.getElementById('name');
        
        let currentSequence = '[SEQ]';
        const nextSequenceUrl = "{{ route('odps.next_sequence', 'ODC_ID') }}";

        function fetchSequence(odcId) {
            if (!odcId) {
                currentSequence = '[SEQ]';
                updateNamePreview();
                return;
            }
            
            const url = nextSequenceUrl.replace('ODC_ID', odcId);

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    currentSequence = data.sequence;
                    updateNamePreview();
                })
                .catch(error => {
                    console.error('Error fetching sequence:', error);
                    currentSequence = '[SEQ]';
                    updateNamePreview();
                });
        }

        function updateNamePreview() {
            // Area: First + Middle + Last
            let areaVal = areaInput.value ? areaInput.value.replace(/\s+/g, '').toUpperCase() : '';
            if (areaVal.length > 3) {
                let first = areaVal.charAt(0);
                let last = areaVal.charAt(areaVal.length - 1);
                let middle = areaVal.charAt(Math.floor(areaVal.length / 2));
                areaVal = first + middle + last;
            } else if (areaVal.length === 0) {
                areaVal = '[AREA]';
            }

            // Cable: 2 digits
            let cableVal = cableInput.value ? cableInput.value.replace(/[^0-9]/g, '').padStart(2, '0') : '[CABLE]';

            // Color: 1 char
            let colorVal = colorInput.value ? colorInput.value.replace(/\s+/g, '').substring(0, 1).toUpperCase() : '[COLOR]';

            // Format: ODP-[AREA]-[CABLE]-[COLOR]/[SEQ]
            nameInput.value = `ODP-${areaVal}-${cableVal}-${colorVal}/${currentSequence}`;
        }

        areaInput.addEventListener('input', updateNamePreview);
        cableInput.addEventListener('input', updateNamePreview);
        colorInput.addEventListener('input', updateNamePreview);
        
        odcSelect.addEventListener('change', function() {
             fetchSequence(this.value);
        });
        
        // Initial fetch if ODC is selected (e.g. old input)
        if (odcSelect.value) {
            fetchSequence(odcSelect.value);
        }

        var defaultLat = -6.2088;
        var defaultLng = 106.8456;
        var zoom = 13;

        var map = L.map('map-picker').setView([defaultLat, defaultLng], zoom);

        var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        });

        var googleHybrid = L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
            maxZoom: 22,
            subdomains: ['mt0','mt1','mt2','mt3'],
            attribution: '&copy; Google Maps'
        });

        var darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 20,
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
        });

        var currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        if (currentTheme === 'dark') {
            darkLayer.addTo(map);
        } else {
            osm.addTo(map);
        }

        var baseMaps = {
            "Dark Mode": darkLayer,
            "Satellite (Google)": googleHybrid,
            "Street (OSM)": osm
        };
        L.control.layers(baseMaps).addTo(map);

        window.addEventListener('themeChanged', function(e) {
            if (e.detail.theme === 'dark') {
                if (map.hasLayer(osm)) map.removeLayer(osm);
                if (map.hasLayer(googleHybrid)) map.removeLayer(googleHybrid);
                if (!map.hasLayer(darkLayer)) darkLayer.addTo(map);
            } else {
                if (map.hasLayer(darkLayer)) map.removeLayer(darkLayer);
                if (!map.hasLayer(osm) && !map.hasLayer(googleHybrid)) osm.addTo(map);
            }
        });

        var marker;

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 15);
            });
        }

        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            document.getElementById('latitude').value = lat.toFixed(8);
            document.getElementById('longitude').value = lng.toFixed(8);

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });
    });
</script>
@endsection
