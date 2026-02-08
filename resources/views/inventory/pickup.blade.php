@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <!-- Mengubah col-md-8 menjadi col-12 col-lg-8 untuk lebar penuh di mobile -->
        <div class="col-12 col-lg-10 col-xl-8 px-3 px-md-0">
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

                        <!-- Geolocation Status Alert -->
                        <div id="location-status" class="alert alert-warning d-flex align-items-center shadow-sm mb-4" role="alert">
                            <i class="fa-solid fa-location-crosshairs fa-lg me-3"></i>
                            <div class="flex-grow-1">{{ __('Mendeteksi lokasi Anda...') }}</div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">{{ __('Select Items') }}</label>
                            
                            <div class="table-responsive border rounded overflow-hidden">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-body-secondary">
                                        <tr>
                                            <th>{{ __('Item') }}</th>
                                            <th style="width: 200px;">{{ __('Quantity') }}</th>
                                            <th style="width: 80px;" class="text-center">{{ __('Act') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-body">
                                        <tr class="item-row">
                                            <td>
                                                <!-- Menggunakan form-select-lg untuk kemudahan sentuh -->
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
                            <div class="alert alert-info mt-2 small">
                                <strong>{{ __('Note:') }}</strong> {{ __('Leave empty if this is for personal use by a technician. Select a Coordinator only for team stock.') }}
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="proof_image" class="form-label fw-bold small text-muted">{{ __('Proof of Pickup') }}</label>
                            <!-- Menggunakan form-control-lg agar area klik file lebih besar -->
                            <input type="file" name="proof_image" id="proof_image" class="form-control form-control-lg" accept="image/*" required>
                            <div class="form-text">{{ __('Take a photo of the items or receipt.') }}</div>
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
    /* Mobile Optimization Styles */
    @media (max-width: 768px) {
        /* 1. Table to Card Transformation */
        #items-body tr {
            display: block;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
        }

        #items-body td {
            display: block;
            width: 100% !important;
            padding: 0.5rem 0 !important;
            border: none;
            text-align: left !important;
        }

        /* Hide table header on mobile */
        .table thead {
            display: none;
        }

        /* 2. Input Styling for Card View */
        .item-select {
            margin-bottom: 1rem;
            font-size: 16px; /* Prevents iOS zoom */
            padding: 0.75rem;
        }

        /* Input group needs to be full width */
        .input-group {
            display: flex;
            width: 100%;
            margin-bottom: 1rem;
        }

        .quantity-input {
            height: 50px; /* Tambah tinggi untuk sentuhan */
            font-size: 1.25rem;
            text-align: center;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .unit-display {
            font-size: 1rem;
            font-weight: bold;
            background-color: #e9ecef;
            min-width: 60px;
            justify-content: center;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        /* 3. Remove Button Styling */
        .remove-item-row {
            width: 100%;
            padding: 0.75rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            border: 2px dashed #dc3545;
            background-color: #fff;
            color: #dc3545;
            transition: all 0.2s;
            border-radius: 0.5rem;
        }

        .remove-item-row:active {
            background-color: #dc3545;
            color: #fff;
        }

        /* Tambahkan teks "Remove" menggunakan CSS (karena icon saja bisa membingungkan) */
        .remove-item-row::after {
            content: "Hapus Item";
            font-weight: 600;
        }

        /* Sembunyikan icon asli jika mau teks saja, atau biarkan keduanya */
        .remove-item-row i {
            font-size: 1.1em;
        }
        
        /* 4. Location Alert Compact */
        #location-status {
            font-size: 0.9rem;
            padding: 0.75rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const statusDiv = document.getElementById('location-status');
        const statusText = statusDiv.querySelector('div');
        const submitBtn = document.querySelector('button[type="submit"]');

        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    latInput.value = position.coords.latitude;
                    lngInput.value = position.coords.longitude;
                    
                    statusDiv.classList.remove('alert-warning');
                    statusDiv.classList.add('alert-success', 'border-success');
                    statusText.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i> <strong>{{ __("Lokasi Terkunci:") }}</strong> ' + position.coords.latitude.toFixed(6) + ', ' + position.coords.longitude.toFixed(6);
                },
                function(error) {
                    let errorMsg = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = "{{ __('Izin lokasi ditolak. Mohon aktifkan izin lokasi.') }}";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = "{{ __('Informasi lokasi tidak tersedia.') }}";
                            break;
                        case error.TIMEOUT:
                            errorMsg = "{{ __('Waktu permintaan lokasi habis.') }}";
                            break;
                        default:
                            errorMsg = "{{ __('Terjadi kesalahan saat mengambil lokasi.') }}";
                            break;
                    }
                    statusDiv.classList.remove('alert-warning');
                    statusDiv.classList.add('alert-danger');
                    statusText.textContent = errorMsg;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            statusDiv.classList.remove('alert-warning');
            statusDiv.classList.add('alert-danger');
            statusText.textContent = "{{ __('Browser tidak mendukung Geolocation.') }}";
        }
    });

    function updateUnit(select) {
        var row = select.closest('.item-row');
        var option = select.options[select.selectedIndex];
        var unit = option.getAttribute('data-unit') || 'pcs';
        var span = row.querySelector('.unit-display');
        if (span) {
            span.textContent = unit;
        }
    }

    function refreshRemoveButtons() {
        var rows = document.querySelectorAll('#items-body .item-row');
        rows.forEach(function(row, index) {
            var btn = row.querySelector('.remove-item-row');
            if (btn) {
                // Disable tombol hapus jika hanya 1 baris tersisa
                btn.disabled = rows.length === 1;
                // Visual feedback saat disabled
                if(rows.length === 1) {
                    btn.style.opacity = '0.5';
                    btn.style.cursor = 'not-allowed';
                } else {
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                }
            }
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-select')) {
            updateUnit(e.target);
        }
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
                if (el.tagName === 'INPUT') {
                    el.value = '';
                }
                if (el.tagName === 'SELECT') {
                    el.selectedIndex = 0;
                    // Reset unit display
                    var row = el.closest('.item-row');
                    var span = row.querySelector('.unit-display');
                    if(span) span.textContent = 'pcs';
                }
            }
        });

        body.appendChild(clone);
        
        // Animasi masuk yang halus (opsional)
        clone.style.opacity = '0';
        clone.style.transform = 'translateY(10px)';
        setTimeout(() => {
            clone.style.transition = 'all 0.3s ease';
            clone.style.opacity = '1';
            clone.style.transform = 'translateY(0)';
        }, 10);

        refreshRemoveButtons();
    });

    document.getElementById('items-body').addEventListener('click', function(e) {
        // Handle klik pada icon atau tombol remove
        var target = e.target.closest('.remove-item-row');
        
        if (target) {
            var row = target.closest('.item-row');
            var body = document.getElementById('items-body');
            var rows = body.querySelectorAll('.item-row');
            
            if (rows.length > 1) {
                // Animasi hapus
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                
                setTimeout(function() {
                    row.remove();
                    refreshRemoveButtons();
                }, 300);
            }
        }
    });

    // Inisialisasi awal
    refreshRemoveButtons();
</script>
@endpush
@endsection