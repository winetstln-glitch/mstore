@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Neraca Awal') }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali ke Keuangan') }}
            </a>
            <a href="{{ route('finance.manager_report.excel', ['month' => request('month')]) }}" class="btn btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> {{ __('Unduh Excel') }}
            </a>
            <a href="{{ route('finance.manager_report.pdf', ['month' => request('month')]) }}" class="btn btn-danger">
                <i class="fa-solid fa-file-pdf me-1"></i> {{ __('Unduh PDF') }}
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa-solid fa-print me-1"></i> {{ __('Cetak Laporan') }}
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Periode Laporan') }}</h6>
            <form action="{{ route('finance.manager_report') }}" method="GET" class="d-flex align-items-center">
                <select name="coordinator_id" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                    <option value="">{{ __('Semua Pengurus') }}</option>
                    @foreach($coordinators as $coordinator)
                        <option value="{{ $coordinator->id }}" {{ isset($selectedCoordinatorId) && $selectedCoordinatorId == $coordinator->id ? 'selected' : '' }}>
                            {{ $coordinator->name }}
                        </option>
                    @endforeach
                </select>
                <input type="month" name="month" class="form-control form-control-sm me-2" value="{{ request('month') }}" onchange="this.form.submit()">
            </form>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card border-left-success h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Pendapatan Member') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($memberIncome, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-left-success h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Pendapatan Voucher') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($voucherIncome, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-left-success h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Total Pendapatan') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalRevenue, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card border-left-info h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('Komisi Pengurus') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">-{{ number_format($coordCommission, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card border-left-danger h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">{{ __('Pengeluaran Pengurus') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">-{{ number_format($operatingExpenses, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card border-left-primary h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ __('Sudah Disetor') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">-{{ number_format($deposited, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card border-left-success h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Sisa Saldo (Wajib Setor)') }}</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($netBalance, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td>{{ __('Pendapatan Member') }}</td>
                            <td class="text-end">{{ number_format($memberIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Pendapatan Voucher') }}</td>
                            <td class="text-end">{{ number_format($voucherIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold table-success">
                            <td>{{ __('Total Pendapatan') }}</td>
                            <td class="text-end">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Komisi Pengurus (±' . $coordRate . '%)') }}</td>
                            <td class="text-end text-danger">-{{ number_format($coordCommission, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Pengeluaran Transportasi') }}</td>
                            <td class="text-end text-danger">-{{ number_format($transportExpenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Pengeluaran Konsumsi') }}</td>
                            <td class="text-end text-danger">-{{ number_format($consumptionExpenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Pengeluaran Perbaikan') }}</td>
                            <td class="text-end text-danger">-{{ number_format($repairExpenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Pengeluaran Lainnya') }}</td>
                            <td class="text-end text-danger">-{{ number_format($otherOperatingExpenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold">
                            <td>{{ __('Total Pengeluaran Pengurus') }}</td>
                            <td class="text-end text-danger">-{{ number_format($operatingExpenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Sudah Disetor') }}</td>
                            <td class="text-end text-success">-{{ number_format($deposited, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold table-primary">
                            <td>{{ __('Sisa Saldo (Wajib Setor)') }}</td>
                            <td class="text-end">{{ number_format($netBalance, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
