@extends('layouts.app')

@section('title', 'Edit Item Stok')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Item Stok</h1>
        <a href="{{ route('wash.stock.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('wash.stock.update', $stockItem) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Item *</label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name', $stockItem->name) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <input type="text" name="category" class="form-control" value="{{ old('category', $stockItem->category) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Satuan *</label>
                        <input type="text" name="unit" class="form-control" required value="{{ old('unit', $stockItem->unit) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Stok Saat Ini *</label>
                        <input type="number" step="0.01" name="current_stock" class="form-control" required value="{{ old('current_stock', $stockItem->current_stock) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Minimum Stok</label>
                        <input type="number" step="0.01" name="minimum_stock" class="form-control" value="{{ old('minimum_stock', $stockItem->minimum_stock) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Harga Beli Terakhir</label>
                        <input type="number" step="100" name="last_buy_price" class="form-control" value="{{ old('last_buy_price', $stockItem->last_buy_price) }}">
                    </div>
                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" {{ old('is_active', $stockItem->is_active) ? 'checked' : '' }}>
                            <label for="is_active" class="form-check-label">Aktif</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
