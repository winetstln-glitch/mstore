@extends('layouts.app')

@section('title', 'Kasir Wash')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Daftar Kasir Wash & Caffe</h1>
        <a href="{{ route('wash.cash-registers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Kasir
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
                            <th>Deskripsi</th>
                            <th>Saldo Saat Ini</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registers as $register)
                        <tr>
                            <td>{{ $register->code }}</td>
                            <td>{{ $register->name }}</td>
                            <td>{{ $register->description ?? '-' }}</td>
                            <td class="fw-bold">Rp {{ number_format($register->current_balance, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $register->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $register->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('wash.cash-registers.show', $register) }}" class="btn btn-sm btn-outline-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('wash.cash-registers.edit', $register) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('wash.cash-registers.destroy', $register) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus kasir ini?')">
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
            {{ $registers->links() }}
        </div>
    </div>
</div>
@endsection
