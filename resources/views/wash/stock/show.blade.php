@extends('layouts.app')

@section('title', 'Detail Stok: ' . $stockItem->name)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ $stockItem->name }}</h1>
            <p class="text-muted mb-0">Stok saat ini: {{ number_format($stockItem->current_stock, 2, ',', '.') }} {{ $stockItem->unit }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('wash.stock.stock-in', $stockItem) }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Tambah Stok
            </a>
            <a href="{{ route('wash.stock.edit', $stockItem) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('wash.stock.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title text-muted">Kategori</h6>
                    <p class="h4">{{ $stockItem->category ?? '-' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title text-muted">Minimum Stok</h6>
                    <p class="h4">{{ $stockItem->minimum_stock ? number_format($stockItem->minimum_stock, 2, ',', '.') : '-' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title text-muted">Harga Beli Terakhir</h6>
                    <p class="h4">{{ $stockItem->last_buy_price ? 'Rp ' . number_format($stockItem->last_buy_price, 0, ',', '.') : '-' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title text-muted">Status</h6>
                    <p class="h4">
                        <span class="badge {{ $stockItem->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $stockItem->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Riwayat Pergerakan Stok</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Jumlah</th>
                            <th>Harga Satuan</th>
                            <th>Total</th>
                            <th>Catatan</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $movement)
                        <tr>
                            <td>{{ $movement->movement_date->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge {{ $movement->movement_type === 'in' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $movement->movement_type === 'in' ? 'Masuk' : 'Keluar' }}
                                </span>
                            </td>
                            <td>{{ number_format($movement->quantity, 2, ',', '.') }}</td>
                            <td>{{ $movement->unit_price ? 'Rp ' . number_format($movement->unit_price, 0, ',', '.') : '-' }}</td>
                            <td>{{ $movement->total_amount ? 'Rp ' . number_format($movement->total_amount, 0, ',', '.') : '-' }}</td>
                            <td>{{ $movement->notes ?? '-' }}</td>
                            <td>{{ $movement->user?->name ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $movements->links() }}
        </div>
    </div>
</div>
@endsection
