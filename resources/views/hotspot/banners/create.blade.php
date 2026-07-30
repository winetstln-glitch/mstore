@extends('layouts.app')

@section('title', __('Tambah Banner Hotspot'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fa-solid fa-plus-circle text-primary me-2"></i>Tambah Banner Hotspot</h4>
        <a href="{{ route('hotspot.banners.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="card border-0 shadow-sm border-top border-4 border-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('hotspot.banners.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Judul Banner <span class="text-danger">*</span></label>
                        <input name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" placeholder="Maks. 150 karakter" required maxlength="150">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Target Halaman <span class="text-danger">*</span></label>
                        <select name="page_target" class="form-select @error('page_target') is-invalid @enderror" required>
                            @foreach($pageTargets as $val => $label)
                                <option value="{{ $val }}" @selected(old('page_target','all')===$val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('page_target')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Sub Judul</label>
                        <input name="subtitle" class="form-control @error('subtitle') is-invalid @enderror"
                               value="{{ old('subtitle') }}" placeholder="Opsional. Maks. 255 karakter" maxlength="255">
                        @error('subtitle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Gambar Banner <span class="text-danger">*</span></label>
                        <input name="image" type="file" accept="image/*"
                               class="form-control @error('image') is-invalid @enderror" required>
                        <small class="text-muted d-block mt-1">Format: JPG, PNG, WebP. Maks. 5MB. Rasio rekomendasi: 16:9 atau 4:3.</small>
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gambar Mobile (Opsional)</label>
                        <input name="mobile_image" type="file" accept="image/*"
                               class="form-control @error('mobile_image') is-invalid @enderror">
                        <small class="text-muted d-block mt-1">Khusus tampilan mobile. Kosongkan untuk pakai gambar utama.</small>
                        @error('mobile_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Teks Tombol (CTA)</label>
                        <input name="cta_text" class="form-control @error('cta_text') is-invalid @enderror"
                               value="{{ old('cta_text') }}" placeholder="Contoh: Beli Sekarang, Lihat Detail" maxlength="80">
                        @error('cta_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">URL Tombol (CTA)</label>
                        <input name="url_cta" type="url" class="form-control @error('url_cta') is-invalid @enderror"
                               value="{{ old('url_cta') }}" placeholder="https://..." maxlength="500">
                        @error('url_cta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Urutan Tampil</label>
                        <input name="sort_order" type="number" min="0" max="255"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               value="{{ old('sort_order', 0) }}">
                        <small class="text-muted">Angka kecil = tampil lebih dulu.</small>
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" value="1" id="open_new_tab" name="open_new_tab" @checked(old('open_new_tab', '1'))>
                            <label class="form-check-label" for="open_new_tab"><b>Buka di tab baru</b></label>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" @checked(old('is_active', '1'))>
                            <label class="form-check-label" for="is_active"><b>Aktifkan Banner</b></label>
                        </div>
                    </div>
                    <div class="col-md-3"></div>

                    <div class="col-md-6">
                        <label class="form-label">Tanggal Mulai Tayang</label>
                        <input name="start_date" type="date" class="form-control @error('start_date') is-invalid @enderror"
                               value="{{ old('start_date') }}">
                        <small class="text-muted">Kosongkan = mulai tayang sekarang.</small>
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Selesai Tayang</label>
                        <input name="end_date" type="date" class="form-control @error('end_date') is-invalid @enderror"
                               value="{{ old('end_date') }}">
                        <small class="text-muted">Kosongkan = tayang selamanya.</small>
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa-solid fa-save me-1"></i>Simpan Banner
                    </button>
                    <a href="{{ route('hotspot.banners.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
