@extends('layouts.app')

@section('title', 'Tambah Stok: ' . $stockItem->name)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Tambah Stok {{ $stockItem->name }}</h1>
        <a href="{{ route('wash.stock.show', $stockItem) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="mb-4 p-3 bg-light rounded">
                <p class="mb-0">
                    <strong>Stok saat ini:</strong> {{ number_format($stockItem->current_stock, 2, ',', '.') }} {{ $stockItem->unit }}
                </p>
                <p class="mb-0">
                    <strong>Harga beli terakhir:</strong> {{ $stockItem->last_buy_price ? 'Rp ' . number_format($stockItem->last_buy_price, 0, ',', '.') : '-' }}
                </p>
            </div>
            
            <form method="POST" action="{{ route('wash.stock.stock-in.store', $stockItem) }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jumlah Masuk *</label>
                        <input type="number" step="0.01" name="quantity" class="form-control" required min="0.01" placeholder="Jumlah stok masuk...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Harga Satuan *</label>
                        <input type="number" step="100" name="unit_price" class="form-control" required min="0" value="{{ old('unit_price', $stockItem->last_buy_price) }}" placeholder="Harga beli per satuan...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal *</label>
                        <input type="date" name="movement_date" class="form-control" required value="{{ old('movement_date', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)..."></textarea>
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
