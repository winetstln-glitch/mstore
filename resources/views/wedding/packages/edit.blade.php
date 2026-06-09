@extends('layouts.app')

@section('title', __('Edit Paket Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Paket Wedding</h4>
        <a href="{{ route('wedding.packages.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('wedding.packages.update', $package) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Nama Paket</label>
                    <input name="name" class="form-control" value="{{ old('name', $package->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $package->description) }}</textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Harga</label>
                        <input name="price" type="number" class="form-control" value="{{ old('price', (int) $package->price) }}" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kapasitas</label>
                        <input name="capacity" type="number" class="form-control" value="{{ old('capacity', $package->capacity) }}" min="0">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" {{ old('is_active', $package->is_active ? '1' : '') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Fasilitas (1 baris = 1 item)</label>
                    <textarea name="facilities_text" class="form-control" rows="5">{{ old('facilities_text', implode("\n", $package->facilities ?? [])) }}</textarea>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

