@extends('layouts.app')

@section('title', 'Laporan Kas Harian')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Kas Harian</h1>
        <div>
            <a href="{{ route('atk.reports.cash.pdf', request()->query()) }}" class="btn btn-danger me-2"><i class="fas fa-file-pdf"></i> PDF</a>
            <a href="{{ route('atk.reports.cash.excel', request()->query()) }}" class="btn btn-success me-2"><i class="fas fa-file-excel"></i> Excel</a>
            <a href="{{ route('atk.dashboard') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('atk.reports.cash') }}" method="GET">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label>Periode</label>
                        <select name="period" class="form-control">
                            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Range</option>
                            <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Harian</option>
                            <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                            <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Tanggal Awal</label>
                        <input type="date" name="start_date" class="form-control" value="{{ is_object($start) ? $start->format('Y-m-d') : $start }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" value="{{ is_object($end) ? $end->format('Y-m-d') : $end }}">
                    </div>
                    <div class="col-md-3 mb-3 align-self-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
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
        <div class="col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kas Masuk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($incoming,0,',','.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Kas Keluar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($outgoing,0,',','.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
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
</div>
@endsection
