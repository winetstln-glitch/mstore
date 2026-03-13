@extends('layouts.app')

@section('title', 'Ubah Layanan Wash')

@section('content')
<div class="container-fluid wash-service-edit-page">
    <h1 class="h3 mb-4 text-gray-800">Ubah Layanan</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('wash.services.update', $service->id) }}" method="POST" enctype="multipart/form-data" id="editServiceForm">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Nama Layanan</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $service->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="vehicle_type" class="form-label">Jenis Kendaraan</label>
                    <select class="form-select @error('vehicle_type') is-invalid @enderror" id="vehicle_type" name="vehicle_type" required>
                        <option value="car" {{ old('vehicle_type', $service->vehicle_type) == 'car' ? 'selected' : '' }}>Mobil</option>
                        <option value="motor" {{ old('vehicle_type', $service->vehicle_type) == 'motor' ? 'selected' : '' }}>Motor</option>
                    </select>
                    @error('vehicle_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Harga</label>
                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $service->price) }}" required min="0">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi</label>
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
                    <label for="image" class="form-label">Gambar Layanan</label>
                    @if($service->image)
                        <div class="mb-2">
                            <img src="{{ Storage::url($service->image) }}" alt="Gambar Layanan" class="img-thumbnail" style="max-height: 150px;">
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
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>

                <button type="submit" class="btn btn-primary">Perbarui Layanan</button>
                <a href="{{ route('wash.services.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<div class="position-fixed bottom-0 start-0 end-0 bg-body border-top shadow d-md-none" style="z-index: 1030;">
    <div class="container py-2">
        <div class="d-flex gap-2">
            <a href="{{ route('wash.services.index') }}" class="btn btn-outline-secondary w-50">Batal</a>
            <button type="submit" class="btn btn-primary w-50" form="editServiceForm">Perbarui</button>
        </div>
    </div>
</div>
@push('styles')
<style>
    .wash-description-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .wash-description-chip {
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

    .wash-description-chip button {
        border: 0;
        background: transparent;
        color: inherit;
        font-size: 0.9rem;
        line-height: 1;
        padding: 0;
        cursor: pointer;
    }

    [data-bs-theme="dark"] .wash-description-chip {
        background: rgba(59, 130, 246, 0.2);
        border-color: rgba(96, 165, 250, 0.42);
        color: #bfdbfe;
    }

    .wash-service-edit-page .form-control,
    .wash-service-edit-page .form-select {
        min-height: 44px;
    }

    @media (max-width: 767.98px) {
        .wash-service-edit-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
            padding-bottom: 5rem !important;
        }

        .wash-service-edit-page .h3 {
            font-size: 1.1rem;
            margin-bottom: 0.9rem !important;
        }

        .wash-service-edit-page .card-body {
            padding: 0.9rem;
        }

        .wash-service-edit-page form > .btn,
        .wash-service-edit-page form > a.btn {
            display: none;
        }
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

            const render = function (items) {
                chipsWrap.innerHTML = '';
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
                const current = new Set(Array.from(chipsWrap.querySelectorAll('[data-item-value]')).map(function (el) {
                    return el.dataset.itemValue.toLowerCase();
                }));
                const added = [];
                parseItems(rawValue).forEach(function (item) {
                    if (!current.has(item.toLowerCase())) {
                        current.add(item.toLowerCase());
                        added.push(item);
                    }
                });
                render(Array.from(chipsWrap.querySelectorAll('[data-item-value]')).map(function (el) {
                    return el.dataset.itemValue;
                }).concat(added));
            };

            render(parseItems(textarea.value));

            addButton.addEventListener('click', function () {
                if (!input.value.trim()) {
                    return;
                }
                addItems(input.value);
                input.value = '';
                input.focus();
            });

            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addButton.click();
                }
            });
        });
    });
</script>
@endpush
@endsection
