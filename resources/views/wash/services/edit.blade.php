@extends('layouts.app')

@section('title', 'Edit Wash Service')

@section('content')
<div class="container-fluid wash-service-edit-page">
    <h1 class="h3 mb-4 text-gray-800">Edit Service</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('wash.services.update', $service->id) }}" method="POST" enctype="multipart/form-data" id="editServiceForm">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Service Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $service->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="vehicle_type" class="form-label">Vehicle Type</label>
                    <select class="form-select @error('vehicle_type') is-invalid @enderror" id="vehicle_type" name="vehicle_type" required>
                        <option value="car" {{ old('vehicle_type', $service->vehicle_type) == 'car' ? 'selected' : '' }}>Car (Mobil)</option>
                        <option value="motor" {{ old('vehicle_type', $service->vehicle_type) == 'motor' ? 'selected' : '' }}>Motor (Motorcycle)</option>
                        <option value="coffee" {{ old('vehicle_type', $service->vehicle_type) == 'coffee' ? 'selected' : '' }}>Coffee (Kopi)</option>
                    </select>
                    @error('vehicle_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="service_category" class="form-label">Kategori Layanan</label>
                        <select class="form-select @error('service_category') is-invalid @enderror" id="service_category" name="service_category" required>
                            @foreach(\App\Models\WashService::CATEGORY_OPTIONS as $value => $label)
                                <option value="{{ $value }}" {{ old('service_category', $service->service_category ?? 'main') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('service_category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="size_tier" class="form-label">Ukuran Kendaraan</label>
                        <select class="form-select @error('size_tier') is-invalid @enderror" id="size_tier" name="size_tier" required>
                            @foreach(\App\Models\WashService::SIZE_TIER_OPTIONS as $value => $label)
                                <option value="{{ $value }}" {{ old('size_tier', $service->size_tier ?? 'none') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('size_tier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="package_type" class="form-label">Jenis Paket</label>
                        <select class="form-select @error('package_type') is-invalid @enderror" id="package_type" name="package_type" required>
                            @foreach(\App\Models\WashService::PACKAGE_TYPE_OPTIONS as $value => $label)
                                <option value="{{ $value }}" {{ old('package_type', $service->package_type ?? 'general') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('package_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Urutan Tampil</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $service->sort_order ?? 0) }}" min="0">
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $service->price) }}" required min="0">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="holiday_price" class="form-label">Penyesuaian Harga Hari Raya (+/-)</label>
                    <input type="number" class="form-control @error('holiday_price') is-invalid @enderror" id="holiday_price" name="holiday_price" value="{{ old('holiday_price', $service->holiday_price) }}" placeholder="Contoh: 5000 atau -3000">
                    @error('holiday_price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @php
                    $existingRules = old('rule_price')
                        ? null
                        : ($service->priceRules ?? collect());
                    if (is_null($existingRules)) {
                        $ruleRows = max(1, count(old('rule_price', [])));
                    } else {
                        $ruleRows = max(1, $existingRules->count());
                    }
                @endphp
                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Aturan Harga POS (Opsional)</span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="applyPriceTemplate">Template Otomatis</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addPriceRuleRow">Tambah Baris</button>
                        </div>
                    </label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" id="priceRulesTable">
                            <thead>
                                <tr>
                                    <th>Tipe</th>
                                    <th>Ukuran</th>
                                    <th>Paket</th>
                                    <th>Harga</th>
                                    <th>Urutan</th>
                                    <th>Aktif</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i = 0; $i < $ruleRows; $i++)
                                    @php
                                        $rule = is_null($existingRules) ? null : $existingRules->values()->get($i);
                                        $oldVehicle = old('rule_vehicle_type.'.$i, $rule?->vehicle_type ?? 'all');
                                        $oldSize = old('rule_size_tier.'.$i, $rule?->size_tier ?? 'none');
                                        $oldPackage = old('rule_package_type.'.$i, $rule?->package_type ?? 'general');
                                        $oldPrice = old('rule_price.'.$i, $rule?->price);
                                        $oldSort = old('rule_sort_order.'.$i, $rule?->sort_order ?? 0);
                                        $activeFromOld = collect(old('rule_is_active', []))->map(fn($v) => (string) $v)->all();
                                        $isActive = in_array((string) $i, $activeFromOld, true);
                                        if (! old('rule_is_active') && ! is_null($rule)) {
                                            $isActive = (bool) $rule->is_active;
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm" name="rule_vehicle_type[]">
                                                <option value="all" {{ $oldVehicle === 'all' || is_null($oldVehicle) ? 'selected' : '' }}>Semua</option>
                                                <option value="car" {{ $oldVehicle === 'car' ? 'selected' : '' }}>Mobil</option>
                                                <option value="motor" {{ $oldVehicle === 'motor' ? 'selected' : '' }}>Motor</option>
                                                <option value="coffee" {{ $oldVehicle === 'coffee' ? 'selected' : '' }}>Kopi</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="rule_size_tier[]">
                                                @foreach(\App\Models\WashService::SIZE_TIER_OPTIONS as $value => $label)
                                                    <option value="{{ $value }}" {{ $oldSize === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="rule_package_type[]">
                                                @foreach(\App\Models\WashService::PACKAGE_TYPE_OPTIONS as $value => $label)
                                                    <option value="{{ $value }}" {{ $oldPackage === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" min="0" class="form-control form-control-sm" name="rule_price[]" value="{{ $oldPrice }}" placeholder="0"></td>
                                        <td><input type="number" min="0" class="form-control form-control-sm" name="rule_sort_order[]" value="{{ $oldSort }}"></td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input" name="rule_is_active[]" value="{{ $i }}" {{ $isActive ? 'checked' : '' }}>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-rule>&times;</button>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $service->description) }}</textarea>
                    <div class="wash-description-editor mt-2" data-description-editor data-target="description">
                        <div class="wash-description-chips" data-description-chips></div>
                        <div class="input-group input-group-sm mt-2">
                            <input type="text" class="form-control" data-description-input placeholder="Tambah label, contoh: Scoopy">
                            <button class="btn btn-outline-primary" type="button" data-description-add>Tambah</button>
                        </div>
                    </div>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Service Image</label>
                    @if($service->image)
                        <div class="mb-2">
                            <img src="{{ Storage::url($service->image) }}" alt="Service Image" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    @endif
                    <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-primary">Update Service</button>
                <a href="{{ route('wash.services.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<div class="position-fixed start-0 end-0 bg-body border-top shadow d-md-none wash-mobile-action-bar">
    <div class="container py-2">
        <div class="d-flex gap-2">
            <a href="{{ route('wash.services.index') }}" class="btn btn-outline-secondary w-50">Cancel</a>
            <button type="submit" class="btn btn-primary w-50" form="editServiceForm">Update</button>
        </div>
    </div>
</div>
@push('styles')
<style>
    .wash-service-edit-page .form-control,
    .wash-service-edit-page .form-select {
        min-height: 44px;
    }

    .wash-service-edit-page .wash-description-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .wash-service-edit-page .wash-description-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.58rem;
        border-radius: 999px;
        border: 1px solid #c7d2fe;
        background: #eef2ff;
        color: #3730a3;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .wash-service-edit-page .wash-description-chip button {
        border: 0;
        background: transparent;
        color: inherit;
        font-size: 0.9rem;
        line-height: 1;
        padding: 0;
        cursor: pointer;
    }

    .wash-service-edit-page .wash-description-chip-empty {
        border-color: #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        font-weight: 500;
    }

    [data-bs-theme="dark"] .wash-service-edit-page .wash-description-chip {
        background: rgba(59, 130, 246, 0.2);
        border-color: rgba(96, 165, 250, 0.42);
        color: #bfdbfe;
    }

    [data-bs-theme="dark"] .wash-service-edit-page .wash-description-chip-empty {
        background: #1e293b;
        border-color: #334155;
        color: #94a3b8;
    }

    @media (max-width: 767.98px) {
        .wash-service-edit-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
            padding-bottom: 6rem !important;
        }

        .wash-service-edit-page .h3 {
            font-size: 1.1rem;
            margin-bottom: 0.9rem !important;
        }

        .wash-service-edit-page .card-body {
            padding: 0.9rem;
        }
    }

    .wash-mobile-action-bar {
        bottom: 0;
        z-index: 1080;
    }
</style>
@endpush
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-description-editor]').forEach(function (editor) {
            const targetId = editor.dataset.target;
            const textarea = document.getElementById(targetId);
            if (!textarea) {
                return;
            }

            const chipsWrap = editor.querySelector('[data-description-chips]');
            const input = editor.querySelector('[data-description-input]');
            const addButton = editor.querySelector('[data-description-add]');

            const parseItems = function (value) {
                return value
                    .split(/[,;\n]/)
                    .map(function (item) { return item.trim(); })
                    .filter(function (item) { return item.length > 0; });
            };

            const syncTextarea = function () {
                const items = Array.from(chipsWrap.querySelectorAll('[data-item-value]')).map(function (el) {
                    return el.dataset.itemValue;
                });
                textarea.value = items.join(', ');
            };

            const getCurrentItems = function () {
                return Array.from(chipsWrap.querySelectorAll('[data-item-value]')).map(function (el) {
                    return el.dataset.itemValue;
                });
            };

            const render = function (items) {
                chipsWrap.innerHTML = '';
                if (!items.length) {
                    const empty = document.createElement('span');
                    empty.className = 'wash-description-chip wash-description-chip-empty';
                    empty.textContent = 'Belum ada label';
                    chipsWrap.appendChild(empty);
                    textarea.value = '';
                    return;
                }
                items.forEach(function (item) {
                    const chip = document.createElement('span');
                    chip.className = 'wash-description-chip';
                    chip.dataset.itemValue = item;
                    chip.textContent = item;

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.innerHTML = '&times;';
                    removeButton.addEventListener('click', function () {
                        chip.remove();
                        syncTextarea();
                    });

                    chip.appendChild(removeButton);
                    chipsWrap.appendChild(chip);
                });
                syncTextarea();
            };

            const addItems = function (rawValue) {
                const existingItems = getCurrentItems();
                const current = new Set(existingItems.map(function (item) {
                    return item.toLowerCase();
                }));
                const added = [];
                parseItems(rawValue).forEach(function (item) {
                    if (!current.has(item.toLowerCase())) {
                        current.add(item.toLowerCase());
                        added.push(item);
                    }
                });
                render(existingItems.concat(added));
            };

            const syncFromTextarea = function () {
                const items = [];
                const dedupe = new Set();
                parseItems(textarea.value).forEach(function (item) {
                    const key = item.toLowerCase();
                    if (dedupe.has(key)) {
                        return;
                    }
                    dedupe.add(key);
                    items.push(item);
                });
                render(items);
            };

            textarea.addEventListener('input', function () {
                const selectionStart = textarea.selectionStart;
                const selectionEnd = textarea.selectionEnd;
                syncFromTextarea();
                textarea.setSelectionRange(selectionStart, selectionEnd);
            });

            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addButton.click();
                }
            });

            addButton.addEventListener('click', function () {
                if (!input.value.trim()) {
                    return;
                }
                addItems(input.value);
                input.value = '';
                input.focus();
            });

            const initialItems = [];
            const initialSeen = new Set();
            parseItems(textarea.value).forEach(function (item) {
                const key = item.toLowerCase();
                if (initialSeen.has(key)) {
                    return;
                }
                initialSeen.add(key);
                initialItems.push(item);
            });
            render(initialItems);
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.querySelector('#priceRulesTable tbody');
        const addButton = document.getElementById('addPriceRuleRow');
        const templateButton = document.getElementById('applyPriceTemplate');
        const basePriceInput = document.getElementById('price');
        const vehicleTypeInput = document.getElementById('vehicle_type');
        const categoryInput = document.getElementById('service_category');
        if (!tableBody || !addButton) {
            return;
        }

        const renderRow = function (index) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <select class="form-select form-select-sm" name="rule_vehicle_type[]">
                        <option value="all" selected>Semua</option>
                        <option value="car">Mobil</option>
                        <option value="motor">Motor</option>
                        <option value="coffee">Kopi</option>
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="rule_size_tier[]">
                        <option value="none" selected>-</option>
                        <option value="kecil">Kecil</option>
                        <option value="sedang">Sedang</option>
                        <option value="besar">Besar</option>
                        <option value="extra_besar">Extra Besar</option>
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="rule_package_type[]">
                        <option value="general" selected>General</option>
                        <option value="body_only">Body Only</option>
                        <option value="full_clean">Body + Kolong + Vacuum</option>
                        <option value="express">Cuci Cepat + Semir Ban</option>
                        <option value="engine_cleaner">Cleaner Mesin</option>
                        <option value="leather_cleaner">Cleaner Jok Kulit</option>
                    </select>
                </td>
                <td><input type="number" min="0" class="form-control form-control-sm" name="rule_price[]" placeholder="0"></td>
                <td><input type="number" min="0" class="form-control form-control-sm" name="rule_sort_order[]" value="${index}"></td>
                <td class="text-center"><input type="checkbox" class="form-check-input" name="rule_is_active[]" value="${index}" checked></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-rule>&times;</button></td>
            `;
            tableBody.appendChild(row);
        };

        const syncActiveIndexes = function () {
            Array.from(tableBody.querySelectorAll('tr')).forEach((row, index) => {
                const active = row.querySelector('input[name="rule_is_active[]"]');
                if (active) {
                    active.value = String(index);
                }
            });
        };

        const addRow = function (rowData, index) {
            renderRow(index);
            const row = tableBody.lastElementChild;
            if (!row) {
                return;
            }
            row.querySelector('select[name="rule_vehicle_type[]"]').value = rowData.vehicle_type || 'all';
            row.querySelector('select[name="rule_size_tier[]"]').value = rowData.size_tier || 'none';
            row.querySelector('select[name="rule_package_type[]"]').value = rowData.package_type || 'general';
            row.querySelector('input[name="rule_price[]"]').value = rowData.price || '';
            row.querySelector('input[name="rule_sort_order[]"]').value = rowData.sort_order ?? index;
            row.querySelector('input[name="rule_is_active[]"]').checked = rowData.is_active !== false;
        };

        const buildTemplateRows = function () {
            const base = parseFloat(basePriceInput?.value || 0) || 0;
            const vehicleType = vehicleTypeInput?.value || 'car';
            const category = categoryInput?.value || 'main';

            if (category === 'addon' || category === 'skincare') {
                const sizes = ['kecil', 'sedang', 'besar'];
                return sizes.map((size, idx) => ({
                    vehicle_type: vehicleType,
                    size_tier: size,
                    package_type: category === 'skincare' ? 'leather_cleaner' : 'engine_cleaner',
                    price: Math.max(0, Math.round(base + (idx * 5000))),
                    sort_order: idx + 1,
                    is_active: true,
                }));
            }

            if (vehicleType === 'motor') {
                return [
                    { vehicle_type: 'motor', size_tier: 'kecil', package_type: 'express', price: Math.max(0, Math.round(base * 0.85)), sort_order: 1, is_active: true },
                    { vehicle_type: 'motor', size_tier: 'sedang', package_type: 'express', price: Math.max(0, Math.round(base)), sort_order: 2, is_active: true },
                    { vehicle_type: 'motor', size_tier: 'besar', package_type: 'express', price: Math.max(0, Math.round(base * 1.25)), sort_order: 3, is_active: true },
                ];
            }

            return [
                { vehicle_type: 'car', size_tier: 'kecil', package_type: 'body_only', price: Math.max(0, Math.round(base * 0.8)), sort_order: 1, is_active: true },
                { vehicle_type: 'car', size_tier: 'kecil', package_type: 'full_clean', price: Math.max(0, Math.round(base)), sort_order: 2, is_active: true },
                { vehicle_type: 'car', size_tier: 'sedang', package_type: 'body_only', price: Math.max(0, Math.round(base)), sort_order: 3, is_active: true },
                { vehicle_type: 'car', size_tier: 'sedang', package_type: 'full_clean', price: Math.max(0, Math.round(base * 1.2)), sort_order: 4, is_active: true },
                { vehicle_type: 'car', size_tier: 'besar', package_type: 'body_only', price: Math.max(0, Math.round(base * 1.2)), sort_order: 5, is_active: true },
                { vehicle_type: 'car', size_tier: 'besar', package_type: 'full_clean', price: Math.max(0, Math.round(base * 1.4)), sort_order: 6, is_active: true },
            ];
        };

        addButton.addEventListener('click', function () {
            renderRow(tableBody.querySelectorAll('tr').length);
            syncActiveIndexes();
        });

        if (templateButton) {
            templateButton.addEventListener('click', function () {
                const rows = buildTemplateRows();
                tableBody.innerHTML = '';
                rows.forEach((row, index) => addRow(row, index));
                if (rows.length === 0) {
                    renderRow(0);
                }
                syncActiveIndexes();
            });
        }

        tableBody.addEventListener('click', function (event) {
            const button = event.target.closest('[data-remove-rule]');
            if (!button) {
                return;
            }
            const row = button.closest('tr');
            if (!row) {
                return;
            }
            if (tableBody.querySelectorAll('tr').length <= 1) {
                row.querySelectorAll('input[type="number"]').forEach(input => input.value = '');
                return;
            }
            row.remove();
            syncActiveIndexes();
        });
    });
</script>
@endpush
@endsection
