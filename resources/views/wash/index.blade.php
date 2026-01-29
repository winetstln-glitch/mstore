@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">Cuci Mobil & Motor - Dashboard</h1>
        </div>
        <div class="col-auto">
            <a href="{{ route('wash.pos') }}" class="btn btn-primary">
                <i class="fas fa-cash-register"></i> Ke Kasir (POS)
            </a>
            <a href="{{ route('wash.services.index') }}" class="btn btn-info">
                <i class="fas fa-list"></i> Kelola Layanan
            </a>
        </div>
    </div>

    <!-- Transaction List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Data</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('wash.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Atau Pilih Bulan</label>
                    <input type="month" name="month" class="form-control" value="{{ request('month') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('wash.index') }}" class="btn btn-secondary">
                        <i class="fas fa-undo"></i>
                    </a>
                    <button type="submit" formaction="{{ route('wash.export') }}" class="btn btn-success w-100">
                        <i class="fas fa-file-excel"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Riwayat Transaksi</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Layanan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $trx)
                        <tr>
                            <td>{{ $trx->transaction_code }}</td>
                            <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                {{ $trx->customer_name }}<br>
                                <small class="text-muted">{{ $trx->plate_number }}</small>
                            </td>
                            <td>
                                <ul class="pl-3 mb-0">
                                @foreach($trx->items as $item)
                                    <li>{{ $item->service->name }} (x{{ $item->quantity }})</li>
                                @endforeach
                                </ul>
                            </td>
                            <td>Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge badge-success">{{ ucfirst($trx->status) }}</span>
                            </td>
                            <td>
                                <a href="{{ route('wash.receipt', $trx->id) }}" target="_blank" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-print"></i> Struk
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
