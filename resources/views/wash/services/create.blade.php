@extends('layouts.app')

@section('title', 'Tambah Layanan Wash')

@section('content')
<div class="col-12">
    <h1 class="h3 mb-4 text-gray-800">Tambah Layanan Baru</h1>

    <div class="card shadow mb-4">
        <div class="card-header d-none d-md-flex justify-content-between align-items-center">
            <div class="fw-semibold">Formulir Layanan</div>
            <div class="d-flex gap-2">
                <a href="{{ route('wash.services.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary" form="createServiceForm">Tambah Layanan Wash</button>
            </div>
        </div>
        <div class="card-body">
            <form id="createServiceForm" action="{{ route('wash.services.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Nama Layanan</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="vehicle_type" class="form-label">Jenis Kendaraan</label>
                    <select class="form-select @error('vehicle_type') is-invalid @enderror" id="vehicle_type" name="vehicle_type" required>
                        <option value="car" {{ old('vehicle_type') == 'car' ? 'selected' : '' }}>Mobil</option>
                        <option value="motor" {{ old('vehicle_type') == 'motor' ? 'selected' : '' }}>Motor</option>
                    </select>
                    @error('vehicle_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Harga</label>
                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price') }}" required min="0">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Gambar Layanan</label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Layanan</button>
                <a href="{{ route('wash.services.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<!-- Sticky Mobile Action Bar -->
<div class="position-fixed bottom-0 start-0 end-0 bg-body border-top shadow d-md-none" style="z-index: 1030;">
    <div class="container py-2">
        <div class="d-flex gap-2">
            <a href="{{ route('wash.services.index') }}" class="btn btn-outline-secondary w-50">Batal</a>
            <button type="submit" class="btn btn-primary w-50" form="createServiceForm">Tambah Layanan</button>
        </div>
    </div>
</div>
@endsection
