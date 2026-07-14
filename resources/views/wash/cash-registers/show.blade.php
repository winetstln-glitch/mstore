@extends('layouts.app')

@section('title', 'Detail Kasir')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Kasir</h1>
        <a href="{{ route('wash.cash-registers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-cash-register"></i> Informasi Kasir
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th>Kode</th><td>{{ $register->code }}</td></tr>
                        <tr><th>Nama</th><td>{{ $register->name }}</td></tr>
                        <tr><th>Deskripsi</th><td>{{ $register->description ?? '-' }}</td></tr>
                        <tr><th>Saldo Saat Ini</th><td class="fw-bold">Rp {{ number_format($register->current_balance, 0, ',', '.') }}</td></tr>
                        <tr><th>Status</th><td>
                            <span class="badge {{ $register->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $register->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-door-open"></i> Sesi Shift Aktif
                </div>
                <div class="card-body">
                    @if($sessions->where('status', 'open')->count() > 0)
                    <ul class="list-group">
                        @foreach($sessions->where('status', 'open') as $session)
                        <li class="list-group-item">
                            <i class="fas fa-user mr-2"></i> {{ $session->user->name }}
                            <span class="badge bg-primary">Buka</span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-center text-muted">Tidak ada sesi shift aktif</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-history"></i> Riwayat Mutasi Kas
        </div>
        <div class="card-body">
            @if($movements->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Waktu</th>
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
            @else
            <p class="text-center text-muted">Belum ada mutasi kas</p>
            @endif
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('wash.cash-registers.edit', $register) }}" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit
        </a>
        <form method="POST" action="{{ route('wash.cash-registers.destroy', $register) }}" onsubmit="return confirm('Yakin ingin menghapus kasir ini?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash"></i> Hapus
            </button>
        </form>
    </div>
</div>
@endsection
