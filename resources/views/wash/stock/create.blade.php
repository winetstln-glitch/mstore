@extends('layouts.app')

@section('title', 'Tambah Item Stok')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Tambah Item Stok</h1>
        <a href="{{ route('wash.stock.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('wash.stock.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Item *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Nama item stok...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <input type="text" name="category" class="form-control" placeholder="Kategori (opsional)...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Satuan *</label>
                        <input type="text" name="unit" class="form-control" required placeholder="Pcs, Botol, dll...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Stok Awal *</label>
                        <input type="number" step="0.01" name="current_stock" class="form-control" required value="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Minimum Stok</label>
                        <input type="number" step="0.01" name="minimum_stock" class="form-control" placeholder="Minimal stok (opsional)...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Harga Beli Terakhir</label>
                        <input type="number" step="100" name="last_buy_price" class="form-control" placeholder="Harga beli (opsional)...">
                    </div>
                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                            <label for="is_active" class="form-check-label">Aktif</label>
                        </div>
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
