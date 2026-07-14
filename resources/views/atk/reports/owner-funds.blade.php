@extends('layouts.app')

@section('title', 'Laporan Dana Talangan')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Dana Talangan</h1>
        <div>
            <a href="{{ route('atk.reports.owner-funds.pdf', request()->query()) }}" class="btn btn-danger me-2"><i class="fas fa-file-pdf"></i> PDF</a>
            <a href="{{ route('atk.reports.owner-funds.excel', request()->query()) }}" class="btn btn-success me-2"><i class="fas fa-file-excel"></i> Excel</a>
            <a href="{{ route('atk.dashboard') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('atk.reports.owner-funds') }}" method="GET">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Tanggal Awal</label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', (is_object($start) ? $start->format('Y-m-d') : $start)) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', (is_object($end) ? $end->format('Y-m-d') : $end)) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        </select>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Dana Masuk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($incoming,0,',','.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Pengembalian</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($outgoing,0,',','.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Saldo Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($currentBalance,0,',','.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Saldo</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($funds as $fund)
                        <tr>
                            <td>{{ $fund->transaction_date->format('d-m-Y') }}</td>
                            <td>{{ $fund->transaction_code }}</td>
                            <td><span class="badge {{ $fund->type === 'loan' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($fund->type) }}</span></td>
                            <td>Rp {{ number_format($fund->amount,0,',','.') }}</td>
                            <td>Rp {{ number_format($fund->balance,0,',','.') }}</td>
                            <td>{{ $fund->description }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $funds->links() }}
        </div>
    </div>
</div>
@endsection
