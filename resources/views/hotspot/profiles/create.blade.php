@extends('layouts.app')

@section('title', __('Tambah Paket Hotspot'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fa-solid fa-plus-circle text-primary me-2"></i>Tambah Paket Hotspot</h4>
        <a href="{{ route('hotspot.profiles.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="card border-0 shadow-sm border-top border-4 border-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('hotspot.profiles.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Paket <span class="text-danger">*</span></label>
                        <input name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="contoh: Voucher 3 Jam" required maxlength="100">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipe Paket <span class="text-danger">*</span></label>
                        <select name="package_type" class="form-select @error('package_type') is-invalid @enderror" required>
                            <option value="voucher" @selected(old('package_type','voucher')==='voucher')>Voucher Harian</option>
                            <option value="member" @selected(in_array(old('package_type'), ['member','membership','hotspot'], true))>Member Hotspot</option>
                            <option value="pppoe" @selected(in_array(old('package_type'), ['pppoe','residential','home','rumahan'], true))>PPPoE Rumahan/Bisnis</option>
                        </select>
                        @error('package_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Router (opsional)</label>
                        <select name="router_id" class="form-select @error('router_id') is-invalid @enderror">
                            <option value="">-- Default (router aktif pertama) --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}" @selected(old('router_id')==$r->id)>{{ $r->name }}</option>
                            @endforeach
                        </select>
                        @error('router_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Nama Profile di RouterOS <span class="text-danger">*</span></label>
                        <input name="mikrotik_profile_name" class="form-control @error('mikrotik_profile_name') is-invalid @enderror"
                               value="{{ old('mikrotik_profile_name') }}" placeholder="contoh: voucher-2rb" maxlength="64" required>
                        @error('mikrotik_profile_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                        <input name="price" type="number" class="form-control @error('price') is-invalid @enderror"
                               value="{{ old('price', 0) }}" min="0" step="1" required>
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warna Badge</label>
                        <select name="color_badge" class="form-select">
                            <option value="">Tidak Ada</option>
                            <option value="green" @selected(old('color_badge')==='green')>Hijau</option>
                            <option value="blue" @selected(old('color_badge')==='blue')>Biru</option>
                            <option value="orange" @selected(old('color_badge')==='orange')>Oranye</option>
                            <option value="purple" @selected(old('color_badge')==='purple')>Ungu</option>
                            <option value="gold" @selected(old('color_badge')==='gold')>Emas</option>
                            <option value="yellow" @selected(old('color_badge')==='yellow')>Kuning</option>
                            <option value="gray" @selected(old('color_badge')==='gray')>Abu-Abu / Silver</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Kecepatan (Mbps)</label>
                        <input name="rate_limit_mbps" type="number" step="0.01" min="0" class="form-control"
                               value="{{ old('rate_limit_mbps', 5) }}" placeholder="5">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Durasi Sesi (lama penggunaan per sesi login)</label>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <div class="input-group">
                                    <input name="duration_hours" type="number" min="0" class="form-control"
                                           value="{{ old('duration_hours', 3) }}" placeholder="0">
                                    <span class="input-group-text">Jam</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="input-group">
                                    <input name="duration_days" type="number" min="0" class="form-control"
                                           value="{{ old('duration_days', 0) }}" placeholder="0">
                                    <span class="input-group-text">Hari</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <small class="text-muted d-block mt-2">Durasi = batas waktu penggunaan per sesi login. Kosongkan keduanya = tanpa batas durasi.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Masa Aktif Paket (masa berlaku voucher setelah didistribusikan)</label>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <div class="input-group">
                                    <input name="validity_hours" type="number" min="0" class="form-control"
                                           value="{{ old('validity_hours', 0) }}" placeholder="0">
                                    <span class="input-group-text">Jam</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="input-group">
                                    <input name="validity_days" type="number" min="0" class="form-control"
                                           value="{{ old('validity_days', 30) }}" placeholder="0">
                                    <span class="input-group-text">Hari</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <small class="text-muted d-block mt-2">Masa Aktif = waktu berlaku paket sejak dibuat/dijual. Kosongkan keduanya = tanpa batas masa aktif.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Limit Uptime RouterOS (opsional)</label>
                        <input name="limit_uptime" class="form-control" maxlength="32"
                               value="{{ old('limit_uptime') }}" placeholder="contoh: 3h, 7d, 30d">
                        <small class="text-muted">Kosongkan agar otomatis dari Durasi di atas.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kuota (MB)</label>
                        <input name="quota_mb" type="number" min="0" class="form-control"
                               value="{{ old('quota_mb') }}" placeholder="Kosongkan = Unlimited">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Shared Users (perangkat)</label>
                        <input name="shared_users" type="number" min="1" class="form-control"
                               value="{{ old('shared_users', 1) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Urutan Tampil</label>
                        <input name="sort_order" type="number" min="0" class="form-control"
                               value="{{ old('sort_order', 0) }}">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" @checked(old('is_active', '1'))>
                            <label class="form-check-label" for="is_active"><b>Tampilkan di halaman paket</b></label>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Deskripsi (tampil di captive portal)</label>
                        <textarea name="description" rows="2" class="form-control"
                                  placeholder="Contoh: Kuota Unlimited. Speed 5 Mbps. Berlaku 3 jam setelah login.">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa-solid fa-save me-1"></i>Simpan Paket
                    </button>
                    <a href="{{ route('hotspot.profiles.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
