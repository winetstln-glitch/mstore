@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Tambah Tiket Baru') }}</h5>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali ke Daftar') }}
                </a>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('tickets.store') }}">
                    @csrf

                    <div class="row g-3 mb-4">
                        <!-- Tipe -->
                        <div class="col-md-12">
                            <label for="type" class="form-label">{{ __('Jenis Tiket') }}</label>
                            <select name="type" id="type" required class="form-select @error('type') is-invalid @enderror">
                                <option value="gangguan" {{ old('type') == 'gangguan' ? 'selected' : '' }}>{{ __('Gangguan') }}</option>
                                <option value="pasang_baru" {{ old('type') == 'pasang_baru' ? 'selected' : '' }}>{{ __('Pasang Baru') }}</option>
                                <option value="pasang_odc" {{ old('type') == 'pasang_odc' ? 'selected' : '' }}>{{ __('Instalasi') }}</option>
                                <option value="tarik_jalur" {{ old('type') == 'tarik_jalur' ? 'selected' : '' }}>{{ __('Tarik Jalur') }}</option>
                                <option value="perbaikan" {{ old('type') == 'perbaikan' ? 'selected' : '' }}>{{ __('Perbaikan') }}</option>
                                <option value="maintenance" {{ old('type') == 'maintenance' ? 'selected' : '' }}>{{ __('Pemeliharaan') }}</option>
                                <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>{{ __('Lainnya') }}</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Pilih pelanggan yang sudah ada -->
                        <div class="col-12" id="existing-customer-section">
                            <label for="customer_id" class="form-label">{{ __('Pelanggan') }}</label>
                            <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                <option value="">{{ __('Pilih Pelanggan') }}</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" 
                                        data-lat="{{ $customer->latitude }}" 
                                        data-lng="{{ $customer->longitude }}"
                                        data-address="{{ $customer->address }}"
                                        {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} - {{ $customer->address }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Form pelanggan baru -->
                        <div id="new-customer-section" class="col-12" style="display: none;">
                            <div class="card  border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-user-plus me-1"></i> {{ __('Data Pelanggan Baru') }}</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="new_customer_name" class="form-label">{{ __('Nama') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="new_customer_name" name="new_customer_name" value="{{ old('new_customer_name') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_customer_phone" class="form-label">{{ __('Nomor HP') }}</label>
                                            <input type="text" class="form-control" id="new_customer_phone" name="new_customer_phone" value="{{ old('new_customer_phone') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_customer_modem_type" class="form-label">{{ __('Type Modem') }}</label>
                                            <input type="text" class="form-control" id="new_customer_modem_type" name="new_customer_modem_type" value="{{ old('new_customer_modem_type') }}" placeholder="{{ __('Contoh: ZTE F609') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_customer_onu_serial" class="form-label">{{ __('SN Modem/ONU') }}</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control @error('new_customer_onu_serial') is-invalid @enderror" id="new_customer_onu_serial" name="new_customer_onu_serial" value="{{ old('new_customer_onu_serial') }}" placeholder="{{ __('Contoh: ZTEGC1234567') }}">
                                                <button class="btn btn-outline-primary" type="button" id="startCreateOnuQrScan">
                                                    <i class="fa-solid fa-qrcode me-1"></i>{{ __('Scan SN') }}
                                                </button>
                                            </div>
                                            @error('new_customer_onu_serial')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div id="createOnuQrScanStatus" class="small text-muted mt-2"></div>
                                            <div id="createOnuQrScannerWrapper" class="mt-2 d-none">
                                                <div id="create-onu-qr-reader" style="width: 100%; max-width: 520px;"></div>
                                                <button class="btn btn-sm btn-outline-danger mt-2" type="button" id="stopCreateOnuQrScan">
                                                    <i class="fa-solid fa-stop me-1"></i>{{ __('Hentikan Scan') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_customer_wan_mac" class="form-label">{{ __('MAC Modem (WAN)') }}</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control @error('new_customer_wan_mac') is-invalid @enderror" id="new_customer_wan_mac" name="new_customer_wan_mac" value="{{ old('new_customer_wan_mac') }}" placeholder="{{ __('Contoh: AA:BB:CC:DD:EE:FF') }}">
                                                <button class="btn btn-outline-primary" type="button" id="startCreateMacQrScan">
                                                    <i class="fa-solid fa-qrcode me-1"></i>{{ __('Scan MAC') }}
                                                </button>
                                            </div>
                                            @error('new_customer_wan_mac')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div id="createMacQrScanStatus" class="small text-muted mt-2"></div>
                                            <div id="createMacQrScannerWrapper" class="mt-2 d-none">
                                                <div id="create-mac-qr-reader" style="width: 100%; max-width: 520px;"></div>
                                                <button class="btn btn-sm btn-outline-danger mt-2" type="button" id="stopCreateMacQrScan">
                                                    <i class="fa-solid fa-stop me-1"></i>{{ __('Hentikan Scan') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label for="new_customer_address" class="form-label">{{ __('Alamat') }} <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="new_customer_address" name="new_customer_address" rows="2">{{ old('new_customer_address') }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_customer_lat" class="form-label">{{ __('Lintang') }}</label>
                                            <input type="text" class="form-control" id="new_customer_lat" name="new_customer_lat" value="{{ old('new_customer_lat') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_customer_lng" class="form-label">{{ __('Bujur') }}</label>
                                            <input type="text" class="form-control" id="new_customer_lng" name="new_customer_lng" value="{{ old('new_customer_lng') }}">
                                        </div>
                                        <div class="col-12">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="getNewCustomerLocation">
                                                <i class="fa-solid fa-location-crosshairs me-1"></i>{{ __('Ambil Location Map') }}
                                            </button>
                                            <small id="newCustomerLocationStatus" class="text-muted ms-2"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pilih teknisi -->
                        <div class="col-12">
                            <label for="technicians" class="form-label">{{ __('Tugaskan Teknisi') }}</label>
                            <select name="technicians[]" id="technicians" class="form-select @error('technicians') is-invalid @enderror" multiple>
                                @foreach($technicians as $technician)
                                    <option value="{{ $technician->id }}" {{ (collect(old('technicians'))->contains($technician->id)) ? 'selected' : '' }}>
                                        {{ $technician->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">{{ __('Pilih lebih dari satu teknisi bila perlu. Hanya teknisi yang hadir hari ini dan tidak memiliki tugas aktif yang ditampilkan.') }}</div>
                            @error('technicians')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Subjek -->
                        <div class="col-12">
                            <label for="subject" class="form-label">{{ __('Subjek') }}</label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required placeholder="{{ __('Ringkasan singkat kendala') }}" class="form-control @error('subject') is-invalid @enderror">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Prioritas -->
                        <div class="col-md-12">
                            <label for="priority" class="form-label">{{ __('Prioritas') }}</label>
                            <select name="priority" id="priority" required class="form-select @error('priority') is-invalid @enderror">
                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>{{ __('Rendah') }}</option>
                                <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>{{ __('Sedang') }}</option>
                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>{{ __('Tinggi') }}</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label for="estimated_duration_minutes" class="form-label">{{ __('Estimasi Pekerjaan (Menit)') }}</label>
                            <input
                                type="number"
                                min="15"
                                max="1440"
                                step="5"
                                name="estimated_duration_minutes"
                                id="estimated_duration_minutes"
                                value="{{ old('estimated_duration_minutes') }}"
                                class="form-control @error('estimated_duration_minutes') is-invalid @enderror"
                                placeholder="{{ __('Contoh: 90') }}"
                            >
                            <div class="form-text text-muted">{{ __('Gunakan estimasi realistis agar durasi pengerjaan lebih terkontrol.') }}</div>
                            @error('estimated_duration_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Deskripsi -->
                        <div class="col-12">
                            <label for="description" class="form-label">{{ __('Deskripsi') }}</label>
                            <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ODP dan Pengurus -->
                        <div class="col-md-6">
                            <label for="odp_id" class="form-label">ODP</label>
                            <select name="odp_id" id="odp_id" class="form-select @error('odp_id') is-invalid @enderror">
                                <option value="">{{ __('Pilih ODP') }}</option>
                                @foreach($odps as $odp)
                                    <option value="{{ $odp->id }}" {{ old('odp_id') == $odp->id ? 'selected' : '' }}>
                                        {{ $odp->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('odp_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="coordinator_id" class="form-label">{{ __('Pengurus') }}</label>
                            <select name="coordinator_id" id="coordinator_id" class="form-select @error('coordinator_id') is-invalid @enderror">
                                <option value="">{{ __('Pilih Pengurus') }}</option>
                                @foreach($coordinators as $coordinator)
                                    <option value="{{ $coordinator->id }}" {{ old('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                        {{ $coordinator->name }} ({{ $coordinator->region->name ?? __('Tanpa Wilayah') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('coordinator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Alat & Material -->
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-boxes-stacked me-1"></i> {{ __('Alat & Material yang Dibutuhkan') }}</h6>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-primary"><i class="fa-solid fa-toolbox me-1"></i> {{ __('Alat') }}</label>
                                        <div id="tools-container">
                                            <div class="row g-2 mb-2 tool-item">
                                                <div class="col-md-5">
                                                    <select name="tools[0][inventory_item_id]" class="form-select">
                                                        <option value="">{{ __('Pilih Alat') }}</option>
                                                        @foreach($inventoryItems as $item)
                                                            @if($item->type_group === 'tool')
                                                                <option value="{{ $item->id }}" data-stock="{{ $item->stock }}">
                                                                    {{ $item->name }} (Stok: {{ $item->stock }})
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="number" name="tools[0][quantity]" class="form-control" min="1" value="1" placeholder="{{ __('Jumlah') }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <button type="button" class="btn btn-outline-danger w-100 remove-item">
                                                        <i class="fa-solid fa-trash"></i> {{ __('Hapus') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-tool">
                                            <i class="fa-solid fa-plus me-1"></i> {{ __('Tambah Alat') }}
                                        </button>
                                    </div>
                                    
                                    <div>
                                        <label class="form-label fw-bold text-success"><i class="fa-solid fa-cube me-1"></i> {{ __('Material') }}</label>
                                        <div id="materials-container">
                                            <div class="row g-2 mb-2 material-item">
                                                <div class="col-md-5">
                                                    <select name="materials[0][inventory_item_id]" class="form-select">
                                                        <option value="">{{ __('Pilih Material') }}</option>
                                                        @foreach($inventoryItems as $item)
                                                            @if($item->type_group === 'material')
                                                                <option value="{{ $item->id }}" data-stock="{{ $item->stock }}">
                                                                    {{ $item->name }} (Stok: {{ $item->stock }})
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="number" name="materials[0][quantity]" class="form-control" min="1" value="1" placeholder="{{ __('Jumlah') }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <button type="button" class="btn btn-outline-danger w-100 remove-item">
                                                        <i class="fa-solid fa-trash"></i> {{ __('Hapus') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-success btn-sm mt-2" id="add-material">
                                            <i class="fa-solid fa-plus me-1"></i> {{ __('Tambah Material') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lokasi -->
                        <div class="col-12">
                            <label for="location" class="form-label">{{ __('Lokasi (Opsional)') }}</label>
                            <div class="input-group">
                                <input type="text" name="location" id="location" value="{{ old('location') }}" placeholder="{{ __('Koordinat atau catatan lokasi spesifik') }}" class="form-control @error('location') is-invalid @enderror">
                                <a href="#" id="view-map-link" target="_blank" class="btn btn-outline-secondary" style="display: none;" title="{{ __('Lihat di Google Maps') }}">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                </a>
                            </div>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-4">
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">{{ __('Batal') }}</a>
                        <button type="submit" class="btn btn-primary px-4">{{ __('Simpan Tiket') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    #create-onu-qr-reader video,
    #create-mac-qr-reader video {
        width: 100% !important;
        height: auto !important;
        object-fit: cover;
        border-radius: 0.5rem;
        filter: brightness(1.2) contrast(1.15) saturate(1.05);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    $(document).ready(function() {
        // Inisialisasi Select2
        $('#technicians').select2({
            theme: 'bootstrap-5',
            placeholder: "{{ __('Pilih Teknisi') }}",
            allowClear: true,
            width: '100%'
        });

        const typeSelect = $('#type');
        const existingCustomerSection = $('#existing-customer-section');
        const newCustomerSection = $('#new-customer-section');
        const customerSelect = $('#customer_id');
        const newCustomerInputs = $('#new_customer_name, #new_customer_address');
        const customerOptionalTypes = ['pasang_odc', 'tarik_jalur', 'perbaikan', 'maintenance'];
        const estimatedInput = $('#estimated_duration_minutes');
        const locationInput = document.getElementById('location');
        const mapLink = document.getElementById('view-map-link');
        const newCustomerLatInput = $('#new_customer_lat');
        const newCustomerLngInput = $('#new_customer_lng');
        const newCustomerLocationStatus = $('#newCustomerLocationStatus');
        const estimateByType = {
            'gangguan': 90,
            'pasang_baru': 180,
            'pasang_odc': 240,
            'tarik_jalur': 240,
            'perbaikan': 120,
            'maintenance': 90,
            'other': 120
        };

        const getMostAccuratePosition = () => new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error("{{ __('Browser tidak mendukung geolocation.') }}"));
                return;
            }

            let bestPosition = null;
            let lastError = null;
            let settled = false;
            let watchId = null;
            let timerId = null;
            const options = { enableHighAccuracy: true, timeout: 18000, maximumAge: 0 };

            const finalize = () => {
                if (settled) return;
                settled = true;
                if (watchId !== null) navigator.geolocation.clearWatch(watchId);
                if (timerId) clearTimeout(timerId);
                if (bestPosition) {
                    resolve(bestPosition);
                    return;
                }
                reject(lastError || new Error("{{ __('Gagal mengambil lokasi.') }}"));
            };

            const considerPosition = (position) => {
                const accuracy = Number(position?.coords?.accuracy ?? Number.POSITIVE_INFINITY);
                const bestAccuracy = Number(bestPosition?.coords?.accuracy ?? Number.POSITIVE_INFINITY);
                if (!bestPosition || accuracy < bestAccuracy) {
                    bestPosition = position;
                }
                if (accuracy <= 20) {
                    finalize();
                }
            };

            watchId = navigator.geolocation.watchPosition(
                (position) => considerPosition(position),
                (error) => { lastError = error; },
                options
            );

            navigator.geolocation.getCurrentPosition(
                (position) => considerPosition(position),
                (error) => { lastError = error; },
                options
            );

            timerId = setTimeout(finalize, 9000);
        });

        function toggleCustomerForm() {
            if (typeSelect.val() === 'pasang_baru') {
                existingCustomerSection.hide();
                newCustomerSection.show();
                customerSelect.prop('required', false);
                newCustomerInputs.prop('required', true);
            } else if (customerOptionalTypes.includes(typeSelect.val())) {
                existingCustomerSection.hide();
                newCustomerSection.hide();
                customerSelect.prop('required', false);
                newCustomerInputs.prop('required', false);
            } else {
                existingCustomerSection.show();
                newCustomerSection.hide();
                customerSelect.prop('required', true);
                newCustomerInputs.prop('required', false);
            }
        }

        // Cek awal
        toggleCustomerForm();
        if (estimatedInput.val() === '') {
            const suggestedInitial = estimateByType[typeSelect.val()];
            if (suggestedInitial) {
                estimatedInput.val(suggestedInitial);
                estimatedInput.data('auto', 1);
            }
        }

        // Saat tipe berubah
        typeSelect.on('change', function() {
            toggleCustomerForm();
            if (estimatedInput.val() === '' || estimatedInput.data('auto') === 1) {
                const suggested = estimateByType[typeSelect.val()];
                if (suggested) {
                    estimatedInput.val(suggested);
                    estimatedInput.data('auto', 1);
                }
            }
        });

        estimatedInput.on('input', function() {
            estimatedInput.data('auto', 0);
        });

        // Logika lokasi pelanggan lama
        function updateMapLink() {
            const val = locationInput.value;
            if (val && mapLink) {
                mapLink.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(val)}`;
                mapLink.style.display = 'inline-block';
            } else if (mapLink) {
                mapLink.style.display = 'none';
            }
        }
        
        if (locationInput) {
            updateMapLink();
            locationInput.addEventListener('input', updateMapLink);
        }

        customerSelect.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const lat = selectedOption.getAttribute('data-lat');
            const lng = selectedOption.getAttribute('data-lng');
            const address = selectedOption.getAttribute('data-address');

            if (locationInput) {
                if (lat && lng) {
                    locationInput.value = `${lat}, ${lng}`;
                } else if (address) {
                    locationInput.value = address;
                } else {
                    locationInput.value = '';
                }
                updateMapLink();
            }
        });
        
        // Logika lokasi pelanggan baru
        $('#new_customer_lat, #new_customer_lng').on('input', function() {
            const lat = $('#new_customer_lat').val();
            const lng = $('#new_customer_lng').val();
            if (lat && lng && locationInput) {
                locationInput.value = `${lat}, ${lng}`;
                updateMapLink();
            }
        });

        // Ambil lokasi dari GPS browser untuk pelanggan baru
        $('#getNewCustomerLocation').on('click', async function() {
            newCustomerLocationStatus.text("{{ __('Mengambil lokasi paling akurat...') }}");
            try {
                const position = await getMostAccuratePosition();
                const lat = Number(position.coords.latitude).toFixed(7);
                const lng = Number(position.coords.longitude).toFixed(7);
                const accuracy = Number(position.coords.accuracy || 0);
                newCustomerLatInput.val(lat);
                newCustomerLngInput.val(lng);
                if (locationInput) {
                    locationInput.value = `${lat}, ${lng}`;
                    updateMapLink();
                }
                if (accuracy > 0) {
                    newCustomerLocationStatus.text(`{{ __('Lokasi berhasil diambil') }} (±${Math.round(accuracy)}m)`);
                } else {
                    newCustomerLocationStatus.text("{{ __('Lokasi berhasil diambil.') }}");
                }
            } catch (error) {
                let msg = "{{ __('Gagal mengambil lokasi.') }}";
                if (error && error.message) {
                    msg += ' ' + error.message;
                }
                newCustomerLocationStatus.text(msg);
            }
        });

        // QR/Barcode scanner untuk SN dan MAC
        let createOnuScanner = null;
        let createMacScanner = null;
        const scannerFormats = [
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
        ];

        const buildScannerConfig = () => ({
            fps: 15,
            qrbox: { width: 320, height: 320 },
            formatsToSupport: scannerFormats,
            videoConstraints: {
                width: { ideal: 1920 },
                height: { ideal: 1080 },
                facingMode: 'environment'
            }
        });

        const normalizeMac = (value) => {
            const clean = String(value || '').replace(/[^0-9a-fA-F]/g, '').toUpperCase();
            if (clean.length !== 12) {
                return String(value || '').trim();
            }
            return clean.match(/.{1,2}/g).join(':');
        };

        const stopScanner = async (scannerRef) => {
            if (scannerRef && scannerRef.isScanning) {
                try {
                    await scannerRef.stop();
                } catch (e) {}
                try {
                    await scannerRef.clear();
                } catch (e) {}
            }
        };

        const applyTrackConstraints = async (track, constraints) => {
            if (!track || typeof track.applyConstraints !== 'function') {
                return false;
            }
            try {
                await track.applyConstraints(constraints);
                return true;
            } catch (e) {
                return false;
            }
        };

        const enhanceScannerTrack = async (scannerRef) => {
            const runningTrack = scannerRef?.getRunningTrack?.() || null;
            if (!runningTrack) {
                return;
            }

            const baseOptimizations = [
                { advanced: [{ focusMode: 'continuous' }] },
                { advanced: [{ exposureMode: 'continuous' }] },
                { advanced: [{ whiteBalanceMode: 'continuous' }] },
                { advanced: [{ brightness: 0.2 }] },
                { advanced: [{ contrast: 0.3 }] }
            ];

            for (const constraint of baseOptimizations) {
                await applyTrackConstraints(runningTrack, constraint);
            }
        };

        const startScanner = async (scannerRef, readerId, onDecode) => {
            const config = buildScannerConfig();
            const onDecodeError = () => {};

            const startByConstraints = async (constraints) => {
                await scannerRef.start(constraints, config, onDecode, onDecodeError);
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
            } catch (primaryError) {
                await startByConstraints(
                    {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                );
            }
            setTimeout(() => {
                const root = document.getElementById(readerId);
                const video = root ? root.querySelector('video') : null;
                if (video) {
                    video.setAttribute('playsinline', 'true');
                    video.setAttribute('autoplay', 'true');
                    video.setAttribute('muted', 'true');
                }
            }, 200);
            await enhanceScannerTrack(scannerRef);
        };

        $('#startCreateOnuQrScan').on('click', async function() {
            const wrapper = $('#createOnuQrScannerWrapper');
            const statusEl = $('#createOnuQrScanStatus');
            wrapper.removeClass('d-none');
            statusEl.text("{{ __('Membuka kamera untuk scan SN...') }}");
            await stopScanner(createOnuScanner);
            createOnuScanner = new Html5Qrcode('create-onu-qr-reader');
            try {
                await startScanner(createOnuScanner, 'create-onu-qr-reader', async (decodedText) => {
                    $('#new_customer_onu_serial').val(String(decodedText).trim());
                    statusEl.text("{{ __('SN berhasil discan.') }}");
                    await stopScanner(createOnuScanner);
                    wrapper.addClass('d-none');
                });
            } catch (err) {
                statusEl.text("{{ __('Gagal membuka kamera.') }}");
            }
        });

        $('#stopCreateOnuQrScan').on('click', async function() {
            await stopScanner(createOnuScanner);
            $('#createOnuQrScannerWrapper').addClass('d-none');
            $('#createOnuQrScanStatus').text("{{ __('Scan SN dihentikan.') }}");
        });

        $('#startCreateMacQrScan').on('click', async function() {
            const wrapper = $('#createMacQrScannerWrapper');
            const statusEl = $('#createMacQrScanStatus');
            wrapper.removeClass('d-none');
            statusEl.text("{{ __('Membuka kamera untuk scan MAC...') }}");
            await stopScanner(createMacScanner);
            createMacScanner = new Html5Qrcode('create-mac-qr-reader');
            try {
                await startScanner(createMacScanner, 'create-mac-qr-reader', async (decodedText) => {
                    $('#new_customer_wan_mac').val(normalizeMac(decodedText));
                    statusEl.text("{{ __('MAC berhasil discan.') }}");
                    await stopScanner(createMacScanner);
                    wrapper.addClass('d-none');
                });
            } catch (err) {
                statusEl.text("{{ __('Gagal membuka kamera.') }}");
            }
        });

        $('#stopCreateMacQrScan').on('click', async function() {
            await stopScanner(createMacScanner);
            $('#createMacQrScannerWrapper').addClass('d-none');
            $('#createMacQrScanStatus').text("{{ __('Scan MAC dihentikan.') }}");
        });
        
        // Tambah Alat
        let toolIndex = 1;
        $('#add-tool').on('click', function() {
            const toolsContainer = $('#tools-container');
            const toolItem = toolsContainer.find('.tool-item').first().clone();
            toolItem.find('select').attr('name', `tools[${toolIndex}][inventory_item_id]`).val('');
            toolItem.find('input').attr('name', `tools[${toolIndex}][quantity]`).val(1);
            toolsContainer.append(toolItem);
            toolIndex++;
        });
        
        // Tambah Material
        let materialIndex = 1;
        $('#add-material').on('click', function() {
            const materialsContainer = $('#materials-container');
            const materialItem = materialsContainer.find('.material-item').first().clone();
            materialItem.find('select').attr('name', `materials[${materialIndex}][inventory_item_id]`).val('');
            materialItem.find('input').attr('name', `materials[${materialIndex}][quantity]`).val(1);
            materialsContainer.append(materialItem);
            materialIndex++;
        });
        
        // Hapus Item
        $(document).on('click', '.remove-item', function() {
            const container = $(this).closest('.row').parent();
            if (container.children().length > 1) {
                $(this).closest('.row').remove();
            } else {
                $(this).closest('.row').find('select').val('');
                $(this).closest('.row').find('input').val(1);
            }
        });
    });
</script>
@endpush
@endsection
