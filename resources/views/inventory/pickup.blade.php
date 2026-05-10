@extends('layouts.app')

@section('content')
<div class="container-fluid inventory-pickup-page py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            
            <!-- Main Card -->
            <div class="card shadow-sm border-0 inventory-pickup-shell overflow-hidden">
                <!-- Header -->
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-box-open text-primary fa-lg"></i>
                        <h5 class="mb-0 fw-bold">
                            @if(request('type_group') == 'tool')
                                {{ __('Pickup Alat & Aset') }}
                            @elseif(request('type_group') == 'material')
                                {{ __('Pickup Material') }}
                            @else
                                {{ __('Ambil Barang Inventaris') }}
                            @endif
                        </h5>
                    </div>
                    <a href="{{ route('inventory.index', ['type_group' => request('type_group')]) }}" class="btn btn-sm btn-outline-secondary px-3">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali') }}
                    </a>
                </div>

                <div class="card-body p-3 p-md-4">
                    <form action="{{ route('inventory.store-pickup') }}" method="POST" enctype="multipart/form-data" id="pickupForm">
                        @csrf
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <!-- Geolocation Status -->
                        <div id="location-status" class="alert alert-light border d-flex align-items-center mb-4 py-2 px-3 shadow-xs">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status" id="location-spinner"></div>
                            <i class="fa-solid fa-location-dot text-success d-none me-2" id="location-icon"></i>
                            <span class="small fw-medium text-muted" id="location-text">{{ __('Mendeteksi lokasi Anda...') }}</span>
                        </div>
                        
                        <!-- Items Selection Section -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold small text-uppercase text-muted mb-0">{{ __('Pilih Barang & Jumlah') }}</label>
                                <span class="badge bg-light text-dark border fw-normal" id="item-count-badge">1 Item</span>
                            </div>
                            
                            <!-- Items List -->
                            <div id="items-container" class="mb-3">
                                <!-- Initial Row -->
                                <div class="item-row card border shadow-xs mb-2 p-3 bg-light-subtle">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-12 col-md-7">
                                            <label class="x-small fw-bold text-muted text-uppercase mb-1">{{ __('Nama Barang') }}</label>
                                            <select name="items[0][inventory_item_id]" class="form-select item-select select2-basic" required>
                                                <option value="">{{ __('Cari barang...') }}</option>
                                                @foreach($items->groupBy('type_group') as $group => $groupedItems)
                                                    <optgroup label="{{ $group == 'tool' ? 'Alat / Aset' : 'Material / Perangkat' }}">
                                                        @foreach($groupedItems as $item)
                                                            <option value="{{ $item->id }}" data-unit="{{ $item->unit }}">
                                                                {{ $item->name }} (Stok: {{ $item->stock }} {{ $item->unit }})
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-8 col-md-4">
                                            <label class="x-small fw-bold text-muted text-uppercase mb-1">{{ __('Jumlah') }}</label>
                                            <div class="input-group">
                                                <input type="number" name="items[0][quantity]" class="form-control quantity-input" min="1" placeholder="0" required>
                                                <span class="input-group-text bg-light unit-display x-small">pcs</span>
                                            </div>
                                        </div>
                                        <div class="col-4 col-md-1 text-end">
                                            <button type="button" class="btn btn-outline-danger w-100 remove-item-row" title="{{ __('Hapus') }}">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" id="add-item-row" class="btn btn-outline-primary btn-sm w-100 py-2 dashed-btn">
                                <i class="fa-solid fa-plus-circle me-1"></i> {{ __('Tambah Barang Lain') }}
                            </button>
                        </div>

                        <hr class="my-4 opacity-5">

                        <!-- Additional Details -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="usage" class="form-label fw-bold small text-muted text-uppercase">{{ __('Tujuan Pemakaian') }}</label>
                                <select name="usage" id="usage" class="form-select" required>
                                    <option value="">{{ __('Pilih tujuan...') }}</option>
                                    <option value="New Installation">{{ __('Pemasangan Baru') }}</option>
                                    <option value="Installation">{{ __('Instalasi') }}</option>
                                    <option value="Replacement">{{ __('Perbaikan / Maintenance') }}</option>
                                    <option value="Device Replacement">{{ __('Penggantian Alat') }}</option>
                                    <option value="Stock Team">{{ __('Stok Tim / Coordinator') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="coordinator_id" class="form-label fw-bold small text-muted text-uppercase">{{ __('Koordinator (Opsional)') }}</label>
                                <select name="coordinator_id" id="coordinator_id" class="form-select">
                                    <option value="">{{ '-- ' . __('Pilih Koordinator') . ' --' }}</option>
                                    @foreach($coordinators as $coordinator)
                                        <option value="{{ $coordinator->id }}">
                                            {{ $coordinator->name }} ({{ $coordinator->region->name ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Proof of Pickup -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">{{ __('Foto Bukti Pengambilan') }}</label>
                            <div class="upload-container">
                                <label for="proof_image" class="custom-file-upload shadow-xs" id="upload-area">
                                    <img id="image-preview" class="upload-preview" src="#" alt="Preview">
                                    <div class="upload-placeholder d-flex flex-column align-items-center" id="upload-placeholder">
                                        <div class="icon-circle mb-2">
                                            <i class="fa-solid fa-camera fa-lg"></i>
                                        </div>
                                        <span class="upload-label-text fw-bold">Ambil Foto Bukti</span>
                                        <span class="x-small text-muted mt-1">Klik di sini untuk membuka kamera</span>
                                    </div>
                                    <input type="file" name="proof_image" id="proof_image" accept="image/*" required onchange="previewImage(event)">
                                </label>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold small text-muted text-uppercase">{{ __('Catatan Tambahan') }}</label>
                            <textarea name="description" id="description" class="form-control" rows="2" placeholder="{{ __('Contoh: Nama pelanggan, lokasi ODP, dll...') }}"></textarea>
                        </div>

                        <!-- Submit -->
                        <div class="pt-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100 shadow fw-bold py-3">
                                <i class="fa-solid fa-paper-plane me-2"></i> {{ __('SIMPAN PENGAMBILAN') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Compact Design for Pickup Page */
    .inventory-pickup-page .inventory-pickup-shell {
        border-radius: 1rem;
        border-top: 4px solid var(--bs-primary) !important;
        background: var(--bs-card-bg);
    }

    /* Input Styling */
    .inventory-pickup-page .form-label {
        margin-bottom: 0.4rem;
        letter-spacing: 0.025em;
    }

    .inventory-pickup-page .form-select, 
    .inventory-pickup-page .form-control {
        border-radius: 0.5rem;
        padding: 0.6rem 0.85rem;
        border-color: var(--bs-border-color);
    }

    .inventory-pickup-page .form-select:focus,
    .inventory-pickup-page .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.1);
    }

    /* Item Row Card Styling */
    .item-row {
        transition: all 0.2s ease;
        border-color: rgba(0,0,0,0.05) !important;
    }

    .item-row:hover {
        border-color: var(--bs-primary-border-subtle) !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075) !important;
    }

    .dashed-btn {
        border-style: dashed !important;
        border-width: 2px !important;
        background: rgba(var(--bs-primary-rgb), 0.02);
    }

    /* Custom File Upload */
    .upload-container {
        max-width: 400px;
        margin: 0 auto;
    }

    .custom-file-upload {
        border: 2px dashed var(--bs-border-color);
        border-radius: 1rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        cursor: pointer;
        transition: all 0.2s;
        background: var(--bs-light-bg-subtle);
        position: relative;
        overflow: hidden;
        min-height: 180px;
        width: 100%;
    }

    .custom-file-upload:hover {
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.03);
    }

    .custom-file-upload input[type="file"] { display: none; }

    .icon-circle {
        width: 50px;
        height: 50px;
        background: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .upload-preview {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
        z-index: 2;
    }

    .upload-preview.active { display: block; }

    /* Utility Classes */
    .x-small { font-size: 0.7rem; }
    .shadow-xs { box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .shadow-inner { box-shadow: inset 0 2px 4px rgba(0,0,0,0.03); }

    /* Dark Mode Support */
    [data-bs-theme="dark"] .inventory-pickup-page .item-row {
        background: #1e293b !important;
    }
    
    [data-bs-theme="dark"] .custom-file-upload {
        background: #0f172a;
        border-color: #334155;
    }

    /* Mobile Adjustments */
    @media (max-width: 767.98px) {
        .inventory-pickup-page {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        .item-row {
            padding: 1rem !important;
        }
        
        .item-row .row > div {
            margin-bottom: 0.5rem;
        }
        
        .item-row .row > div:last-child {
            margin-bottom: 0;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Geolocation Logic ---
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const statusDiv = document.getElementById('location-status');
        const spinner = document.getElementById('location-spinner');
        const icon = document.getElementById('location-icon');
        const statusText = document.getElementById('location-text');

        const getMostAccuratePosition = () => new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }
            let bestPosition = null;
            let lastError = null;
            let settled = false;
            let watchId = null;
            let timerId = null;
            const options = { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 };

            const finalize = () => {
                if (settled) return;
                settled = true;
                if (watchId !== null) navigator.geolocation.clearWatch(watchId);
                if (timerId) clearTimeout(timerId);
                if (bestPosition) resolve(bestPosition);
                else reject(lastError || new Error('Location unavailable'));
            };

            const considerPosition = (position) => {
                const accuracy = Number(position?.coords?.accuracy ?? Number.POSITIVE_INFINITY);
                const bestAccuracy = Number(bestPosition?.coords?.accuracy ?? Number.POSITIVE_INFINITY);
                if (!bestPosition || accuracy < bestAccuracy) {
                    bestPosition = position;
                }
                if (accuracy <= 20) finalize();
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
            timerId = setTimeout(finalize, 8000);
        });

        if ("geolocation" in navigator) {
            getMostAccuratePosition().then(function(position) {
                    latInput.value = position.coords.latitude;
                    lngInput.value = position.coords.longitude;
                    
                    spinner.classList.add('d-none');
                    icon.classList.remove('d-none');
                    statusDiv.classList.replace('alert-light', 'alert-success');
                    statusDiv.style.backgroundColor = 'rgba(var(--bs-success-rgb), 0.1)';
                    
                    const accuracy = Math.round(position.coords.accuracy || 0);
                    statusText.innerHTML = `<strong>Lokasi Terkunci:</strong> ${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)} <span class="x-small opacity-75">(±${accuracy}m)</span>`;
                }).catch(function(error) {
                    spinner.classList.add('d-none');
                    statusDiv.classList.replace('alert-light', 'alert-danger');
                    statusText.textContent = "{{ __('Gagal mendapatkan lokasi. Pastikan GPS aktif.') }}";
                });
        }

        // --- 2. Add / Remove Item Rows ---
        function updateUnit(select) {
            var row = select.closest('.item-row');
            var option = select.options[select.selectedIndex];
            var unit = option.getAttribute('data-unit') || 'pcs';
            var span = row.querySelector('.unit-display');
            if (span) span.textContent = unit;
        }

        function updateItemCount() {
            const count = document.querySelectorAll('.item-row').length;
            const badge = document.getElementById('item-count-badge');
            if (badge) badge.textContent = `${count} Item`;
        }

        function refreshRemoveButtons() {
            var rows = document.querySelectorAll('.item-row');
            rows.forEach(function(row) {
                var btn = row.querySelector('.remove-item-row');
                if (btn) btn.style.display = rows.length === 1 ? 'none' : 'block';
            });
            updateItemCount();
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('item-select')) updateUnit(e.target);
        });

        document.getElementById('add-item-row').addEventListener('click', function() {
            var container = document.getElementById('items-container');
            var rows = container.querySelectorAll('.item-row');
            var lastRow = rows[rows.length - 1];
            var newIndex = rows.length;
            var clone = lastRow.cloneNode(true);

            clone.querySelectorAll('select, input').forEach(function(el) {
                if (el.name && el.name.indexOf('items[') === 0) {
                    el.name = el.name.replace(/items\[\d+\]/, 'items[' + newIndex + ']');
                    if (el.tagName === 'INPUT') el.value = '';
                    if (el.tagName === 'SELECT') {
                        el.selectedIndex = 0;
                        var row = el.closest('.item-row');
                        var span = row.querySelector('.unit-display');
                        if(span) span.textContent = 'pcs';
                    }
                }
            });

            container.appendChild(clone);
            clone.style.opacity = '0'; clone.style.transform = 'translateY(10px)';
            setTimeout(() => {
                clone.style.transition = 'all 0.3s ease';
                clone.style.opacity = '1'; clone.style.transform = 'translateY(0)';
            }, 10);
            refreshRemoveButtons();
        });

        document.getElementById('items-container').addEventListener('click', function(e) {
            var target = e.target.closest('.remove-item-row');
            if (target) {
                var row = target.closest('.item-row');
                var rows = document.querySelectorAll('.item-row');
                
                if (rows.length > 1) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0'; row.style.transform = 'scale(0.95)';
                    setTimeout(function() { row.remove(); refreshRemoveButtons(); }, 250);
                }
            }
        });

        refreshRemoveButtons();
    });

    // --- 3. Image Preview Logic ---
    function previewImage(event) {
        const input = event.target;
        const preview = document.getElementById('image-preview');
        const placeholder = document.getElementById('upload-placeholder');
        const uploadArea = document.getElementById('upload-area');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.add('active');
                placeholder.style.display = 'none';
                uploadArea.style.borderStyle = 'solid';
                uploadArea.style.borderColor = 'var(--bs-primary)';
                uploadArea.style.padding = '0';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
@endsection
