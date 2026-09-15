@extends('layouts.app')

@section('title', 'Edit Paket Member')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('wash.member-packages.index') }}" class="btn btn-sm btn-outline-secondary me-3">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
        <h4 class="mb-0 fs-5">Edit Paket Member: {{ $package->name }}</h4>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('wash.member-packages.update', $package->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nama Paket <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $package->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Kode Paket <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $package->code) }}" required>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tipe Paket <span class="text-danger">*</span></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="wash" {{ old('type', $package->type) == 'wash' ? 'selected' : '' }}>Wash Saja</option>
                            <option value="wifi" {{ old('type', $package->type) == 'wifi' ? 'selected' : '' }}>WiFi Saja</option>
                            <option value="both" {{ old('type', $package->type) == 'both' ? 'selected' : '' }}>Kombinasi (Wash + WiFi)</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Harga (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', (int)$package->price) }}" min="0" required>
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Durasi (Hari)</label>
                        <input type="number" name="duration_days" class="form-control @error('duration_days') is-invalid @enderror" value="{{ old('duration_days', $package->duration_days) }}" min="1">
                        @error('duration_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $package->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Aktif</label>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <h6 class="fw-bold border-bottom pb-2">Pengaturan WiFi (Jika Tipe Termasuk WiFi)</h6>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Profil Hotspot</label>
                        <select name="hotspot_profile_id" class="form-select @error('hotspot_profile_id') is-invalid @enderror">
                            <option value="">-- Pilih Profil --</option>
                            @foreach($hotspotProfiles as $profile)
                            <option value="{{ $profile->id }}" {{ old('hotspot_profile_id', $package->hotspot_profile_id) == $profile->id ? 'selected' : '' }}>{{ $profile->name }}</option>
                            @endforeach
                        </select>
                        @error('hotspot_profile_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Router</label>
                        <select name="router_id" class="form-select @error('router_id') is-invalid @enderror">
                            <option value="">-- Pilih Router --</option>
                            @foreach($routers as $router)
                            <option value="{{ $router->id }}" {{ old('router_id', $package->router_id) == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                            @endforeach
                        </select>
                        @error('router_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100 w-md-auto">
                            <i class="fa-solid fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
