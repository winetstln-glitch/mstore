@extends('layouts.app')

@section('title', 'Detail Mutasi Kas')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Mutasi Kas</h1>
        <a href="{{ route('wash.cash-movements.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <table class="table table-borderless">
                <tr><th>Waktu</th><td>{{ $movement->movement_date->format('d-m-Y H:i') }}</td></tr>
                <tr><th>Kasir Utama</th><td>{{ $movement->cashRegister->name ?? '-' }}</td></tr>
                <tr><th>Tipe</th><td>
                    <span class="badge {{ $movement->type === 'in' ? 'bg-success' : 'bg-danger' }}">
                        {{ $movement->type === 'in' ? 'Kas Masuk' : 'Kas Keluar' }}
                    </span>
                </td></tr>
                <tr><th>Jumlah</th><td class="fw-bold">Rp {{ number_format($movement->amount, 0, ',', '.') }}</td></tr>
                <tr><th>Referensi</th><td>{{ $movement->reference_no ?? '-' }}</td></tr>
                <tr><th>Keterangan</th><td>{{ $movement->description }}</td></tr>
                <tr><th>Pengguna</th><td>{{ $movement->user->name }}</td></tr>
                @if($movement->shiftSession)
                <tr><th>Sesi Shift</th><td>{{ $movement->shiftSession->shift->name ?? '-' }} ({{ $movement->shiftSession->user->name ?? '-' }})</td></tr>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
