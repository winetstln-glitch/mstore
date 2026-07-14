@extends('layouts.app')

@section('title', 'Supplier Wash')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Daftar Supplier Wash & Caffe</h1>
        <a href="{{ route('wash.suppliers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Supplier
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Alamat</th>
                            <th>Telepon</th>
                            <th>Email</th>
                            <th>PIC</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->code }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->address ?? '-' }}</td>
                            <td>{{ $supplier->phone ?? '-' }}</td>
                            <td>{{ $supplier->email ?? '-' }}</td>
                            <td>{{ $supplier->pic ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $supplier->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('wash.suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('wash.suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('wash.suppliers.destroy', $supplier) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus supplier ini?')">
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
            {{ $suppliers->links() }}
        </div>
    </div>
</div>
@endsection
