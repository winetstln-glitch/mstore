@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h1 class="h3 mb-0 text-body">{{ __('Profit & Loss Statement') }}</h1>
        <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
            <a href="{{ route('finance.index') }}" class="btn btn-secondary btn-lg w-100 w-md-auto">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
            </a>
            <a href="{{ route('finance.profit_loss.excel', ['month' => request('month'), 'coordinator_id' => request('coordinator_id')]) }}" class="btn btn-success btn-lg w-100 w-md-auto">
                <i class="fa-solid fa-file-excel me-1"></i> {{ __('Excel') }}
            </a>
            <a href="{{ route('finance.profit_loss.pdf', ['month' => request('month'), 'coordinator_id' => request('coordinator_id')]) }}" class="btn btn-danger btn-lg w-100 w-md-auto">
                <i class="fa-solid fa-file-pdf me-1"></i> {{ __('PDF') }}
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-lg w-100 w-md-auto">
                <i class="fa-solid fa-print me-1"></i> {{ __('Print') }}
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Report Period') }}</h6>
            <form action="{{ route('finance.profit_loss') }}" method="GET" class="d-flex flex-column flex-md-row align-items-center gap-2 w-100 w-md-auto">
                <select name="coordinator_id" class="form-select form-select-lg" onchange="this.form.submit()">
                    <option value="">{{ __('Semua Pengurus') }}</option>
                    @foreach($coordinators as $coordinator)
                        <option value="{{ $coordinator->id }}" {{ $selectedCoordinatorId == $coordinator->id ? 'selected' : '' }}>
                            {{ $coordinator->name }}
                        </option>
                    @endforeach
                </select>
                <input type="month" name="month" class="form-control form-control-lg" value="{{ request('month') }}" onchange="this.form.submit()">
            </form>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card border-left-success h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Total Revenue') }}</div>
                            <div class="h5 mb-0 font-weight-bold finance-kpi-value text-gray-800">{{ number_format($totalRevenue, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-left-warning h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">{{ __('Total Cost of Revenue') }}</div>
                            <div class="h5 mb-0 font-weight-bold finance-kpi-value text-gray-800">-{{ number_format($totalCOGS, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-left-info h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('Gross Profit') }}</div>
                            <div class="h5 mb-0 font-weight-bold finance-kpi-value text-gray-800">{{ number_format($grossProfit, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-left-primary h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ __('Net Profit') }}</div>
                            <div class="h5 mb-0 font-weight-bold finance-kpi-value text-gray-800">{{ number_format($netProfit, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
    <table class="table table-bordered table-responsive-mobile">
        <thead class="">
            <tr>
                <th class="text-uppercase">{{ __('Uraian') }}</th>
                <th class="text-end text-success">{{ __('Jumlah Pemasukan') }}</th>
                <th class="text-end text-danger">{{ __('Jumlah Pengeluaran') }}</th>
            </tr>
        </thead>
        <tbody>

            {{-- ================= PENDAPATAN ================= --}}
            <tr class="table-light fw-bold">
                <td colspan="3" class="text-uppercase text-primary">{{ __('Pendapatan') }}</td>
            </tr>
            <tr>
                <td>{{ __('Pendapatan Member') }}</td>
                <td class="text-end text-muted">{{ number_format($memberIncome, 0, ',', '.') }}</td>
                <td class="text-end text-muted">-</td>
            </tr>
            <tr>
                <td>{{ __('Pendapatan Voucher') }}</td>
                <td class="text-end text-muted">{{ number_format($voucherIncome, 0, ',', '.') }}</td>
                <td class="text-end text-muted">-</td>
            </tr>
            <tr>
                <td>{{ __('Pendapatan Lainnya') }}</td>
                <td class="text-end text-muted">{{ number_format($otherIncome, 0, ',', '.') }}</td>
                <td class="text-end text-muted">-</td>
            </tr>
            <tr class="fw-bold table-success">
                <td>{{ __('Total Pendapatan') }}</td>
                <td class="text-end text-muted">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
                <td class="text-end text-muted">-</td>
            </tr>

            <tr><td colspan="3"></td></tr>

            {{-- ================= BEBAN POKOK ================= --}}
            <tr class="table-light fw-bold">
                <td colspan="3" class="text-uppercase text-warning">{{ __('Beban Pokok Pendapatan') }}</td>
            </tr>
            <tr>
                <td>{{ __('Komisi Koordinator') }} (±{{ number_format($coordRate, 1) }}%)</td>
                <td class="text-end text-muted">-</td>
                <td class="text-end text-muted">{{ number_format($coordCommission, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>{{ __('Pembayaran ISP') }} (±{{ number_format($ispRate, 1) }}%)</td>
                <td class="text-end text-muted">-</td>
                <td class="text-end text-muted">{{ number_format($ispPayment, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>{{ __('Dana Alat') }} (±{{ number_format($toolRate, 1) }}%)</td>
                <td class="text-end text-muted">-</td>
                <td class="text-end text-muted">{{ number_format($toolFund, 0, ',', '.') }}</td>
            </tr>
            <tr class="fw-bold table-warning">
                <td>{{ __('Total Beban Pokok Pendapatan') }}</td>
                <td class="text-end text-muted">-</td>
                <td class="text-end text-muted">{{ number_format($totalCOGS, 0, ',', '.') }}</td>
            </tr>

            <tr><td colspan="3"></td></tr>

            {{-- ================= LABA KOTOR ================= --}}
            <tr class="table-secondary text-white fw-bold">
                <td>{{ __('Laba Kotor') }}</td>
                <td class="text-end text-muted">{{ number_format($grossProfit, 0, ',', '.') }}</td>
                <td class="text-end text-muted">-</td>
            </tr>

            <tr><td colspan="3"></td></tr>

            {{-- ================= BIAYA OPERASIONAL ================= --}}
            <tr class="table-light fw-bold">
                <td colspan="3" class="text-uppercase text-danger">{{ __('Biaya Operasional') }}</td>
            </tr>
            <tr>
                <td>{{ __('Biaya Server') }}</td>
                <td class="text-end text-muted">-</td>
                <td class="text-end text-muted">{{ number_format($serverExpenses, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>{{ __('Transportasi') }}</td>
                <td class="text-end">-</td>
                <td class="text-end">{{ number_format($transportExpenses, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>{{ __('Konsumsi') }}</td>
                <td class="text-end">-</td>
                <td class="text-end">{{ number_format($consumptionExpenses, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>{{ __('Perbaikan') }}</td>
                <td class="text-end">-</td>
                <td class="text-end">{{ number_format($repairExpenses, 0, ',', '.') }}</td>
            </tr>
            @foreach($otherExpensesBreakdown as $category => $amount)
            <tr>
                <td>{{ $category }}</td>
                <td class="text-end">-</td>
                <td class="text-end">{{ number_format($amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @if($otherOperatingExpenses == 0 && count($otherExpensesBreakdown) == 0)
            <!-- No other expenses -->
            @endif

            <tr><td colspan="3"></td></tr>

            {{-- ================= LABA BERSIH ================= --}}
            <tr class="table-primary text-white fw-bold">
                <td>{{ __('Laba Bersih (Investor Share)') }}</td>
                <td class="text-end">{{ number_format($netProfit, 0, ',', '.') }}</td>
                <td class="text-end">-</td>
            </tr>
            <tr>
                <td>{{ __('Cadangan Kas Investor') }} ({{ $investorCashPercent }}%)</td>
                <td class="text-end">-</td>
                <td class="text-end">{{ number_format($investorCashReserve, 0, ',', '.') }}</td>
            </tr>
            <tr class="fw-bold table-primary">
                <td>{{ __('Bagi Hasil Investor Setelah Kas') }}</td>
                <td class="text-end">{{ number_format($investorShareAfterCash, 0, ',', '.') }}</td>
                <td class="text-end">-</td>
            </tr>

        </tbody>
    </table>
</div>

        </div>
    </div>
</div>
@endsection
