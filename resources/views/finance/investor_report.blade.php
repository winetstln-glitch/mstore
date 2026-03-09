@extends('layouts.app')

@section('title', __('Laporan Investor'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h1 class="h3 mb-0 text-body">{{ __('Laporan Laba Bersih Investor') }}</h1>
        <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
            <form action="{{ route('finance.investor_report') }}" method="GET" class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
                <select name="coordinator_id" class="form-select form-control-lg" onchange="this.form.submit()">
                    <option value="">{{ __('Semua Pengurus') }}</option>
                    @foreach($coordinators as $coordinator)
                        <option value="{{ $coordinator->id }}" {{ $coordinatorId == $coordinator->id ? 'selected' : '' }}>
                            {{ $coordinator->name }}
                        </option>
                    @endforeach
                </select>
                <input type="month" name="month" class="form-control form-control-lg" value="{{ $selectedMonth }}" onchange="this.form.submit()">
            </form>
            <div class="d-flex flex-column flex-md-row gap-2">
                <a href="{{ route('finance.investor_report.pdf', ['month' => $selectedMonth, 'coordinator_id' => $coordinatorId]) }}" class="btn btn-danger btn-lg w-100 w-md-auto" target="_blank">
                    <i class="fas fa-file-pdf me-1"></i> {{ __('Export PDF') }}
                </a>
                <a href="{{ route('finance.index') }}" class="btn btn-secondary btn-lg w-100 w-md-auto">
                    <i class="fas fa-arrow-left me-1"></i> {{ __('Back') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">{{ __('Perhitungan Pembagian Hasil Investor') }} - {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-responsive-mobile">
                    <thead class="">
                        <tr>
                            <th>{{ __('Keterangan') }}</th>
                            <th class="text-end">{{ __('Nilai (IDR)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>{{ __('Total Pendapatan Kotor') }}</strong> <br> <small class="text-muted">(Member + Voucher)</small></td>
                            <td class="text-end fw-bold">{{ number_format($grossRevenue, 0, ',', '.') }}</td>
                        </tr>
                        
                        <!-- Deductions Section -->
                        <tr>
                            <td colspan="2" class=" font-weight-bold text-danger">{{ __('Potongan / Alokasi Dana') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">{{ __('Komisi Pengurus') }}</td>
                            <td class="text-end text-danger">-{{ number_format($commission, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">{{ __('Pembagian ISP') }}</td>
                            <td class="text-end text-danger">-{{ number_format($ispShare, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">{{ __('Dana Peralatan / Manajemen') }}</td>
                            <td class="text-end text-danger">-{{ number_format($toolFund, 0, ',', '.') }}</td>
                        </tr>
                        
                        <!-- Expenses Section -->
                        <tr>
                            <td colspan="2" class=" font-weight-bold text-danger">{{ __('Pengeluaran Operasional & Lainnya') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">
                                {{ __('Total Pengeluaran Operasional') }} <br>
                                <small class="text-muted">(Server, Ambil Barang, Material Kantor, dll)</small>
                            </td>
                            <td class="text-end text-danger">-{{ number_format($operationalExpenses, 0, ',', '.') }}</td>
                        </tr>

                        <tr>
                            <td class="ps-4">{{ __('Kas Wajib Investor (5% dari Sisa)') }}</td>
                            <td class="text-end text-danger">-{{ number_format($investorCashFund, 0, ',', '.') }}</td>
                        </tr>

                        <!-- Result Section -->
                        <tr class="table-success border-top border-dark">
                            <td class="h5 font-weight-bold text-success">{{ __('Total Laba Bersih untuk Investor') }}</td>
                            <td class="text-end h5 font-weight-bold finance-kpi-value text-success">{{ number_format($netProfit, 0, ',', '.') }}</td>
                        </tr>
                        
                        <!-- Split Section -->
                        <tr>
                            <td colspan="2" class=" font-weight-bold text-primary">{{ __('Pembagian Per Investor') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">{{ __('Jumlah Investor') }}</td>
                            <td class="text-end text-muted fw-bold">{{ $investorCount }}</td>
                        </tr>
                        <tr class="table-primary">
                            <td class="h5 font-weight-bold text-primary">{{ __('Laba Per Investor') }}</td>
                            <td class="text-end h5 font-weight-bold finance-kpi-value text-primary">{{ number_format($profitPerInvestor, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Detailed Investor Breakdown -->
            <div class="mt-4">
                <h5 class="font-weight-bold text-body mb-3">{{ __('Rincian Pembagian Per Investor') }}</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-responsive-mobile">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Nama Investor') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th class="text-end text-muted">{{ __('Pembagian Laba (IDR)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($investors as $index => $investor)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        {{ $investor->name }}
                                        @if($investor->coordinator_id)
                                            <br><small class="text-muted">Koordinator: {{ $investor->coordinator->name ?? '-' }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ __('Investor') }}</span>
                                    </td>
                                    <td class="text-end text-muted fw-bold">{{ number_format($profitPerInvestor, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">{{ __('Tidak ada data investor.') }}</td>
                                </tr>
                            @endforelse
                            <tr class="table-secondary fw-bold">
                                <td colspan="3" class="text-end text-muted">{{ __('Total Dibagikan') }}</td>
                                <td class="text-end text-muted">{{ number_format($profitPerInvestor * $investors->count(), 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-1"></i>
                <strong>Catatan:</strong> Perhitungan ini mencakup pengurangan "Ambil Barang" dan biaya operasional lainnya (Server, dll) dari total pendapatan sebelum dibagi ke investor.
            </div>
        </div>
    </div>
</div>
@endsection
