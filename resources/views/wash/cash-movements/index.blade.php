@extends('layouts.app')

@section('title', 'Mutasi Kas Wash')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Riwayat Mutasi Kas Wash & Caffe</h1>
        <a href="{{ route('wash.cash-movements.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Mutasi
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Waktu</th>
                            <th>Kasir Utama</th>
                            <th>Tipe</th>
                            <th>Jumlah</th>
                            <th>Referensi</th>
                            <th>Keterangan</th>
                            <th>Pengguna</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $movement)
                        <tr>
                            <td>{{ $movement->movement_date->format('d-m-Y H:i') }}</td>
                            <td>{{ $movement->cashRegister->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $movement->type === 'in' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $movement->type === 'in' ? 'Masuk' : 'Keluar' }}
                                </span>
                            </td>
                            <td>Rp {{ number_format($movement->amount, 0, ',', '.') }}</td>
                            <td>{{ $movement->reference_no ?? '-' }}</td>
                            <td>{{ $movement->description }}</td>
                            <td>{{ $movement->user->name }}</td>
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
