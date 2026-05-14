@extends('layouts.app')
@section('title', 'Pengeluaran Wash')
@section('content')
@php
    $stockCategoryLabels = [
        'shampoo' => 'Sampo Wash',
        'snack' => 'Snack',
        'kopi' => 'Caffe',
        'caffe' => 'Caffe',
        'lainnya' => 'Lainnya',
    ];
@endphp
<div class="container-fluid py-3 wash-expenses-page">
    <div class="d-flex justify-content-between align-items-center mb-3 expenses-header">
        <h5 class="m-0">Pengeluaran Wash</h5>
        <a href="{{ route('wash.expenses.create') }}" class="btn btn-primary">Tambah Pengeluaran</a>
    </div>
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(($hasStockTables ?? false) === false)
    <div class="alert alert-warning">
        Modul stok wash belum aktif penuh. Jalankan <strong>php artisan migrate --force</strong> pada server ini untuk mengaktifkan stok sampo/snack/caffe.
    </div>
    @endif
    <div class="card expenses-panel mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        <option value="shampoo" {{ ($category ?? '') === 'shampoo' ? 'selected' : '' }}>Sampo Wash</option>
                        <option value="snack" {{ ($category ?? '') === 'snack' ? 'selected' : '' }}>Snack</option>
                        <option value="caffe" {{ in_array(($category ?? ''), ['caffe', 'kopi'], true) ? 'selected' : '' }}>Caffe</option>
                        <option value="lainnya" {{ ($category ?? '') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-dark btn-sm"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                </div>
                <div class="col ms-auto text-md-end">
                    @if(($hasStockTables ?? false) && ($lowStockCount ?? 0) > 0)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                            Peringatan: {{ $lowStockCount }} item stok minimum
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>
    <div class="card expenses-panel mb-3">
        <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
            <span>Stok Sampo Wash, Snack, Caffe</span>
            @if($hasStockTables ?? false)
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#stockOutModal">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Pemakaian Stok
                </button>
            @endif
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Item</th>
                        <th>Stok Saat Ini</th>
                        <th>Satuan</th>
                        <th>Harga Beli Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($stockItems ?? []) as $item)
                    <tr>
                        <td>{{ $stockCategoryLabels[strtolower((string) $item->category)] ?? ucfirst((string) $item->category) }}</td>
                        <td>{{ $item->name }}</td>
                        <td>
                            @if((float)$item->current_stock <= (float)$item->minimum_stock)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ (float)$item->current_stock }}</span>
                            @else
                                <span class="badge bg-info-subtle text-info border border-info-subtle">{{ (float)$item->current_stock }}</span>
                            @endif
                        </td>
                        <td>{{ $item->unit }}</td>
                        <td>Rp {{ number_format((float)$item->last_buy_price,0,',','.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">Belum ada stok tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($hasStockTables ?? false)
    <div class="card expenses-panel mb-3">
        <div class="card-header bg-light fw-semibold">Riwayat Pergerakan Stok (IN/OUT)</div>
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Item</th>
                        <th>Tipe</th>
                        <th>Qty</th>
                        <th>Nilai</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($stockMovements ?? []) as $mv)
                    <tr>
                        <td>{{ optional($mv->movement_date)->format('Y-m-d') }}</td>
                        <td>{{ $mv->stockItem?->name ?? '-' }}</td>
                        <td>
                            @if($mv->movement_type === 'out')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">OUT</span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle">IN</span>
                            @endif
                        </td>
                        <td>{{ (float)$mv->quantity }} {{ $mv->stockItem?->unit }}</td>
                        <td>{{ $mv->total_amount ? 'Rp '.number_format((float)$mv->total_amount,0,',','.') : '-' }}</td>
                        <td>{{ $mv->notes ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada riwayat stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
    <div class="card expenses-panel">
        <div class="card-body table-responsive table-responsive-mobile">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        @if($hasStockTables ?? false)
                            <th>Kategori</th>
                            <th>Item</th>
                            <th>Qty</th>
                        @endif
                        <th>Deskripsi</th>
                        <th>Nominal</th>
                        <th>Ref</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $e)
                    <tr>
                        <td>{{ $e->transaction_date->format('Y-m-d') }}</td>
                        @if($hasStockTables ?? false)
                            <td>{{ $e->category }}</td>
                            <td>{{ $e->washStockMovement?->stockItem?->name ?? '-' }}</td>
                            <td>{{ $e->washStockMovement ? ((float)$e->washStockMovement->quantity.' '.$e->washStockMovement->stockItem?->unit) : '-' }}</td>
                        @endif
                        <td>{{ $e->description }}</td>
                        <td>Rp {{ number_format($e->amount,0,',','.') }}</td>
                        <td><span class="badge bg-secondary">{{ $e->reference_number }}</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('wash.expenses.edit', $e->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('wash.expenses.destroy', $e->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pengeluaran ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ ($hasStockTables ?? false) ? 9 : 6 }}" class="text-center text-muted">Belum ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-0 pt-2">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@if($hasStockTables ?? false)
<div class="modal fade" id="stockOutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('wash.expenses.stock_out') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Catat Pemakaian Stok</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small">
                        Pilih stok existing, atau isi "Item Baru" jika belum ada di daftar.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Stok</label>
                        <select name="wash_stock_item_id" class="form-select">
                            <option value="">-- Buat item baru di bawah --</option>
                            @foreach(($stockItems ?? []) as $item)
                                <option value="{{ $item->id }}">{{ $stockCategoryLabels[strtolower((string) $item->category)] ?? ucfirst((string) $item->category) }} - {{ $item->name }} ({{ (float)$item->current_stock }} {{ $item->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label">Item Baru (opsional)</label>
                            <input type="text" name="new_item_name" class="form-control" placeholder="Contoh: Caffe sachet premium">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kategori</label>
                            <select name="new_item_category" class="form-select">
                                <option value="shampoo">Sampo Wash</option>
                                <option value="snack">Snack</option>
                                <option value="caffe">Caffe</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="new_item_unit" class="form-control" placeholder="pcs">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Min Stok</label>
                            <input type="number" min="0" step="0.01" name="new_item_minimum_stock" class="form-control" placeholder="0">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="movement_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Pakai</label>
                            <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" class="form-control" placeholder="Pemakaian operasional wash">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@push('styles')
<style>
    .wash-expenses-page .expenses-panel {
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 1rem;
        overflow: hidden;
    }

    .wash-expenses-page .table thead th {
        background: rgba(148, 163, 184, 0.12);
    }

    @media (max-width: 767.98px) {
        .wash-expenses-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }

        .wash-expenses-page .expenses-header {
            flex-direction: column;
            align-items: stretch !important;
            gap: 0.6rem;
        }

        .wash-expenses-page .expenses-header .btn {
            width: 100%;
            min-height: 42px;
            border-radius: 0.75rem;
        }

        .wash-expenses-page .table-responsive {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.9rem;
            padding: 0.25rem;
        }

        .wash-expenses-page .table-responsive-mobile td {
            align-items: flex-start;
            gap: 0.55rem;
        }
    }

    [data-bs-theme="dark"] .wash-expenses-page .expenses-panel {
        border-color: rgba(96, 165, 250, 0.28);
    }
</style>
@endpush
@endsection
