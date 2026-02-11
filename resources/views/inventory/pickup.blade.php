@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8 px-3 px-md-0">
            <!-- Card: Background & Border diset pakai CSS Variable -->
            <div class="card shadow-sm border-0 border-top border-4 border-primary">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-primary">
                        @if(request('type_group') == 'tool')
                            {{ __('Pickup Tool & Asset') }}
                        @elseif(request('type_group') == 'material')
                            {{ __('Pickup Material') }}
                        @else
                            {{ __('Pickup Item') }}
                        @endif
                    </h6>
                    <a href="{{ route('inventory.index', ['type_group' => request('type_group')]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> <span class="d-none d-sm-inline">{{ __('Back') }}</span>
                    </a>
                </div>
                <div class="card-body p-3 p-md-4">
                    <form action="{{ route('inventory.store-pickup') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <!-- Alert Style Override -->
                        <div id="location-status" class="alert alert-warning d-flex align-items-center shadow-sm mb-4" role="alert" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                            <i class="fa-solid fa-location-crosshairs fa-lg me-3"></i>
                            <div class="flex-grow-1">{{ __('Mendeteksi lokasi Anda...') }}</div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">{{ __('Select Items') }}</label>
                            
                            <!-- Table Wrapper: Border transparan di mobile -->
                            <div class="table-responsive border rounded overflow-hidden" style="border-color: var(--border-color) !important;">
                                <table class="table table-hover align-middle mb-0">
                                    <!-- Thead: Dipaksa pakai warna tema -->
                                    <thead class="bg-body-secondary" style="background-color: var(--table-head-bg) !important;">
                                        <tr>
                                            <th style="color: var(--text-main) !important;">{{ __('Item') }}</th>
                                            <th style="width: 200px; color: var(--text-main) !important;">{{ __('Quantity') }}</th>
                                            <th style="width: 80px; color: var(--text-main) !important;" class="text-center">{{ __('Act') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-body">
                                        <tr class="item-row">
                                            <td>
                                                <select name="items[0][inventory_item_id]" class="form-select form-select-lg item-select" required>
                                                    <option value="">{{ __('Choose an item...') }}</option>
                                                    @foreach($items->groupBy('type_group') as $group => $groupedItems)
                                                        <optgroup label="{{ ucfirst($group) }}">
                                                            @foreach($groupedItems as $item)
                                                                <option value="{{ $item->id }}" data-unit="{{ $item->unit }}">
                                                                    {{ $item->name }} (Stock: {{ $item->stock }} {{ $item->unit }})
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-lg">
                                                    <input type="number" name="items[0][quantity]" class="form-control quantity-input" min="1" placeholder="0" required>
                                                    <span class="input-group-text unit-display">pcs</span>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-item-row" title="{{ __('Remove Item') }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" id="add-item-row" class="btn btn-outline-primary w-100 mt-3 py-2">
                                <i class="fa-solid fa-plus me-1"></i> {{ __('Add Another Item') }}
                            </button>
                        </div>

                        <div class="mb-4">
                            <label for="usage" class="form-label fw-bold small text-muted">{{ __('Usage Type') }}</label>
                            <select name="usage" id="usage" class="form-select form-select-lg" required>
                                <option value="">{{ __('Select Usage...') }}</option>
                                <option value="New Installation">{{ __('New Installation') }}</option>
                                <option value="Replacement">{{ __('Replacement') }}</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="coordinator_id" class="form-label fw-bold small text-muted">{{ __('Coordinator (Optional)') }}</label>
                            <select name="coordinator_id" id="coordinator_id" class="form-select form-select-lg">
                                <option value="">{{ '-- ' . __('Select Coordinator') . ' --' }}</option>
                                @foreach($coordinators as $coordinator)
                                    <option value="{{ $coordinator->id }}">
                                        {{ $coordinator->name }} ({{ $coordinator->region->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            <!-- Alert Info Override -->
                            <div class="alert alert-info mt-2 small" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                                <strong>{{ __('Note:') }}</strong> {{ __('Leave empty if this is for personal use by a technician. Select a Coordinator only for team stock.') }}
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">{{ __('Proof of Pickup') }}</label>
                            <label for="proof_image" class="custom-file-upload" id="upload-area">
                                <img id="image-preview" class="upload-preview" src="#" alt="Preview">
                                <div class="upload-placeholder d-flex flex-column align-items-center" id="upload-placeholder">
                                    <i class="fa-solid fa-camera"></i>
                                    <span class="upload-label-text">Ketuk untuk ambil foto</span>
                                </div>
                                <input type="file" name="proof_image" id="proof_image" accept="image/*" required onchange="previewImage(event)">
                            </label>
                            <div class="form-text text-center mt-2">{{ __('Pastikan foto terang dan jelas.') }}</div>
                        </div>

                        <div class="mb-5">
                            <label for="description" class="form-label fw-bold small text-muted">{{ __('Notes / Description') }}</label>
                            <textarea name="description" id="description" class="form-control form-control-lg" rows="3" placeholder="{{ __('Optional notes...') }}"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">
                                <i class="fa-solid fa-paper-plane me-2"></i> {{ __('Submit Pickup') }}
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
    /* --- 1. LIGHT MODE (TERANG & BERSIH) --- */
    :root {
        --app-bg: #f3f4f6;        /* Abu-abu sangat muda (bukan putih polos biar card kelihatan) */
        --card-bg: #ffffff;       /* Kartu Putih Bersih */
        --text-main: #1f2937;     /* Hitam teks */
        --text-muted: #6b7280;    /* Abu-abu teks sekunder */
        --border-color: #e5e7eb;  /* Border halus */
        --input-bg: #f9fafb;      /* Background input sedikit abu */
        --input-focus-bg: #ffffff;
        --table-head-bg: #f3f4f6; /* Header tabel sama dengan body app */
        --item-row-bg: #ffffff;   /* Baris item putih */
        --item-row-border: #e5e7eb;
        --upload-border: #d1d5db;
        --upload-hover-bg: #f0f9ff;
        --remove-btn-bg: #fef2f2;
        --remove-btn-border: #fecaca;
        --remove-btn-text: #ef4444;
        --shadow-color: rgba(0, 0, 0, 0.05); /* Bayangan lembut */
        --primary: #2563eb;
        --primary-contrast: #ffffff;
        --secondary: #6b7280;
    }

    /* --- 2. DARK MODE (GELAP TOTAL) --- */
    [data-bs-theme="dark"] {
        --app-bg: #000000;
        --card-bg: #1c1c1e;
        --text-main: #ffffff;
        --text-muted: #a1a1aa;
        --border-color: #2c2c2e;
        --input-bg: #2c2c2e;
        --input-focus-bg: #3a3a3c;
        --table-head-bg: #2c2c2e;
        --item-row-bg: #1c1c1e;
        --item-row-border: #2c2c2e;
        --upload-border: #4b5563;
        --upload-hover-bg: #374151;
        --remove-btn-bg: #450a0a;
        --remove-btn-border: #7f1d1d;
        --remove-btn-text: #fca5a5;
        --shadow-color: rgba(0, 0, 0, 0.5);
        --primary: #60a5fa;
        --primary-contrast: #0b1220;
        --secondary: #a1a1aa;
    }

    /* --- 3. GLOBAL RESETS (Paksa Warna) --- */
   [data-bs-theme="dark"] body {
        background-color: var(--app-bg) !important;
        color: var(--text-main) !important;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

   [data-bs-theme="dark"] .card {
        background-color: var(--card-bg) !important;
        border-color: var(--border-color) !important;
        color: var(--text-main) !important;
    }
    .border-primary { border-color: var(--primary) !important; }
    .bg-body-secondary { background-color: var(--table-head-bg) !important; color: var(--text-main) !important; }
    .btn-primary { background-color: var(--primary) !important; border-color: var(--primary) !important; color: var(--primary-contrast) !important; }
    .btn-outline-primary { color: var(--primary) !important; border-color: var(--primary) !important; }
    .btn-outline-primary:hover { background-color: var(--primary) !important; color: var(--primary-contrast) !important; }
    .btn-outline-secondary { color: var(--secondary) !important; border-color: var(--secondary) !important; }
    .btn-outline-secondary:hover { background-color: var(--secondary) !important; color: var(--primary-contrast) !important; }
    
    /* Pastikan tabel hover tidak aneh */
    .table-hover > tbody > tr:hover > td, 
    .table-hover > tbody > tr:hover > th {
        background-color: rgba(0,0,0,0.02); /* Sangat tipis di light mode */
        color: var(--text-main);
    }
    [data-bs-theme="dark"] .table-hover > tbody > tr:hover > td, 
    [data-bs-theme="dark"] .table-hover > tbody > tr:hover > th {
        background-color: rgba(255,255,255,0.05);
    }

    /* Form Controls */
    .form-select, .form-control, .input-group-text {
        background-color: var(--input-bg) !important;
        border-color: var(--border-color) !important;
        color: var(--text-main) !important;
    }
    
    .form-select:focus, .form-control:focus, .input-group:focus-within {
        background-color: var(--input-focus-bg) !important;
        border-color: #3b82f6 !important; /* Biru selalu jelas di kedua mode */
        color: var(--text-main) !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25) !important;
    }

    /* Labels & Small Text */
    .form-label, .small, .form-text, .text-muted {
        color: var(--text-muted) !important;
    }

    /* Dropdown Options (Browser Native Fix) */
    [data-bs-theme="dark"] option {
        background-color: var(--card-bg) !important;
        color: var(--text-main) !important;
    }
    [data-bs-theme="dark"] optgroup {
        background-color: var(--app-bg) !important;
        color: var(--text-main) !important;
        font-weight: bold;
    }

    /* --- 4. MOBILE OPTIMIZATION --- */
    @media (max-width: 768px) {
        .container-fluid { padding: 0 !important; }
        .card { 
            border: none !important; 
            border-radius: 0 !important; 
            box-shadow: none !important; 
        }
        .card-header {
            background-color: var(--card-bg) !important;
            border-bottom: 1px solid var(--border-color) !important;
            position: sticky; top: 0; z-index: 10;
        }

        .table-responsive { border: none !important; background: transparent; }

        #items-body tr {
            display: flex; flex-direction: column;
            background-color: var(--item-row-bg) !important; /* Paksa warna baris */
            border: 1px solid var(--item-row-border) !important;
            border-radius: 12px;
            margin-bottom: 1.25rem;
            padding: 1.25rem;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        #items-body td {
            display: block; width: 100% !important;
            padding: 0.25rem 0 1rem 0 !important;
            border: none; text-align: left !important;
            background: transparent !important;
        }

        .table thead { display: none; }

        .input-group { height: 56px; }
        .quantity-input {
            font-size: 1.25rem; text-align: center; font-weight: bold;
            background-color: var(--input-bg) !important;
            color: var(--text-main) !important;
        }
      [data-bs-theme="dark"]  .unit-display {
            background-color: var(--table-head-bg) !important;
            color: var(--text-main) !important;
            font-weight: 600; border: none;
        }

        /* Remove Button */
        .remove-item-row {
            width: 100%; height: 44px; border-radius: 8px;
            background-color: var(--remove-btn-bg) !important;
            border: 1px dashed var(--remove-btn-border) !important;
            color: var(--remove-btn-text) !important;
            font-weight: 600;
            display: flex; justify-content: center; align-items: center;
        }
        .remove-item-row:active {
            background-color: #ef4444 !important; color: white !important;
        }
        .remove-item-row::after {
            content: "Hapus Item Ini"; margin-left: 8px;
        }
        
        #add-item-row {
            border-radius: 50px; font-weight: 600;
            box-shadow: 0 4px 6px var(--shadow-color);
            height: 50px; display: flex; align-items: center; justify-content: center;
            background-color: var(--card-bg) !important;
            border-color: #3b82f6 !important;
            color: #3b82f6 !important;
        }
    }

    /* --- 5. CUSTOM FILE UPLOAD --- */
    .custom-file-upload {
        border: 2px dashed var(--upload-border) !important;
        border-radius: 12px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 2rem; cursor: pointer; transition: all 0.2s;
        background-color: var(--input-bg) !important;
        position: relative; overflow: hidden; min-height: 150px;
        color: var(--text-muted);
    }
    [data-bs-theme="dark"] .custom-file-upload {
        border-color: var(--upload-border) !important;
        background-color: var(--input-bg) !important;
        color: var(--text-muted) !important;
    }
    .custom-file-upload:hover {
        border-color: #3b82f6 !important; 
        background-color: var(--upload-hover-bg) !important;
    }
    .custom-file-upload input[type="file"] { display: none; }
    
    .upload-placeholder i { font-size: 2.5rem; color: var(--text-muted) !important; margin-bottom: 0.5rem; }
    .upload-label-text { color: var(--text-muted) !important; font-weight: 500; z-index: 2; }
    
    .upload-preview {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        object-fit: cover; display: none; border-radius: 10px; z-index: 1;
    }
    .upload-preview.active { display: block; }

</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Geolocation Logic ---
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const statusDiv = document.getElementById('location-status');
        const statusText = statusDiv.querySelector('div');

        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    latInput.value = position.coords.latitude;
                    lngInput.value = position.coords.longitude;
                    
                    statusDiv.className = 'alert d-flex align-items-center shadow-sm mb-4'; // Reset class
                    statusDiv.style.backgroundColor = '#d1fae5'; // Light Green (Light Mode Friendly)
                    statusDiv.style.color = '#065f46';
                    statusDiv.style.borderColor = '#a7f3d0';
                    if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
                        statusDiv.style.backgroundColor = '#064e3b';
                        statusDiv.style.color = '#ecfdf5';
                        statusDiv.style.borderColor = '#065f46';
                    }
                    
                    statusText.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i> <strong>{{ __("Lokasi Terkunci:") }}</strong> ' + position.coords.latitude.toFixed(6) + ', ' + position.coords.longitude.toFixed(6);
                },
                function(error) {
                    let errorMsg = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED: errorMsg = "{{ __('Izin lokasi ditolak. Mohon aktifkan izin lokasi.') }}"; break;
                        case error.POSITION_UNAVAILABLE: errorMsg = "{{ __('Informasi lokasi tidak tersedia.') }}"; break;
                        case error.TIMEOUT: errorMsg = "{{ __('Waktu permintaan lokasi habis.') }}"; break;
                        default: errorMsg = "{{ __('Terjadi kesalahan saat mengambil lokasi.') }}"; break;
                    }
                    statusDiv.className = 'alert d-flex align-items-center shadow-sm mb-4 alert-danger';
                    statusText.textContent = errorMsg;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        // --- 2. Add / Remove Item Rows ---
        function updateUnit(select) {
            var row = select.closest('.item-row');
            var option = select.options[select.selectedIndex];
            var unit = option.getAttribute('data-unit') || 'pcs';
            var span = row.querySelector('.unit-display');
            if (span) span.textContent = unit;
        }

        function refreshRemoveButtons() {
            var rows = document.querySelectorAll('#items-body .item-row');
            rows.forEach(function(row) {
                var btn = row.querySelector('.remove-item-row');
                if (btn) {
                    btn.disabled = rows.length === 1;
                    btn.style.opacity = rows.length === 1 ? '0.5' : '1';
                    btn.style.cursor = rows.length === 1 ? 'not-allowed' : 'pointer';
                }
            });
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('item-select')) updateUnit(e.target);
        });

        document.getElementById('add-item-row').addEventListener('click', function() {
            var body = document.getElementById('items-body');
            var rows = body.querySelectorAll('.item-row');
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

            body.appendChild(clone);
            clone.style.opacity = '0'; clone.style.transform = 'translateY(10px)';
            setTimeout(() => {
                clone.style.transition = 'all 0.3s ease';
                clone.style.opacity = '1'; clone.style.transform = 'translateY(0)';
            }, 10);
            refreshRemoveButtons();
        });

        document.getElementById('items-body').addEventListener('click', function(e) {
            var target = e.target.closest('.remove-item-row');
            if (target) {
                var row = target.closest('.item-row');
                var body = document.getElementById('items-body');
                var rows = body.querySelectorAll('.item-row');
                
                if (rows.length > 1) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0'; row.style.transform = 'translateX(20px)';
                    setTimeout(function() { row.remove(); refreshRemoveButtons(); }, 300);
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
                uploadArea.style.borderColor = '#3b82f6'; // Biru solid saat ada foto
                uploadArea.style.padding = '0';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = '#';
            preview.classList.remove('active');
            placeholder.style.display = 'flex';
            uploadArea.style.borderStyle = 'dashed';
            uploadArea.style.borderColor = ''; // Reset ke var CSS
        }
    }
</script>
@endpush
@endsection
