@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">{{ __('Laporan Pembukuan Pengembang') }}</h1>
            <p class="text-muted mb-0">{{ __('Laporan pendapatan dan pengeluaran untuk manajemen/pengembang.') }}</p>
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
            <a href="{{ route('finance.developer.pdf', request()->all()) }}" class="btn btn-danger btn-lg w-100 w-md-auto" target="_blank">
                <i class="fas fa-file-pdf me-1"></i> {{ __('Export PDF') }}
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body rounded-3">
            <form action="{{ route('finance.developer') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="month" class="form-label fw-bold small text-muted">{{ __('Pilih Bulan') }}</label>
                    <input type="month" id="month" name="month" class="form-control form-control-lg" value="{{ $selectedMonth }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                        <i class="fas fa-filter me-1"></i> {{ __('Filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Summary Cards -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                {{ __('Total Pendapatan') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                {{ __('Total Pengeluaran') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('Laba Bersih') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($netProfit, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Income Breakdown -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Rincian Pendapatan') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-responsive-mobile" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>{{ __('Sumber Pendapatan') }}</th>
                                    <th class="text-end">{{ __('Jumlah') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Pendapatan ISP (25% dari Coordinator)</td>
                                    <td class="text-end text-success fw-bold">Rp {{ number_format($incomeISP, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Pendapatan Management (20% dari Coordinator)</td>
                                    <td class="text-end text-success fw-bold">Rp {{ number_format($incomeMgmt, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Penjualan Material Wifi</td>
                                    <td class="text-end text-success fw-bold">Rp {{ number_format($incomeMaterial, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Jasa Cuci Kendaraan</td>
                                    <td class="text-end text-success fw-bold">Rp {{ number_format($incomeWash, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Penjualan ATK</td>
                                    <td class="text-end text-success fw-bold">Rp {{ number_format($incomeATK, 0, ',', '.') }}</td>
                                </tr>
                                <tr class=" fw-bold">
                                    <td>TOTAL PENDAPATAN</td>
                                    <td class="text-end">Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense Breakdown -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">{{ __('Rincian Pengeluaran') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-responsive-mobile" width="100%" cellspacing="0">
                            <thead class="">
                                <tr>
                                    <th>{{ __('Jenis Pengeluaran') }}</th>
                                    <th class="text-end">{{ __('Jumlah') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Pembayaran Internet (ISP)</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($expenseISP, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Gaji (Teknisi & Staf)</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($expenseSalaries, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Pembelian Material</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($expenseMaterials, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Pembelian Alat Kerja</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($expenseTools, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Pengeluaran Jasa Cuci</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($expenseWash, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Pengeluaran Lainnya</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($expenseOthers, 0, ',', '.') }}</td>
                                </tr>
                                <tr class=" fw-bold">
                                    <td>TOTAL PENGELUARAN</td>
                                    <td class="text-end">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
