@extends('layouts.app')

@section('title', 'Detail Sesi Shift')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Sesi Shift</h1>
        <a href="{{ route('wash.shift-sessions.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-door-open"></i> Informasi Sesi
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th>Shift</th><td>{{ $session->shift->name ?? '-' }}</td></tr>
                        <tr><th>Kasir</th><td>{{ $session->user->name }}</td></tr>
                        <tr><th>Kasir Utama</th><td>{{ $session->cashRegister->name ?? '-' }}</td></tr>
                        <tr><th>Waktu Buka</th><td>{{ $session->opened_at->format('d-m-Y H:i') }}</td></tr>
                        <tr><th>Waktu Tutup</th><td>{{ $session->closed_at ? $session->closed_at->format('d-m-Y H:i') : '-' }}</td></tr>
                        <tr><th>Status</th><td>
                            <span class="badge {{ $session->status === 'open' ? 'bg-primary' : 'bg-success' }}">
                                {{ $session->status === 'open' ? 'Buka' : 'Tutup' }}
                            </span>
                        </td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-money-bill-wave"></i> Keuangan
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th>Uang Kas Awal</th><td class="text-end fw-bold">Rp {{ number_format($session->opening_cash, 0, ',', '.') }}</td></tr>
                        <tr><th>Total Penjualan</th><td class="text-end fw-bold">Rp {{ number_format($session->total_sales, 0, ',', '.') }}</td></tr>
                        <tr><th>Total Pengeluaran</th><td class="text-end fw-bold text-danger">Rp {{ number_format($session->total_expenses, 0, ',', '.') }}</td></tr>
                        <tr><th>Uang Kas Seharusnya</th><td class="text-end fw-bold text-primary">Rp {{ number_format($session->opening_cash + $session->total_sales - $session->total_expenses, 0, ',', '.') }}</td></tr>
                        @if($session->status === 'closed')
                        <tr><th>Uang Kas Akhir</th><td class="text-end fw-bold">Rp {{ number_format($session->closing_cash, 0, ',', '.') }}</td></tr>
                        <tr><th>Selisih Kas</th><td class="text-end fw-bold {{ $session->cash_difference > 0 ? 'text-success' : ($session->cash_difference < 0 ? 'text-danger' : '') }}">
                            Rp {{ number_format($session->cash_difference, 0, ',', '.') }}
                        </td></tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($session->notes)
    <div class="card shadow mb-4">
        <div class="card-header bg-secondary text-white">
            <i class="fas fa-sticky-note"></i> Catatan
        </div>
        <div class="card-body">
            {{ $session->notes }}
        </div>
    </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-history"></i> Riwayat Transaksi & Mutasi
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="true">
                        Transaksi Penjualan
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="movements-tab" data-bs-toggle="tab" data-bs-target="#movements" type="button" role="tab" aria-controls="movements" aria-selected="false">
                        Mutasi Kas
                    </button>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active py-3" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                    @if($session->transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Pelanggan</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($session->transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->transaction_number }}</td>
                                    <td>{{ $transaction->customer_name ?? '-' }}</td>
                                    <td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted">Belum ada transaksi di sesi ini</p>
                    @endif
                </div>
                <div class="tab-pane fade py-3" id="movements" role="tabpanel" aria-labelledby="movements-tab">
                    @if($session->cashMovements->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Tipe</th>
                                    <th>Jumlah</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($session->cashMovements as $movement)
                                <tr>
                                    <td>{{ $movement->movement_date->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <span class="badge {{ $movement->type === 'in' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $movement->type === 'in' ? 'Masuk' : 'Keluar' }}
                                        </span>
                                    </td>
                                    <td>Rp {{ number_format($movement->amount, 0, ',', '.') }}</td>
                                    <td>{{ $movement->description }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted">Belum ada mutasi kas di sesi ini</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        @if($session->status === 'open')
        <a href="{{ route('wash.shift-sessions.edit', $session) }}" class="btn btn-danger">
            <i class="fas fa-door-closed"></i> Tutup Shift
        </a>
        @endif
    </div>
</div>
@endsection
