@extends('layouts.app')

@section('title', 'Detail Supplier')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Supplier</h1>
        <a href="{{ route('wash.suppliers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-building"></i> Informasi Supplier
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Kode</th>
                            <td>{{ $supplier->code }}</td>
                        </tr>
                        <tr>
                            <th>Nama</th>
                            <td>{{ $supplier->name }}</td>
                        </tr>
                        <tr>
                            <th>Alamat</th>
                            <td>{{ $supplier->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Telepon</th>
                            <td>{{ $supplier->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $supplier->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>PIC</th>
                            <td>{{ $supplier->pic ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge {{ $supplier->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-boxes"></i> Stok Item dari Supplier Ini
                </div>
                <div class="card-body">
                    @if($stockItems->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockItems as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ number_format($item->current_stock, 2, ',', '.') }} {{ $item->unit }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted">Tidak ada stok item dari supplier ini</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('wash.suppliers.edit', $supplier) }}" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit
        </a>
        <form method="POST" action="{{ route('wash.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Yakin ingin menghapus supplier ini?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash"></i> Hapus
            </button>
        </form>
    </div>
</div>
@endsection
