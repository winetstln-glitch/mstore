@extends('layouts.app')

@section('title', __('Edit Closure'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Edit Closure') }}</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('closures.update', $closure) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" class="block mt-1 w-full form-control" type="text" name="name" :value="old('name', $closure->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="mb-3">
                        <x-input-label for="coordinates" :value="__('Coordinates (Lat, Lng)')" />
                        <div class="input-group">
                            <x-text-input id="coordinates" class="form-control" type="text" name="coordinates" :value="old('coordinates', $closure->coordinates)" placeholder="-6.123456, 106.123456" />
                            <button class="btn btn-outline-secondary" type="button" id="btn-get-location"><i class="fa-solid fa-location-crosshairs"></i> Current</button>
                        </div>
                        <div class="form-text">{{ __('Click on the map below or use button to select location.') }}</div>
                        <x-input-error :messages="$errors->get('coordinates')" class="mt-2" />
                    </div>

                    <div id="map-picker" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;" class="mb-3"></div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <x-input-label for="parent_type" :value="__('Parent Type')" />
                            <select id="parent_type" name="parent_type" class="form-select mt-1">
                                <option value="">{{ __('Select Parent Type') }}</option>
                                <option value="App\Models\Olt" {{ old('parent_type', $closure->parent_type) == 'App\Models\Olt' ? 'selected' : '' }}>OLT</option>
                                <option value="App\Models\Odc" {{ old('parent_type', $closure->parent_type) == 'App\Models\Odc' ? 'selected' : '' }}>ODC</option>
                            </select>
                            <x-input-error :messages="$errors->get('parent_type')" class="mt-2" />
                        </div>
                        <div class="col-md-6">
                            <x-input-label for="parent_id" :value="__('Parent Node')" />
                            
                            <select id="parent_olt" class="form-select mt-1 parent-selector" style="display: none;">
                                <option value="">{{ __('Select OLT') }}</option>
                                @foreach($olts as $olt)
                                    <option value="{{ $olt->id }}" {{ old('parent_id', ($closure->parent_type == 'App\Models\Olt' ? $closure->parent_id : '')) == $olt->id ? 'selected' : '' }}>{{ $olt->name }}</option>
                                @endforeach
                            </select>

                            <select id="parent_odc" class="form-select mt-1 parent-selector" style="display: none;">
                                <option value="">{{ __('Select ODC') }}</option>
                                @foreach($odcs as $odc)
                                    <option value="{{ $odc->id }}" {{ old('parent_id', ($closure->parent_type == 'App\Models\Odc' ? $closure->parent_id : '')) == $odc->id ? 'selected' : '' }}>{{ $odc->name }}</option>
                                @endforeach
                            </select>

                            <input type="hidden" name="parent_id" id="parent_id_input" value="{{ old('parent_id', $closure->parent_id) }}">
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" class="form-control mt-1" rows="3">{{ old('description', $closure->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('closures.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const parentTypeSelect = document.getElementById('parent_type');
        const parentOltSelect = document.getElementById('parent_olt');
        const parentOdcSelect = document.getElementById('parent_odc');
        const parentIdInput = document.getElementById('parent_id_input');

        // Store initial parent ID to restore if type matches
        let currentParentId = '{{ old('parent_id', $closure->parent_id) }}';

        function updateParentSelect() {
            const type = parentTypeSelect.value;
            
            // Hide all first
            parentOltSelect.style.display = 'none';
            parentOdcSelect.style.display = 'none';

            if (type === 'App\\Models\\Olt') {
                parentOltSelect.style.display = 'block';
                if (currentParentId) parentOltSelect.value = currentParentId;
            } else if (type === 'App\\Models\\Odc') {
                parentOdcSelect.style.display = 'block';
                if (currentParentId) parentOdcSelect.value = currentParentId;
            }
        }

        parentTypeSelect.addEventListener('change', function() {
            // Only clear if type actually changed from initial load or user selection
            // But here we can just clear value to force re-selection
            parentOltSelect.value = '';
            parentOdcSelect.value = '';
            parentIdInput.value = ''; 
            currentParentId = ''; // Reset tracked ID
            updateParentSelect();
        });

        parentOltSelect.addEventListener('change', function() {
            parentIdInput.value = this.value;
            currentParentId = this.value;
        });

        parentOdcSelect.addEventListener('change', function() {
            parentIdInput.value = this.value;
            currentParentId = this.value;
        });

        // Initialize
        updateParentSelect();

        // Map Logic
        var defaultLat = -6.2088;
        var defaultLng = 106.8456;
        var zoom = 15;

        // Try to parse existing coordinates
        const coordInput = document.getElementById('coordinates');
        if (coordInput.value) {
            const parts = coordInput.value.split(',');
            if (parts.length === 2) {
                defaultLat = parseFloat(parts[0].trim());
                defaultLng = parseFloat(parts[1].trim());
            }
        }

        var map = L.map('map-picker').setView([defaultLat, defaultLng], zoom);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var marker = null;
        if (coordInput.value) {
            marker = L.marker([defaultLat, defaultLng]).addTo(map);
        }

        map.on('click', function(e) {
            var lat = e.latlng.lat.toFixed(6);
            var lng = e.latlng.lng.toFixed(6);
            
            coordInput.value = lat + ', ' + lng;

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });

        // Current Location Button
        document.getElementById('btn-get-location').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude.toFixed(6);
                    var lng = position.coords.longitude.toFixed(6);
                    
                    coordInput.value = lat + ', ' + lng;
                    
                    var latLng = [lat, lng];
                    map.setView(latLng, 15);
                    
                    if (marker) {
                        marker.setLatLng(latLng);
                    } else {
                        marker = L.marker(latLng).addTo(map);
                    }
                });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        });
    });
</script>
@endpush
@endsection
