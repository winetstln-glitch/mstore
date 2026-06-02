@extends('layouts.app')

@section('title', 'Kelola Karyawan')

@section('content')
<div class="container-fluid wash-employees-page">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Karyawan Wash</h1>
        <a href="{{ route('wash.employees.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" title="Tambah Karyawan Baru">
            <i class="fas fa-plus fa-sm text-white-50"></i>
            <span class="d-none d-md-inline ms-1">Tambah Karyawan Baru</span>
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Karyawan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive table-responsive-mobile">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Telepon</th>
                            <th>Akun</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr>
                                <td>{{ $employee->name }}</td>
                                <td>{{ $employee->phone ?? '-' }}</td>
                                <td>{{ $employee->user?->name ?? '—' }}</td>
                                <td>
                                    @if($employee->status == 'active')
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($employee->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('wash.employees.edit', $employee->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('wash.employees.destroy', $employee->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus karyawan ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada karyawan ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
    .wash-employees-page {
        padding-left: 0.35rem;
        padding-right: 0.35rem;
    }

    @media (max-width: 767.98px) {
        .wash-employees-page .h3 {
            font-size: 1.15rem;
        }

        .wash-employees-page .card-header,
        .wash-employees-page .card-body {
            padding-left: 0.85rem;
            padding-right: 0.85rem;
        }

        .wash-employees-page .table-responsive {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.9rem;
            padding: 0.25rem;
        }

        .wash-employees-page .table-responsive-mobile td {
            align-items: flex-start;
            gap: 0.55rem;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }

        .wash-employees-page .table-responsive-mobile td::before {
            font-size: 0.68rem;
            letter-spacing: 0.25px;
        }

        .wash-employees-page .table-responsive-mobile td[data-label="Aksi"] {
            display: block;
            text-align: left;
        }

        .wash-employees-page .table-responsive-mobile td[data-label="Aksi"]::before {
            display: block;
            margin-bottom: 0.45rem;
        }

        .wash-employees-page .table-responsive-mobile td[data-label="Aksi"] .btn {
            min-height: 34px;
            min-width: 34px;
            border-radius: 0.65rem;
            padding: 0.32rem 0.48rem;
        }
    }
</style>
@endpush
@endsection
