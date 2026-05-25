@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="fab fa-whatsapp text-success"></i> Tambah Menu WhatsApp
        </h1>
        <a href="{{ route('whatsapp.builder.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.builder.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Keyword</label>
                        <input type="text" name="keyword" class="form-control @error('keyword') is-invalid @enderror" required placeholder="contoh: halo, jadwal hari ini">
                        @error('keyword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipe Balasan</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required id="menuType">
                            <option value="text">Teks</option>
                            <option value="image">Gambar</option>
                            <option value="document">Dokumen</option>
                            <option value="button">Tombol</option>
                            <option value="list">List</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Teks Balasan</label>
                    <textarea name="response_text" class="form-control @error('response_text') is-invalid @enderror" rows="5" placeholder="Masukkan teks balasan...">{{ old('response_text') }}</textarea>
                    <small class="text-muted">
                        Variabel yang bisa digunakan: {nama_user}, {jam_sekarang}, {tanggal_sekarang}
                    </small>
                    @error('response_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" id="fileUploadSection" style="display: none;">
                    <label class="form-label fw-bold">File Media</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" id="menuFile">
                    <small class="text-muted">Maksimal 10 MB</small>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Priority</label>
                        <input type="number" name="priority" class="form-control @error('priority') is-invalid @enderror" value="0" min="0">
                        <small class="text-muted">Semakin tinggi angka, semakin diprioritaskan</small>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="enableFuzzyMatch" name="enable_fuzzy_match" value="1" checked>
                            <label class="form-check-label" for="enableFuzzyMatch">Enable Fuzzy Matching</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-save"></i> Simpan Menu
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
