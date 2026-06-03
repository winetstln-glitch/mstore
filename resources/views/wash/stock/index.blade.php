@extends('layouts.app')

@section('title', 'Stok Wash & Caffe')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Daftar Stok Wash & Caffe</h1>
        <a href="{{ route('wash.stock.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Item
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Satuan</th>
                            <th>Stok Saat Ini</th>
                            <th>Min. Stok</th>
                            <th>Harga Beli Terakhir</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->category ?? '-' }}</td>
                            <td>{{ $item->unit }}</td>
                            <td class="{{ $item->current_stock <= ($item->minimum_stock ?? 0) ? 'text-danger fw-bold' : '' }}">
                                {{ number_format($item->current_stock, 2, ',', '.') }}
                            </td>
                            <td>{{ $item->minimum_stock ? number_format($item->minimum_stock, 2, ',', '.') : '-' }}</td>
                            <td>{{ $item->last_buy_price ? 'Rp ' . number_format($item->last_buy_price, 0, ',', '.') : '-' }}</td>
                            <td>
                                <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('wash.stock.show', $item) }}" class="btn btn-sm btn-outline-info" title="Detail & Riwayat">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('wash.stock.edit', $item) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('wash.stock.stock-in', $item) }}" class="btn btn-sm btn-outline-success" title="Tambah Stok">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                    <form method="POST" action="{{ route('wash.stock.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus item ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection
