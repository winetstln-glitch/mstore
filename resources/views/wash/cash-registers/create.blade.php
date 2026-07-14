@extends('layouts.app')

@section('title', 'Tambah Kasir')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Tambah Kasir Baru</h1>
        <a href="{{ route('wash.cash-registers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('wash.cash-registers.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Nama Kasir <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Contoh: Kasir Utama, Kasir Caffe" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi</label>
                    <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label for="current_balance" class="form-label">Saldo Awal <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="current_balance" id="current_balance" class="form-control" value="0" step="100" required>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                        <label class="form-check-label" for="is_active">
                            Aktif
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
