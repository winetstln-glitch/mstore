@extends('layouts.app')

@section('content')
<div class="container-fluid inventory-pickup-page py-2 py-md-3">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8 px-3 px-md-0">
            <!-- Card: Background & Border diset pakai CSS Variable -->
            <div class="card shadow-sm border-0 border-top border-4 border-primary inventory-pickup-shell">
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
                        <div id="location-status" class="alert alert-warning d-flex align-items-center shadow-sm mb-4" role="alert">
                            <i class="fa-solid fa-location-crosshairs fa-lg me-3"></i>
                            <div class="flex-grow-1">{{ __('Mendeteksi lokasi Anda...') }}</div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">{{ __('Select Items') }}</label>
                            
                            <!-- Table Wrapper: Border transparan di mobile -->
                            <div class="table-responsive border rounded overflow-hidden">
                                <table class="table table-hover align-middle mb-0">
                                    <!-- Thead: Dipaksa pakai warna tema -->
                                    <thead class="">
                                        <tr>
                                            <th>{{ __('Item') }}</th>
                                            <th style="width: 200px;">{{ __('Quantity') }}</th>
                                            <th style="width: 80px;" class="text-center">{{ __('Act') }}</th>
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
                                                                    {{ $item->name }} ({{ __('Stok') }}: {{ $item->stock }} {{ $item->unit }})
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
                            <div class="alert alert-info mt-2 small">
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
    /* Compact Design for Pickup Page */
    .inventory-pickup-page .inventory-pickup-shell {
        border-radius: 1rem;
        border-top-width: 3px !important;
        background: #fff;
    }

    /* Form Layout Optimization */
    .inventory-pickup-page .form-label {
        margin-bottom: 0.35rem;
        letter-spacing: 0.025em;
    }

    .inventory-pickup-page .form-select-lg, 
    .inventory-pickup-page .form-control-lg {
        font-size: 1rem;
        padding: 0.6rem 1rem;
        border-radius: 0.5rem;
    }

    /* Table Item Styling */
    .inventory-pickup-page .table-responsive {
        border-color: #e2e8f0 !important;
    }

    .inventory-pickup-page .table thead th {
        background: #f8fafc !important;
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #64748b !important;
        padding: 0.75rem;
    }

    /* Custom File Upload Styling */
    .custom-file-upload {
        border: 2px dashed #cbd5e1 !important;
        border-radius: 0.75rem;
        padding: 1.5rem !important;
        min-height: 120px !important;
        background: #f8fafc !important;
    }

    .custom-file-upload:hover {
        border-color: #3b82f6 !important;
        background: #eff6ff !important;
    }

    .upload-placeholder i {
        font-size: 2rem !important;
        margin-bottom: 0.25rem !important;
    }

    /* Dark Mode Support */
    [data-bs-theme="dark"] .inventory-pickup-page .inventory-pickup-shell {
        background: #1e293b;
    }
    
    [data-bs-theme="dark"] .inventory-pickup-page .table thead th {
        background: #334155 !important;
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .custom-file-upload {
        background: #0f172a !important;
        border-color: #334155 !important;
    }

    /* Mobile Adjustments */
    @media (max-width: 767.98px) {
        .inventory-pickup-page #items-body tr {
            border-width: 1px;
            margin-bottom: 1rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
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
        const statusText = statusDiv.querySelector('div');
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
            const options = { enableHighAccuracy: true, timeout: 18000, maximumAge: 0 };

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
            timerId = setTimeout(finalize, 9000);
        });

        if ("geolocation" in navigator) {
            getMostAccuratePosition().then(function(position) {
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
                    const accuracy = Number(position.coords.accuracy || 0);
                    const accuracyText = accuracy > 0 ? ` (±${Math.round(accuracy)}m)` : '';
                    statusText.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i> <strong>{{ __("Lokasi Terkunci:") }}</strong> '
                        + position.coords.latitude.toFixed(6) + ', ' + position.coords.longitude.toFixed(6) + accuracyText;
                }).catch(function(error) {
                    let errorMsg = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED: errorMsg = "{{ __('Izin lokasi ditolak. Mohon aktifkan izin lokasi.') }}"; break;
                        case error.POSITION_UNAVAILABLE: errorMsg = "{{ __('Informasi lokasi tidak tersedia.') }}"; break;
                        case error.TIMEOUT: errorMsg = "{{ __('Waktu permintaan lokasi habis.') }}"; break;
                        default: errorMsg = "{{ __('Terjadi kesalahan saat mengambil lokasi.') }}"; break;
                    }
                    statusDiv.className = 'alert d-flex align-items-center shadow-sm mb-4 alert-danger';
                    statusText.textContent = errorMsg;
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
