@extends('layouts.app')

@section('title', 'Laporan Float Account')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Float Account</h1>
        <div>
            <a href="{{ route('atk.reports.float.pdf', request()->query()) }}" class="btn btn-danger me-2"><i class="fas fa-file-pdf"></i> PDF</a>
            <a href="{{ route('atk.reports.float.excel', request()->query()) }}" class="btn btn-success me-2"><i class="fas fa-file-excel"></i> Excel</a>
            <a href="{{ route('atk.dashboard') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('atk.reports.float') }}" method="GET">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Akun Float</label>
                        <select name="account_id" class="form-control">
                            <option value="">Pilih Akun</option>
                            @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->name }} ({{ $acc->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Tanggal Awal</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedAccount)
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Saldo Awal</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($startBalance,0,',','.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Masuk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalIn,0,',','.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Keluar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalOut,0,',','.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Saldo Akhir</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($endBalance,0,',','.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detail Transaksi Float - {{ $selectedAccount->name }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 15%;">Tanggal</th>
                            <th style="width: 15%;">Referensi</th>
                            <th style="width: 30%;">Keterangan</th>
                            <th class="text-end" style="width: 10%;">Debit</th>
                            <th class="text-end" style="width: 10%;">Kredit</th>
                            <th class="text-end" style="width: 15%;">Saldo Berjalan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $index => $transaction)
                            @php
                                $isIncoming = in_array($transaction->transaction_type, ['deposit', 'transfer_in', 'adjustment']);
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-secondary">{{ $transaction->transaction_type }}</span></td>
                                <td>{{ $transaction->description }}</td>
                                <td class="text-end">{{ $isIncoming ? 'Rp ' . number_format($transaction->amount, 0, ',', '.') : '-' }}</td>
                                <td class="text-end">{{ !$isIncoming ? 'Rp ' . number_format($transaction->amount, 0, ',', '.') : '-' }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($transaction->running_balance, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">Tidak ada data transaksi</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
