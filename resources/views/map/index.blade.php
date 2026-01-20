@extends('layouts.app')

@section('title', __('Map'))

@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="main-card mb-3 card shadow-sm border-0 border-top border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title d-inline-block me-3">{{ __('Distribution Map') }}</h5>
                            <button type="button" class="btn-shadow btn btn-primary btn-sm" id="btnAddOltMode">
                                <i class="fa fa-server me-1"></i> {{ __('Add OLT') }}
                            </button>
                            <button type="button" class="btn-shadow btn btn-warning text-dark btn-sm" id="btnAddOdcMode">
                                <i class="fa fa-plus me-1"></i> {{ __('Add ODC') }}
                            </button>
                            <button type="button" class="btn-shadow btn btn-success btn-sm" id="btnAddOdpMode">
                                <i class="fa fa-plus me-1"></i> {{ __('Add ODP') }}
                            </button>
                            <button type="button" class="btn-shadow btn btn-danger btn-sm d-none" id="btnCancelAdd">
                                <i class="fa fa-times me-1"></i> {{ __('Cancel Add') }}
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2">

                            <button type="button" class="btn-shadow btn btn-info btn-sm" onclick="location.reload()" title="{{ __('Refresh') }}">
                                <i class="fa fa-refresh"></i>
                            </button>
                            <button type="button" class="btn-shadow btn btn-secondary btn-sm" id="btnFullscreen" title="{{ __('Fullscreen') }}">
                                <i class="fa fa-expand"></i>
                            </button>
                        </div>
                    </div>

                    <div id="map" class="border" style="height: 1000px; width: 100%; border-radius: 8px; cursor: default;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- OLT Modal -->
