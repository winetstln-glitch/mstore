@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Profit & Loss Statement') }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to Finance') }}
            </a>
            <a href="{{ route('finance.profit_loss.excel', ['month' => request('month')]) }}" class="btn btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> {{ __('Download Excel') }}
            </a>
            <a href="{{ route('finance.profit_loss.pdf', ['month' => request('month')]) }}" class="btn btn-danger">
                <i class="fa-solid fa-file-pdf me-1"></i> {{ __('Download PDF') }}
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa-solid fa-print me-1"></i> {{ __('Print Report') }}
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Report Period') }}</h6>
            <form action="{{ route('finance.profit_loss') }}" method="GET" class="d-flex">
                <input type="month" name="month" class="form-control form-control-sm me-2" value="{{ request('month') }}" onchange="this.form.submit()">
            </form>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card border-left-success h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Total Revenue') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalRevenue, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-left-warning h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">{{ __('Total Cost of Revenue') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">-{{ number_format($totalCOGS, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-left-info h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('Gross Profit') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($grossProfit, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-left-primary h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ __('Net Profit') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($netProfit, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="bg-light">
                        <tr>
                            <th>{{ __('Uraian') }}</th>
                            <th class="text-end">{{ __('Jumlah') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light fw-bold">
                            <td colspan="2" class="text-uppercase text-primary">{{ __('Pendapatan') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Pendapatan Member') }}</td>
                            <td class="text-end">{{ number_format($memberIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Pendapatan Voucher') }}</td>
                            <td class="text-end">{{ number_format($voucherIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Pendapatan Lainnya') }}</td>
                            <td class="text-end">{{ number_format($otherIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold table-success">
                            <td>{{ __('Total Pendapatan') }}</td>
                            <td class="text-end">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        </tr>

                        <tr>
                            <td colspan="2"></td>
                        </tr>

                        <tr class="table-light fw-bold">
                            <td colspan="2" class="text-uppercase text-warning">{{ __('Beban Pokok Pendapatan') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Komisi Koordinator') }}</td>
                            <td class="text-end text-danger">-{{ number_format($coordCommission, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Pembayaran ISP') }}</td>
                            <td class="text-end text-danger">-{{ number_format($ispPayment, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Dana Alat') }}</td>
                            <td class="text-end text-danger">-{{ number_format($toolFund, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('HPP Toko ATK') }}</td>
                            <td class="text-end text-danger">-{{ number_format($atkCOGS, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Biaya Inventaris (Material)') }}</td>
                            <td class="text-end text-danger">-{{ number_format($inventoryCost, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold table-warning">
                            <td>{{ __('Total Beban Pokok Pendapatan') }}</td>
                            <td class="text-end text-danger">-{{ number_format($totalCOGS, 0, ',', '.') }}</td>
                        </tr>

                        <tr>
                            <td colspan="2"></td>
                        </tr>

                        <tr class="table-secondary text-white fw-bold">
                            <td>{{ __('Laba Kotor') }}</td>
                            <td class="text-end">{{ number_format($grossProfit, 0, ',', '.') }}</td>
                        </tr>

                        <tr>
                            <td colspan="2"></td>
                        </tr>

                        <tr class="table-light fw-bold">
                            <td colspan="2" class="text-uppercase text-danger">{{ __('Biaya Operasional') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Biaya Server') }}</td>
                            <td class="text-end text-danger">-{{ number_format($serverExpenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Transportasi') }}</td>
                            <td class="text-end text-danger">-{{ number_format($transportExpenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Konsumsi') }}</td>
                            <td class="text-end text-danger">-{{ number_format($consumptionExpenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Perbaikan') }}</td>
                            <td class="text-end text-danger">-{{ number_format($repairExpenses, 0, ',', '.') }}</td>
                        </tr>
                        @if($otherOperatingExpenses != 0)
                        <tr>
                            <td>{{ __('Biaya Operasional Lainnya') }}</td>
                            <td class="text-end text-danger">-{{ number_format($otherOperatingExpenses, 0, ',', '.') }}</td>
                        </tr>
                        @endif

                        <tr>
                            <td colspan="2"></td>
                        </tr>

                        <tr class="table-primary text-white fw-bold">
                            <td>{{ __('Laba Bersih (Investor Share)') }}</td>
                            <td class="text-end">{{ number_format($netProfit, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Cadangan Kas Investor') }} ({{ $investorCashPercent }}%)</td>
                            <td class="text-end text-danger">-{{ number_format($investorCashReserve, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold table-primary">
                            <td>{{ __('Bagi Hasil Investor Setelah Kas') }}</td>
                            <td class="text-end">{{ number_format($investorShareAfterCash, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
