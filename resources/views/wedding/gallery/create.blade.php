@extends('layouts.app')

@section('title', __('Tambah Foto Galeri Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Tambah Foto Galeri Wedding</h4>
        <a href="{{ route('wedding.gallery.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('wedding.gallery.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Foto</label>
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', 0) }}" min="0">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Caption (opsional)</label>
                        <input type="text" name="caption" class="form-control @error('caption') is-invalid @enderror" value="{{ old('caption') }}" placeholder="Contoh: Dekor akad indoor">
                        @error('caption')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', '1') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktifkan tampil di landing</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Simpan</button>
                        <a class="btn btn-outline-secondary" href="{{ route('wedding.gallery.index') }}">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

