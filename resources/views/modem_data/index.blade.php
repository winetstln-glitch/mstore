@extends('layouts.app')

@section('title', 'Sistem Pendataan Modem')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    /* Prevent Tailwind's .collapse utility from overriding Bootstrap sidebar collapses */
    #sidebar-wrapper .collapse,
    #sidebar-wrapper .collapsing {
        visibility: visible !important;
    }

    #reader {
        width: 100%;
        border-radius: 8px;
        overflow: hidden;
        background: #000;
    }
    #reader video {
        filter: brightness(1.2) contrast(1.15) saturate(1.05);
    }
    #map {
        height: 300px;
        width: 100%;
        border-radius: 0.5rem;
        z-index: 1;
    }
    .scanner-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 2050;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen pb-12">
    <div class="max-w-md mx-auto bg-white shadow-lg min-h-screen md:min-h-0 md:mt-10 md:rounded-xl overflow-hidden">
        <div class="bg-blue-600 p-6 text-white text-center">
            <h1 class="text-2xl font-bold">Data Instalasi Modem</h1>
            <p class="text-blue-100 text-sm">Input data teknis dan lokasi pelanggan</p>
        </div>

        <form id="modemForm" method="POST" action="{{ route('modem-data.store') }}" class="p-6 space-y-4">
            @csrf
            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pelanggan</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </span>
                    <input type="text" id="customerName" name="customer_name" value="{{ old('customer_name') }}" required class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Masukkan nama pelanggan">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Modem</label>
                <select id="modemType" name="modem_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                    <option value="">Pilih Tipe</option>
                    @forelse(($modemTypes ?? []) as $modemType)
                        <option value="{{ $modemType }}" {{ old('modem_type') === $modemType ? 'selected' : '' }}>{{ $modemType }}</option>
                    @empty
                        <option value="" disabled>Data jenis ONU belum tersedia di material</option>
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">MAC Address</label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i data-lucide="hash" class="w-5 h-5"></i>
                        </span>
                        <input type="text" id="macAddress" name="mac_address" value="{{ old('mac_address') }}" required class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="00:00:00:00:00:00">
                    </div>
                    <button type="button" onclick="openScanner('macAddress')" class="bg-gray-100 p-2 rounded-lg border border-gray-300 hover:bg-gray-200">
                        <i data-lucide="scan-barcode" class="w-6 h-6 text-gray-600"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number (SN)</label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i data-lucide="barcode" class="w-5 h-5"></i>
                        </span>
                        <input type="text" id="serialNumber" name="serial_number" value="{{ old('serial_number') }}" required class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="SN123456789">
                    </div>
                    <button type="button" onclick="openScanner('serialNumber')" class="bg-gray-100 p-2 rounded-lg border border-gray-300 hover:bg-gray-200">
                        <i data-lucide="scan-barcode" class="w-6 h-6 text-gray-600"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi (GPS)</label>
                <div id="map" class="mb-2"></div>
                <div class="flex gap-2">
                    <input type="text" id="locationCoords" name="coordinates" value="{{ old('coordinates') }}" readonly class="flex-1 text-xs bg-gray-50 p-2 border border-gray-300 rounded-lg outline-none" placeholder="Koordinat belum ditentukan">
                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">
                    <button type="button" onclick="getCurrentLocation()" class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg border border-blue-200 text-sm font-medium flex items-center gap-1 hover:bg-blue-100">
                        <i data-lucide="map-pin" class="w-4 h-4"></i> Get GPS
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Gunakan tombol "Pilih di Peta" untuk mengambil koordinat dari fitur peta jaringan.</p>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg mt-4 shadow-md hover:bg-blue-700 transition active:scale-[0.98]">
                Simpan Data
            </button>
        </form>

    </div>

    <div id="scannerModal" class="scanner-modal">
        <div class="bg-white p-4 rounded-xl w-[90%] max-w-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-gray-800">Scan Barcode</h3>
                <button type="button" onclick="closeScanner()" class="p-1 hover:bg-gray-100 rounded-full">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="reader"></div>
            <p class="text-center text-sm text-gray-500 mt-4">Arahkan kamera ke barcode/QR pada modem</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let activeTargetInput = '';
    let html5QrCode = null;
    let currentVideoTrack = null;
    let map = null;
    let marker = null;
    let mapPickerWindow = null;
    const oldLatitude = {{ old('latitude') !== null ? (float) old('latitude') : 'null' }};
    const oldLongitude = {{ old('longitude') !== null ? (float) old('longitude') : 'null' }};

    function initIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function updateLocationInput(lat, lng) {
        document.getElementById('locationCoords').value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        document.getElementById('latitude').value = lat.toFixed(7);
        document.getElementById('longitude').value = lng.toFixed(7);
    }

    function initMap() {
        const defaultLat = Number.isFinite(oldLatitude) ? oldLatitude : -6.2088;
        const defaultLng = Number.isFinite(oldLongitude) ? oldLongitude : 106.8456;
        map = L.map('map').setView([defaultLat, defaultLng], 13);

        const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        });

        const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 22,
            attribution: '&copy; Google Maps'
        });

        const darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 20,
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
        });

        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        if (currentTheme === 'dark') {
            darkLayer.addTo(map);
        } else {
            osm.addTo(map);
        }

        const baseMaps = {
            "Dark Mode": darkLayer,
            "Satellite (Google)": googleHybrid,
            "Street (OSM)": osm
        };
        L.control.layers(baseMaps).addTo(map);

        window.addEventListener('themeChanged', function (e) {
            if (e.detail.theme === 'dark') {
                if (map.hasLayer(osm)) map.removeLayer(osm);
                if (map.hasLayer(googleHybrid)) map.removeLayer(googleHybrid);
                if (!map.hasLayer(darkLayer)) darkLayer.addTo(map);
            } else {
                if (map.hasLayer(darkLayer)) map.removeLayer(darkLayer);
                if (!map.hasLayer(osm) && !map.hasLayer(googleHybrid)) osm.addTo(map);
            }
        });

        marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
        if (Number.isFinite(oldLatitude) && Number.isFinite(oldLongitude)) {
            updateLocationInput(oldLatitude, oldLongitude);
        }

        marker.on('dragend', function () {
            const pos = marker.getLatLng();
            updateLocationInput(pos.lat, pos.lng);
        });

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            updateLocationInput(e.latlng.lat, e.latlng.lng);
        });
    }

    function getCurrentLocation() {
        if (!navigator.geolocation) {
            alert('Geolocation tidak didukung browser ini.');
            return;
        }

        navigator.geolocation.getCurrentPosition((position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            map.setView([lat, lng], 17);
            marker.setLatLng([lat, lng]);
            updateLocationInput(lat, lng);
        }, (err) => {
            alert('Gagal mendapatkan lokasi: ' + err.message);
        });
    }

    function openNetworkMapPicker() {
        const pickerUrl = '{{ route('map.index') }}?picker=1';
        mapPickerWindow = window.open(pickerUrl, 'mstoreMapPicker', 'width=1280,height=800');
        if (!mapPickerWindow) {
            alert('Popup diblokir browser. Izinkan popup untuk memilih lokasi dari peta.');
        }
    }

    function normalizeMac(value) {
        const clean = String(value || '').replace(/[^0-9A-Fa-f]/g, '').toUpperCase();
        if (clean.length !== 12) {
            return String(value || '').trim();
        }
        return clean.match(/.{1,2}/g).join(':');
    }

    async function applyTrackConstraints(track, constraints) {
        if (!track || typeof track.applyConstraints !== 'function') {
            return false;
        }
        try {
            await track.applyConstraints(constraints);
            return true;
        } catch (err) {
            return false;
        }
    }

    async function setupScannerTrack() {
        currentVideoTrack = html5QrCode?.getRunningTrack?.() || null;

        if (!currentVideoTrack) {
            return;
        }

        const baseOptimizations = [
            { advanced: [{ focusMode: 'continuous' }] },
            { advanced: [{ exposureMode: 'continuous' }] },
            { advanced: [{ whiteBalanceMode: 'continuous' }] },
            { advanced: [{ sharpness: 1.0 }] },
            { advanced: [{ brightness: 0.2 }] },
            { advanced: [{ contrast: 0.3 }] }
        ];

        for (const constraint of baseOptimizations) {
            await applyTrackConstraints(currentVideoTrack, constraint);
        }
    }

    async function openScanner(targetId) {
        activeTargetInput = targetId;
        document.getElementById('scannerModal').style.display = 'flex';

        if (html5QrCode && html5QrCode.isScanning) {
            await closeScanner();
        }

        html5QrCode = new Html5Qrcode('reader');
        const scannerFormats = [];
        if (window.Html5QrcodeSupportedFormats) {
            scannerFormats.push(
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E
            );
        }
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.333334,
            disableFlip: true
        };
        if (scannerFormats.length > 0) {
            config.formatsToSupport = scannerFormats;
        }
        const onDecoded = async (decodedText) => {
            const finalValue = activeTargetInput === 'macAddress' ? normalizeMac(decodedText) : decodedText;
            document.getElementById(activeTargetInput).value = finalValue;
            await closeScanner();
        };
        const onDecodeError = () => {};
        const startByConstraints = async (constraints) => {
            await html5QrCode.start(constraints, config, onDecoded, onDecodeError);
        };

        try {
            const cameras = (typeof Html5Qrcode.getCameras === 'function')
                ? await Html5Qrcode.getCameras()
                : [];
            const sortedCameras = Array.isArray(cameras) ? [...cameras].sort((a, b) => {
                const backRegex = /(back|rear|environment|belakang|traseira|trasera)/i;
                const aBack = backRegex.test(String(a?.label || '')) ? 1 : 0;
                const bBack = backRegex.test(String(b?.label || '')) ? 1 : 0;
                return bBack - aBack;
            }) : [];
            let started = false;
            for (const camera of sortedCameras) {
                try {
                    await startByConstraints(camera.id);
                    started = true;
                    break;
                } catch (cameraError) {}
            }
            if (!started) {
                await startByConstraints({
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                });
            }
            await setupScannerTrack();
        } catch (err) {
            try {
                await startByConstraints({
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                });
                await setupScannerTrack();
            } catch (fallbackErr) {
                console.error('Gagal memulai kamera', fallbackErr);
                alert('Gagal membuka kamera untuk scan barcode.');
            }
        }
    }

    async function closeScanner() {
        try {
            if (html5QrCode && html5QrCode.isScanning) {
                await html5QrCode.stop();
                await html5QrCode.clear();
            }
        } catch (err) {
            console.warn('Scanner stop error:', err);
        } finally {
            currentVideoTrack = null;
            document.getElementById('scannerModal').style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initIcons();
        initMap();
        const macInput = document.getElementById('macAddress');
        if (macInput) {
            macInput.addEventListener('blur', function () {
                macInput.value = normalizeMac(macInput.value);
            });
        }

        window.addEventListener('message', function (event) {
            if (event.origin !== window.location.origin) {
                return;
            }
            const payload = event.data || {};
            if (payload.type !== 'mstore-map-picked') {
                return;
            }

            const lat = Number(payload.lat);
            const lng = Number(payload.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 17);
            updateLocationInput(lat, lng);
        });
        document.getElementById('modemForm').addEventListener('submit', function () {
            if (macInput) {
                macInput.value = normalizeMac(macInput.value);
            }
        });
    });
</script>
@endpush