<div class="modal fade" id="oltModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="oltModalLabel">{{ __('Place OLT') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Select an OLT to place on the map:') }}</p>
                <form id="oltForm">
                    <input type="hidden" id="olt_lat" name="latitude">
                    <input type="hidden" id="olt_lng" name="longitude">
                    <div class="mb-3">
                        <label for="olt_select" class="form-label">OLT</label>
                        <select class="form-select" id="olt_select" name="olt_id" required>
                            <option value="">Select OLT</option>
                            @foreach($olts as $olt)
                                <option value="{{ $olt->id }}" data-has-coord="{{ $olt->latitude ? 'true' : 'false' }}">
                                    {{ $olt->name }} ({{ $olt->host }}) {{ $olt->latitude ? '[Mapped]' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="saveOltBtn">{{ __('Place OLT') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- ODC Modal -->
<div class="modal fade" id="odcModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="odcModalLabel">{{ __('Add ODC') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="odcForm">
                    <input type="hidden" id="odc_id" name="id">
                    <div class="mb-3">
                        <label for="odc_name" class="form-label">{{ __('ODC Name') }}</label>
                        <input type="text" class="form-control" id="odc_name" name="name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="odc_pon_port" class="form-label">{{ __('PON Port') }}</label>
                            <input type="text" class="form-control" id="odc_pon_port" name="pon_port" required placeholder="e.g. 01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="odc_area" class="form-label">{{ __('Area') }}</label>
                            <input type="text" class="form-control" id="odc_area" name="area" required placeholder="e.g. CI">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="odc_color" class="form-label">{{ __('Color') }}</label>
                            <input type="text" class="form-control" id="odc_color" name="color" required placeholder="e.g. L">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="odc_cable_no" class="form-label">{{ __('Cable No') }}</label>
                            <input type="text" class="form-control" id="odc_cable_no" name="cable_no" required placeholder="e.g. 01">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="odc_lat" class="form-label">{{ __('Latitude') }}</label>
                            <input type="number" step="any" class="form-control" id="odc_lat" name="latitude" required readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="odc_lng" class="form-label">{{ __('Longitude') }}</label>
                            <input type="number" step="any" class="form-control" id="odc_lng" name="longitude" required readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="odc_capacity" class="form-label">{{ __('Capacity') }}</label>
                        <input type="number" class="form-control" id="odc_capacity" name="capacity" value="48" required>
                    </div>
                    <div class="mb-3">
                        <label for="odc_olt" class="form-label">{{ __('OLT') }}</label>
                        <select class="form-select" id="odc_olt" name="olt_id" required>
                            <option value="">{{ __('Select OLT') }}</option>
                            @foreach($olts as $olt)
                                <option value="{{ $olt->id }}">{{ $olt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="odc_description" class="form-label">{{ __('Description') }}</label>
                        <textarea class="form-control" id="odc_description" name="description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="saveOdcBtn">{{ __('Save ODC') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- ODP Modal -->
<div class="modal fade" id="odpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="odpModalLabel">{{ __('Add ODP') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="odpForm">
                    <input type="hidden" id="odp_id" name="id">
                    <div class="mb-3">
                        <label for="odp_name" class="form-label">{{ __('ODP Name') }}</label>
                        <input type="text" class="form-control" id="odp_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="odp_kampung" class="form-label">{{ __('Kampung') }}</label>
                        <input type="text" class="form-control" id="odp_kampung" name="kampung" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="odp_lat" class="form-label">{{ __('Latitude') }}</label>
                            <input type="number" step="any" class="form-control" id="odp_lat" name="latitude" required readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="odp_lng" class="form-label">{{ __('Longitude') }}</label>
                            <input type="number" step="any" class="form-control" id="odp_lng" name="longitude" required readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="odp_capacity" class="form-label">{{ __('Capacity') }}</label>
                        <input type="number" class="form-control" id="odp_capacity" name="capacity" value="8" required>
                    </div>
                    <div class="mb-3">
                        <label for="odp_region" class="form-label">{{ __('Region') }}</label>
                        <select class="form-select" id="odp_region" name="region_id">
                            <option value="">{{ __('Select Region') }}</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="odp_odc" class="form-label">{{ __('Uplink ODC') }}</label>
                        <select class="form-select" id="odp_odc" name="odc_id">
                            <option value="">{{ __('Select ODC') }}</option>
                            @foreach($odcs as $odc)
                                <option value="{{ $odc->id }}">{{ $odc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="odp_color" class="form-label">{{ __('Uplink Color') }}</label>
                        <select class="form-select" id="odp_color" name="color">
                            <option value="#0000FF" style="color: blue;">Blue (Biru)</option>
                            <option value="#FFA500" style="color: orange;">Orange (Oranye)</option>
                            <option value="#008000" style="color: green;">Green (Hijau)</option>
                            <option value="#A52A2A" style="color: brown;">Brown (Coklat)</option>
                            <option value="#808080" style="color: grey;">Slate (Abu-abu)</option>
                            <option value="#FFFFFF" style="background-color: #ddd;">White (Putih)</option>
                            <option value="#FF0000" style="color: red;">Red (Merah)</option>
                            <option value="#000000">Black (Hitam)</option>
                            <option value="#FFFF00" style="background-color: #333; color: yellow;">Yellow (Kuning)</option>
                            <option value="#EE82EE" style="color: violet;">Violet (Ungu)</option>
                            <option value="#FFC0CB" style="color: pink; background-color: #333;">Rose (Merah Muda)</option>
                            <option value="#40E0D0" style="color: turquoise; background-color: #333;">Aqua (Tosca)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="odp_description" class="form-label">{{ __('Description') }}</label>
                        <textarea class="form-control" id="odp_description" name="description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="saveOdpBtn">{{ __('Save ODP') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<style>
    .custom-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: white;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
    .icon-olt { color: #6f42c1; border-color: #6f42c1; }
    .icon-odc { color: #fd7e14; border-color: #fd7e14; }
    .icon-odp { color: #0dcaf0; border-color: #0dcaf0; }
    .icon-customer-online { color: #198754; border-color: #198754; }
    .icon-customer-offline { color: #dc3545; border-color: #dc3545; }

    /* Animation for online lines */
    .connection-online {
        stroke-dasharray: 10, 10;
        animation: dash 1s linear infinite;
    }
    @keyframes dash {
        to {
            stroke-dashoffset: -20;
        }
    }
</style>
@endpush

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data from Controller
        var customers = @json($customers) || [];
        var odps = @json($odps) || [];
        var odcs = @json($odcs) || [];
        var olts = @json($olts) || [];

        // Initialize map
        // Server Location: -6.800278, 105.939159
        var defaultLat = -6.800278;
        var defaultLng = 105.939159;
        var initialZoom = 15; // Adjusted zoom for better initial view of the area

        function hasCoord(o) {
            return o && typeof o.latitude !== 'undefined' && typeof o.longitude !== 'undefined' && o.latitude !== null && o.longitude !== null;
        }
        function firstWithCoord(arr) {
            for (var i = 0; i < arr.length; i++) {
                if (hasCoord(arr[i])) return arr[i];
            }
            return null;
        }
        // Auto-center logic commented out to prioritize server location
        /*
        var picked = firstWithCoord(customers) || firstWithCoord(olts) || firstWithCoord(odcs);
        if (picked) {
            defaultLat = picked.latitude;
            defaultLng = picked.longitude;
        }
        */

        var map = L.map('map').setView([defaultLat, defaultLng], initialZoom);

        var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        });

        var googleHybrid = L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{
            maxZoom: 22,
            subdomains:['mt0','mt1','mt2','mt3'],
            attribution: '&copy; Google Maps'
        });
        
        var darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 20,
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
        });

        // Determine initial layer based on theme
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

        // Listen for theme changes to auto-switch map layer
        window.addEventListener('themeChanged', function(e) {
            if (e.detail.theme === 'dark') {
                if (map.hasLayer(osm)) map.removeLayer(osm);
                if (map.hasLayer(googleHybrid)) map.removeLayer(googleHybrid);
                if (!map.hasLayer(darkLayer)) darkLayer.addTo(map);
            } else {
                if (map.hasLayer(darkLayer)) map.removeLayer(darkLayer);
                // Default to OSM for light mode, unless user was on Satellite? 
                // For simplicity, switch to OSM.
                if (!map.hasLayer(osm) && !map.hasLayer(googleHybrid)) osm.addTo(map);
            }
        });

        // Feature Groups for bounds
        var markers = L.featureGroup().addTo(map);
        var lines = L.featureGroup().addTo(map);
        var markerMap = {}; // Store markers for easy access

        // Redraw lines function
        function drawLines() {
            lines.clearLayers();

            // OLT -> ODC
            odcs.forEach(function(odc) {
                if (odc.latitude && odc.longitude) {
                    var uplinkOlt = olts.find(o => o.id == odc.olt_id);
                    if (uplinkOlt && uplinkOlt.latitude && uplinkOlt.longitude) {
                        L.polyline([[uplinkOlt.latitude, uplinkOlt.longitude], [odc.latitude, odc.longitude]], {
                            color: '#6f42c1',
                            weight: 4,
                            opacity: 0.7,
                            dashArray: '10, 5'
                        }).addTo(lines);
                    }
                }
            });

            // ODC -> ODP
            odps.forEach(function(odp) {
                if (odp.latitude && odp.longitude) {
                    var uplinkOdc = odcs.find(o => o.id == odp.odc_id);
                    if (uplinkOdc && uplinkOdc.latitude && uplinkOdc.longitude) {
                        L.polyline([[uplinkOdc.latitude, uplinkOdc.longitude], [odp.latitude, odp.longitude]], {
                            color: odp.color || '#fd7e14',
                            weight: 3,
                            opacity: 0.8
                        }).addTo(lines);
                    }
                }
            });

            // ODP -> Customer
            customers.forEach(function(customer) {
                if (customer.latitude && customer.longitude) {
                    var isOnline = customer.is_online;
                    var uplinkOdp = odps.find(o => o.id == customer.odp_id);
                    if (uplinkOdp && uplinkOdp.latitude && uplinkOdp.longitude) {
                        L.polyline([[uplinkOdp.latitude, uplinkOdp.longitude], [customer.latitude, customer.longitude]], {
                            color: '#0000FF',
                            weight: isOnline ? 6 : 4,
                            opacity: isOnline ? 1.0 : 0.8,
                            className: isOnline ? 'connection-online' : ''
                        }).addTo(lines);
                    }
                }
            });
        }

        function deleteLocation(type, id, marker) {
            if (!confirm('Apakah Anda yakin ingin menghapus titik koordinat ini?')) {
                return;
            }

            var url = '';
            var data = { latitude: null, longitude: null, _method: 'PUT' };

            if (type === 'olt') {
                url = `/olt/${id}`; // Note: Route is singular /olt/{id} usually, check routes list if unsure. Usually resource is olts?
                // Wait, Controller route name is olt.index, olt.update. URL is usually /olt/{id} or /olts/{id}
                // Let's assume /olt based on edit link above `/olt/${olt.id}/edit`
                var item = olts.find(i => i.id == id);
                if (item) {
                    data.name = item.name;
                    data.host = item.host;
                    data.port = item.port;
                    data.username = item.username;
                    data.type = item.type;
                    data.brand = item.brand;
                    // Password not needed if nullable on update
                }
            } else if (type === 'odc') {
                url = `/odcs/${id}`;
                var item = odcs.find(i => i.id == id);
                if (item) {
                    data.name = item.name;
                    data.capacity = item.capacity;
                    data.olt_id = item.olt_id;
                    data.description = item.description;
                }
            } else if (type === 'odp') {
                url = `/odps/${id}`;
                var item = odps.find(i => i.id == id);
                if (item) {
                    data.name = item.name;
                    data.capacity = item.capacity;
                    data.region_id = item.region_id;
                    data.odc_id = item.odc_id;
                    data.color = item.color;
                    data.description = item.description;
                }
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                     return response.text().then(text => {
                         let msg = 'Server Error ' + response.status;
                         try {
                             const json = JSON.parse(text);
                             msg = json.message || msg;
                         } catch (e) {
                             msg += ': ' + text.substring(0, 100);
                         }
                         throw new Error(msg);
                     });
                }
                return response.json();
            })
            .then(result => {
                if (result.success || result.id) {
                    if (type === 'olt') {
                        var item = olts.find(i => i.id == id);
                        if (item) { item.latitude = null; item.longitude = null; }
                    } else if (type === 'odc') {
                        var item = odcs.find(i => i.id == id);
                        if (item) { item.latitude = null; item.longitude = null; }
                    } else if (type === 'odp') {
                        var item = odps.find(i => i.id == id);
                        if (item) { item.latitude = null; item.longitude = null; }
                    }
                    
                    map.removeLayer(marker);
                    drawLines();
                    alert('Lokasi berhasil dihapus!');
                } else {
                    var msg = result.message || JSON.stringify(result);
                    if (result.errors) {
                        msg += '\n' + JSON.stringify(result.errors);
                    }
                    alert('Gagal menghapus lokasi: ' + msg);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            });
        }

        // Update Location
        function updateLocation(type, id, lat, lng, oldLat, oldLng, marker) {
            if (!confirm('{{ __('Update location to new coordinates?') }}')) {
                marker.setLatLng([oldLat, oldLng]);
                drawLines(); // Revert lines if needed
                return;
            }

            var url = '';
            var data = {
                latitude: lat,
                longitude: lng,
                _method: 'PUT'
            };

            // Set URL based on type
            if (type === 'olt') {
                url = `/olt/${id}`;
            } else if (type === 'odc') {
                url = `/odcs/${id}`;
            } else if (type === 'odp') {
                url = `/odps/${id}`;
            } else if (type === 'customer') {
                url = `/customers/${id}`;
            }

            fetch(url, {
                method: 'POST', // POST with _method=PUT
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        let msg = 'Server Error ' + response.status;
                        try {
                            const json = JSON.parse(text);
                            msg = json.message || msg;
                        } catch (e) {
                            msg += ': ' + text.substring(0, 100);
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            })
            .then(result => {
                if (result.success || result.id) {
                    // Update internal array
                    if (type === 'olt') {
                        var item = olts.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    } else if (type === 'odc') {
                        var item = odcs.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    } else if (type === 'odp') {
                        var item = odps.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    } else if (type === 'customer') {
                        var item = customers.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    }
                    drawLines(); // Redraw lines with new position
                    alert('{{ __('Location updated!') }}');
                } else {
                    var msg = result.message || JSON.stringify(result);
                    if (result.errors) {
                        msg += '\n' + JSON.stringify(result.errors);
                    }
                    alert('{{ __('Error updating location:') }} ' + msg);
                    marker.setLatLng([oldLat, oldLng]);
                    drawLines();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('Error updating location:') }} ' + error.message);
                marker.setLatLng([oldLat, oldLng]);
                drawLines();
            });
        }

        // Icons
        function createIcon(type) {
            let iconClass = 'fa-user';
            let colorClass = 'icon-customer-offline';
            let size = 26;

            if (type === 'olt') { iconClass = 'fa-server'; colorClass = 'icon-olt'; size = 40; }
            else if (type === 'odc') { iconClass = 'fa-hdd'; colorClass = 'icon-odc'; size = 36; }
            else if (type === 'odp') { iconClass = 'fa-box'; colorClass = 'icon-odp'; size = 32; }
            else if (type === 'online') { iconClass = 'fa-wifi'; colorClass = 'icon-customer-online'; size = 26; }
            else { iconClass = 'fa-user-slash'; colorClass = 'icon-customer-offline'; size = 26; }

            return L.divIcon({
                html: `<i class="fa ${iconClass}" style="font-size: ${size/1.5}px;"></i>`,
                className: `custom-icon ${colorClass}`,
                iconSize: [size, size],
                iconAnchor: [size/2, size/2],
                popupAnchor: [0, -size/2]
            });
        }

        // Draw OLTs
        olts.forEach(function(olt) {
            if (olt.latitude && olt.longitude) {
                var popupContent = document.createElement('div');
                popupContent.innerHTML = `<strong>OLT: ${olt.name}</strong><br>${olt.host}<br>`;
                
                var editLink = document.createElement('a');
                editLink.href = `/olt/${olt.id}/edit`;
                editLink.className = 'btn btn-sm btn-primary text-white mt-2';
                editLink.style.fontSize = '0.8rem';
                editLink.style.padding = '2px 6px';
                editLink.innerText = '{{ __('Edit OLT') }}';
                popupContent.appendChild(editLink);

                var deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-danger mt-2 ms-1';
                deleteBtn.style.fontSize = '0.8rem';
                deleteBtn.style.padding = '2px 6px';
                deleteBtn.innerText = 'Hapus Lokasi';
                deleteBtn.onclick = function() { deleteLocation('olt', olt.id, marker); };
                popupContent.appendChild(deleteBtn);

                var marker = L.marker([olt.latitude, olt.longitude], {
                    icon: createIcon('olt'),
                    draggable: true
                }).bindPopup(popupContent).addTo(markers);

                var oldLat = olt.latitude;
                var oldLng = olt.longitude;

                marker.on('dragstart', function(e) {
                    oldLat = e.target.getLatLng().lat;
                    oldLng = e.target.getLatLng().lng;
                });

                marker.on('dragend', function(e) {
                    var newLat = e.target.getLatLng().lat;
                    var newLng = e.target.getLatLng().lng;
                    updateLocation('olt', olt.id, newLat, newLng, oldLat, oldLng, marker);
                });
            }
        });

        // Draw ODCs
        odcs.forEach(function(odc) {
            if (odc.latitude && odc.longitude) {
                var oltName = 'N/A';
                if (odc.olt_id) {
                    var olt = olts.find(o => o.id == odc.olt_id);
                    if (olt) oltName = olt.name;
                }

                var popupContent = document.createElement('div');
                popupContent.innerHTML = `
                    <div style="min-width: 200px;">
                        <h6 class="mb-2">ODC: ${odc.name}</h6>
                        <table class="table table-sm table-borderless mb-2" style="font-size: 0.85rem;">
                            <tr><td class="p-0 text-muted">Capacity:</td><td class="p-0 text-end">${odc.capacity}</td></tr>
                            <tr><td class="p-0 text-muted">OLT:</td><td class="p-0 text-end">${oltName}</td></tr>
                            <tr><td class="p-0 text-muted">PON Port:</td><td class="p-0 text-end">${odc.pon_port || '-'}</td></tr>
                            <tr><td class="p-0 text-muted">Area:</td><td class="p-0 text-end">${odc.area || '-'}</td></tr>
                            <tr><td class="p-0 text-muted">Color:</td><td class="p-0 text-end">${odc.color || '-'}</td></tr>
                            <tr><td class="p-0 text-muted">Cable No:</td><td class="p-0 text-end">${odc.cable_no || '-'}</td></tr>
                        </table>
                        <div class="text-muted small mb-2" style="font-style: italic;">${odc.description || ''}</div>
                    </div>`;
                
                var editBtn = document.createElement('button');
                editBtn.className = 'btn btn-sm btn-primary mt-2';
                editBtn.style.fontSize = '0.8rem';
                editBtn.style.padding = '2px 6px';
                editBtn.innerText = '{{ __('Edit ODC') }}';
                editBtn.onclick = function() { editOdc(odc.id); };
                popupContent.appendChild(editBtn);

                var deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-danger mt-2 ms-1';
                deleteBtn.style.fontSize = '0.8rem';
                deleteBtn.style.padding = '2px 6px';
                deleteBtn.innerText = 'Hapus Lokasi';
                deleteBtn.onclick = function() { deleteLocation('odc', odc.id, marker); };
                popupContent.appendChild(deleteBtn);

                var marker = L.marker([odc.latitude, odc.longitude], {
                    icon: createIcon('odc'),
                    draggable: true
                }).bindPopup(popupContent).addTo(markers);

                var oldLat = odc.latitude;
                var oldLng = odc.longitude;

                marker.on('dragstart', function(e) {
                    oldLat = e.target.getLatLng().lat;
                    oldLng = e.target.getLatLng().lng;
                });

                marker.on('dragend', function(e) {
                    var newLat = e.target.getLatLng().lat;
                    var newLng = e.target.getLatLng().lng;
                    updateLocation('odc', odc.id, newLat, newLng, oldLat, oldLng, marker);
                });
            }
        });

        // Draw ODPs
        odps.forEach(function(odp) {
            if (odp.latitude && odp.longitude) {
                var odcName = 'N/A';
                if (odp.odc_id) {
                    var odc = odcs.find(o => o.id == odp.odc_id);
                    if (odc) odcName = odc.name;
                }

                var popupContent = document.createElement('div');
                popupContent.innerHTML = `
                    <div style="min-width: 200px;">
                        <h6 class="mb-2">ODP: ${odp.name}</h6>
                        <table class="table table-sm table-borderless mb-2" style="font-size: 0.85rem;">
                            <tr><td class="p-0 text-muted">Capacity:</td><td class="p-0 text-end">${odp.filled || 0}/${odp.capacity}</td></tr>
                            <tr><td class="p-0 text-muted">ODC:</td><td class="p-0 text-end">${odcName}</td></tr>
                            <tr><td class="p-0 text-muted">Area:</td><td class="p-0 text-end">${odp.kampung || '-'}</td></tr>
                            <tr><td class="p-0 text-muted">Color:</td><td class="p-0 text-end">${odp.color || '-'}</td></tr>
                        </table>
                        <div class="text-muted small mb-2" style="font-style: italic;">${odp.description || ''}</div>
                    </div>`;
                
                var editBtn = document.createElement('button');
                editBtn.className = 'btn btn-sm btn-primary mt-2';
                editBtn.style.fontSize = '0.8rem';
                editBtn.style.padding = '2px 6px';
                editBtn.innerText = '{{ __('Edit ODP') }}';
                editBtn.onclick = function() { editOdp(odp.id); };
                popupContent.appendChild(editBtn);

                var deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-danger mt-2 ms-1';
                deleteBtn.style.fontSize = '0.8rem';
                deleteBtn.style.padding = '2px 6px';
                deleteBtn.innerText = 'Hapus Lokasi';
                deleteBtn.onclick = function() { deleteLocation('odp', odp.id, marker); };
                popupContent.appendChild(deleteBtn);

                var marker = L.marker([odp.latitude, odp.longitude], {
                    icon: createIcon('odp'),
                    draggable: true
                }).bindPopup(popupContent).addTo(markers);

                var oldLat = odp.latitude;
                var oldLng = odp.longitude;

                marker.on('dragstart', function(e) {
                    oldLat = e.target.getLatLng().lat;
                    oldLng = e.target.getLatLng().lng;
                });

                marker.on('dragend', function(e) {
                    var newLat = e.target.getLatLng().lat;
                    var newLng = e.target.getLatLng().lng;
                    updateLocation('odp', odp.id, newLat, newLng, oldLat, oldLng, marker);
                });
            }
        });

        // Draw Customers
        customers.forEach(function(customer) {
            var isOnline = customer.is_online; // Assumed passed from controller
            var iconType = isOnline ? 'online' : 'offline';
            var rxPower = customer.rx_power ? customer.rx_power + ' dBm' : 'N/A';
            var genieName = customer.genie_name || '-';

            // Find ODP name
            var odpName = 'N/A';
            if (customer.odp_id) {
                var odp = odps.find(o => o.id == customer.odp_id);
                if (odp) odpName = odp.name;
            } else if (customer.odp) {
                odpName = customer.odp.name || customer.odp; 
            }
            
            var marker = L.marker([customer.latitude, customer.longitude], {
                icon: createIcon(iconType),
                draggable: true
            })
            .addTo(markers)
            .bindPopup(
                `<div style="min-width: 200px;">` +
                `<h6 class="mb-2">${customer.name}</h6>` +
                `<div class="mb-2">` +
                `<span class="badge ${isOnline ? 'bg-success' : 'bg-danger'} me-1">${isOnline ? 'Online' : 'Offline'}</span>` +
                `<span class="badge bg-secondary">${customer.status}</span>` +
                `</div>` +
                `<table class="table table-sm table-borderless mb-2" style="font-size: 0.85rem;">` +
                `<tr><td class="p-0 text-muted">ID:</td><td class="p-0 text-end">${customer.id}</td></tr>` +
                `<tr><td class="p-0 text-muted">Address:</td><td class="p-0 text-end text-truncate" style="max-width: 150px;">${customer.address || '-'}</td></tr>` +
                `<tr><td class="p-0 text-muted">Phone:</td><td class="p-0 text-end">${customer.phone || '-'}</td></tr>` +
                `<tr><td class="p-0 text-muted">Package:</td><td class="p-0 text-end">${customer.package || '-'}</td></tr>` +
                `<tr><td class="p-0 text-muted">ODP:</td><td class="p-0 text-end">${odpName}</td></tr>` +
                `<tr><td class="p-0 text-muted">SN:</td><td class="p-0 text-end font-monospace small">${customer.onu_serial || '-'}</td></tr>` +
                `<tr><td class="p-0 text-muted">Genie Name:</td><td class="p-0 text-end">${genieName}</td></tr>` +
                `<tr><td class="p-0 text-muted">RX Power:</td><td class="p-0 text-end fw-bold">${rxPower}</td></tr>` +
                `</table>` +
                `<div class="d-flex gap-2 mt-2">` +
                `<a href="/customers/${customer.id}" class="btn btn-sm btn-info text-white" style="font-size: 0.8rem; padding: 2px 6px;">Details</a>` +
                `<a href="/customers/${customer.id}/edit" class="btn btn-sm btn-primary text-white" style="font-size: 0.8rem; padding: 2px 6px;">Edit</a>` +
                `</div></div>`
            );

            var oldLat = customer.latitude;
            var oldLng = customer.longitude;

            marker.on('dragstart', function(e) {
                oldLat = e.target.getLatLng().lat;
                oldLng = e.target.getLatLng().lng;
            });

            marker.on('dragend', function(e) {
                var newLat = e.target.getLatLng().lat;
                var newLng = e.target.getLatLng().lng;
                updateLocation('customer', customer.id, newLat, newLng, oldLat, oldLng, marker);
            });
        });

        // Initial line draw
        drawLines();

        // Fit bounds - Disabled to respect default center/zoom
        /*
        if (markers.getLayers().length > 0) {
            map.fitBounds(markers.getBounds().pad(0.1));
        }
        */

        // --- Add Mode Logic ---
        var addMode = null; // 'olt', 'odc', 'odp', or null
        var btnAddOlt = document.getElementById('btnAddOltMode');
        var btnAddOdc = document.getElementById('btnAddOdcMode');
        var btnAddOdp = document.getElementById('btnAddOdpMode');
        var btnCancel = document.getElementById('btnCancelAdd');
        var mapContainer = document.getElementById('map');

        function setMode(mode) {
            addMode = mode;
            if (mode) {
                mapContainer.style.cursor = 'crosshair';
                btnCancel.classList.remove('d-none');
                btnAddOlt.disabled = true;
                btnAddOdc.disabled = true;
                btnAddOdp.disabled = true;
            } else {
                mapContainer.style.cursor = 'default';
                btnCancel.classList.add('d-none');
                btnAddOlt.disabled = false;
                btnAddOdc.disabled = false;
                btnAddOdp.disabled = false;
            }
        }

        btnAddOlt.addEventListener('click', function() { setMode('olt'); });
        btnAddOdc.addEventListener('click', function() { setMode('odc'); });
        btnAddOdp.addEventListener('click', function() { setMode('odp'); });
        btnCancel.addEventListener('click', function() { setMode(null); });

        map.on('click', function(e) {
            if (!addMode) return;

            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            if (addMode === 'olt') {
                document.getElementById('oltForm').reset();
                document.getElementById('olt_lat').value = lat;
                document.getElementById('olt_lng').value = lng;
                // Filter dropdown if needed, or just show modal
                var oltModal = new bootstrap.Modal(document.getElementById('oltModal'));
                oltModal.show();
            } else if (addMode === 'odc') {
                document.getElementById('odcForm').reset(); // Reset form
                document.getElementById('odc_id').value = ''; // Clear ID for new
                document.getElementById('odc_lat').value = lat;
                document.getElementById('odc_lng').value = lng;
                document.getElementById('odcModalLabel').innerText = '{{ __('Add ODC') }}'; // Set title
                var odcModal = new bootstrap.Modal(document.getElementById('odcModal'));
                odcModal.show();
            } else if (addMode === 'odp') {
                document.getElementById('odpForm').reset(); // Reset form
                document.getElementById('odp_id').value = ''; // Clear ID for new
                document.getElementById('odp_lat').value = lat;
                document.getElementById('odp_lng').value = lng;
                document.getElementById('odpModalLabel').innerText = '{{ __('Add ODP') }}'; // Set title
                var odpModal = new bootstrap.Modal(document.getElementById('odpModal'));
                odpModal.show();
            }
            
            setMode(null); // Reset mode after click
        });

        // --- Edit Functions ---
        window.editOdc = function(id) {
            var odc = odcs.find(o => o.id == id);
            if (odc) {
                document.getElementById('odc_id').value = odc.id;
                document.getElementById('odc_name').value = odc.name;
                document.getElementById('odc_lat').value = odc.latitude;
                document.getElementById('odc_lng').value = odc.longitude;
                document.getElementById('odc_capacity').value = odc.capacity;
                document.getElementById('odc_olt').value = odc.olt_id;
                document.getElementById('odc_description').value = odc.description || '';
                
                // New Fields
                document.getElementById('odc_pon_port').value = odc.pon_port || '';
                document.getElementById('odc_area').value = odc.area || '';
                document.getElementById('odc_color').value = odc.color || '';
                document.getElementById('odc_cable_no').value = odc.cable_no || '';

                document.getElementById('odcModalLabel').innerText = '{{ __('Edit ODC') }}';
                var odcModal = new bootstrap.Modal(document.getElementById('odcModal'));
                odcModal.show();
            }
        };

        window.editOdp = function(id) {
            var odp = odps.find(o => o.id == id);
            if (odp) {
                document.getElementById('odp_id').value = odp.id;
                document.getElementById('odp_name').value = odp.name;
                document.getElementById('odp_lat').value = odp.latitude;
                document.getElementById('odp_lng').value = odp.longitude;
                document.getElementById('odp_capacity').value = odp.capacity;
                document.getElementById('odp_region').value = odp.region_id;
                document.getElementById('odp_odc').value = odp.odc_id;
                document.getElementById('odp_color').value = odp.color || '#fd7e14';
                document.getElementById('odp_description').value = odp.description || '';
                
                // New Fields
                document.getElementById('odp_kampung').value = odp.kampung || '';

                document.getElementById('odpModalLabel').innerText = '{{ __('Edit ODP') }}';
                var odpModal = new bootstrap.Modal(document.getElementById('odpModal'));
                odpModal.show();
            }
        };

        // --- Save Functions (AJAX) ---
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.getElementById('saveOltBtn').addEventListener('click', function() {
            var id = document.getElementById('olt_select').value;
            if (!id) {
                alert('{{ __('Please select an OLT') }}');
                return;
            }

            var lat = document.getElementById('olt_lat').value;
            var lng = document.getElementById('olt_lng').value;
            var item = olts.find(i => i.id == id);
            
            if (!item) {
                 alert('{{ __('OLT not found') }}');
                 return;
            }

            // We are just updating the location, so we use the update endpoint
            var url = `/olt/${id}`;
            var data = {
                name: item.name,
                host: item.host,
                port: item.port,
                username: item.username,
                type: item.type,
                brand: item.brand,
                latitude: lat,
                longitude: lng,
                _method: 'PUT'
            };

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.id) {
                    location.reload();
                } else {
                    alert('{{ __('Error placing OLT:') }} ' + (data.message || JSON.stringify(data)));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('An error occurred while saving.') }}');
            });
        });

        document.getElementById('saveOdcBtn').addEventListener('click', function() {
            var id = document.getElementById('odc_id').value;
            var url = id ? `/odcs/${id}` : '/odcs';
            var method = id ? 'PUT' : 'POST';
            var formData = new FormData(document.getElementById('odcForm'));
            var data = Object.fromEntries(formData.entries());

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.id) { // Some controllers return full object
                    location.reload();
                } else {
                    alert('{{ __('Error saving ODC:') }} ' + (data.message || JSON.stringify(data)));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('An error occurred while saving.') }}');
            });
        });

        document.getElementById('saveOdpBtn').addEventListener('click', function() {
            var id = document.getElementById('odp_id').value;
            var url = id ? `/odps/${id}` : '/odps';
            var method = id ? 'PUT' : 'POST';
            var formData = new FormData(document.getElementById('odpForm'));
            var data = Object.fromEntries(formData.entries());

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.id) {
                    location.reload();
                } else {
                    alert('{{ __('Error saving ODP:') }} ' + (data.message || JSON.stringify(data)));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('An error occurred while saving.') }}');
            });
        });

        // Fullscreen
        document.getElementById('btnFullscreen').addEventListener('click', function() {
            var mapElement = document.getElementById('map');
            if (!document.fullscreenElement) {
                mapElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        });
    });
</script>
@endpush
