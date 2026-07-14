@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('whatsapp.builder.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
            <h1 class="h3 mb-0">
                <i class="fab fa-whatsapp text-success"></i> Edit Menu WhatsApp
            </h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.builder.update', $menu) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Keyword</label>
                        <input type="text" name="keyword" class="form-control @error('keyword') is-invalid @enderror" required placeholder="contoh: halo, jadwal hari ini" value="{{ old('keyword', $menu->keyword) }}">
                        @error('keyword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipe Balasan</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required id="menuType">
                            <option value="text" {{ old('type', $menu->type) === 'text' ? 'selected' : '' }}>Teks</option>
                            <option value="image" {{ old('type', $menu->type) === 'image' ? 'selected' : '' }}>Gambar</option>
                            <option value="document" {{ old('type', $menu->type) === 'document' ? 'selected' : '' }}>Dokumen</option>
                            <option value="button" {{ old('type', $menu->type) === 'button' ? 'selected' : '' }}>Tombol</option>
                            <option value="list" {{ old('type', $menu->type) === 'list' ? 'selected' : '' }}>List</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Teks Balasan</label>
                    <textarea name="response_text" class="form-control @error('response_text') is-invalid @enderror" rows="5" placeholder="Masukkan teks balasan...">{{ old('response_text', $menu->response_text) }}</textarea>
                    <small class="text-muted">
                        Variabel yang bisa digunakan: {nama_user}, {jam_sekarang}, {tanggal_sekarang}
                    </small>
                    @error('response_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" id="fileUploadSection" style="{{ in_array(old('type', $menu->type), ['image', 'document']) ? 'display: block;' : 'display: none;' }}">
                    <label class="form-label fw-bold">File Media</label>
                    @if($menu->file_path)
                        <div class="mb-2">
                            @if($menu->file_type && str_starts_with($menu->file_type, 'image/'))
                                <img src="{{ asset('storage/' . $menu->file_path) }}" class="img-thumbnail" style="max-width: 200px;">
                            @else
                                <a href="{{ asset('storage/' . $menu->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-download"></i> Lihat File
                                </a>
                            @endif
                        </div>
                    @endif
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" id="menuFile">
                    <small class="text-muted">Maksimal 10 MB (kosongkan jika tidak ingin mengubah file)</small>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Priority</label>
                        <input type="number" name="priority" class="form-control @error('priority') is-invalid @enderror" value="{{ old('priority', $menu->priority) }}" min="0">
                        <small class="text-muted">Semakin tinggi angka, semakin diprioritaskan</small>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="enableFuzzyMatch" name="enable_fuzzy_match" value="1" {{ old('enable_fuzzy_match', $menu->enable_fuzzy_match) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableFuzzyMatch">Enable Fuzzy Matching</label>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="isActive" name="is_active" value="1" {{ old('is_active', $menu->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Menu Aktif</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-save"></i> Update Menu
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('menuType').addEventListener('change', function() {
    const fileSection = document.getElementById('fileUploadSection');
    if (this.value === 'image' || this.value === 'document') {
        fileSection.style.display = 'block';
    } else {
        fileSection.style.display = 'none';
    }
});
</script>
@endsection
