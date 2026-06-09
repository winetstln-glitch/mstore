@extends('layouts.app')

@section('title', __('Edit Paket CCTV'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Paket CCTV</h4>
        <a href="{{ route('cctv.packages.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('cctv.packages.update', $package) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Paket</label>
                        <input name="name" class="form-control" value="{{ old('name', $package->name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jumlah Kamera</label>
                        <input name="camera_count" type="number" class="form-control" value="{{ old('camera_count', $package->camera_count) }}" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Garansi (bulan)</label>
                        <input name="warranty_months" type="number" class="form-control" value="{{ old('warranty_months', $package->warranty_months) }}" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DVR/NVR</label>
                        <input name="dvr_nvr" class="form-control" value="{{ old('dvr_nvr', $package->dvr_nvr) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">HDD</label>
                        <input name="hdd" class="form-control" value="{{ old('hdd', $package->hdd) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga</label>
                        <input name="price" type="number" class="form-control" value="{{ old('price', (int) $package->price) }}" min="0" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" {{ old('is_active', $package->is_active ? '1' : '') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

