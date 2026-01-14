@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Profit & Loss Statement') }}</h1>
        <div>
            <a href="{{ route('finance.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to Finance') }}
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
            <div class="table-responsive">
                <table class="table table-striped">
                    <!-- Revenue Section -->
                    <thead class="bg-light">
                        <tr>
                            <th colspan="2" class="text-uppercase text-primary">{{ __('Revenue (Pendapatan)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ __('Member Income') }}</td>
                            <td class="text-end">{{ number_format($memberIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Voucher Income') }}</td>
                            <td class="text-end">{{ number_format($voucherIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Other Income') }}</td>
                            <td class="text-end">{{ number_format($otherIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold table-success">
                            <td>{{ __('Total Revenue') }}</td>
                            <td class="text-end">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>

                    <!-- COGS Section -->
                    <thead class="bg-light">
                        <tr>
                            <th colspan="2" class="text-uppercase text-warning">{{ __('Cost of Revenue (Beban Pokok Pendapatan)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ __('Coordinator Commission') }}</td>
                            <td class="text-end text-danger">-{{ number_format($coordCommission, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('ISP Payment') }}</td>
                            <td class="text-end text-danger">-{{ number_format($ispPayment, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Tool Fund') }}</td>
                            <td class="text-end text-danger">-{{ number_format($toolFund, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold table-warning">
                            <td>{{ __('Total Cost of Revenue') }}</td>
                            <td class="text-end text-danger">-{{ number_format($totalCOGS, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>

                    <!-- Gross Profit -->
                    <thead class="bg-secondary text-white">
                        <tr>
                            <th>{{ __('Gross Profit (Laba Kotor)') }}</th>
                            <th class="text-end">{{ number_format($grossProfit, 0, ',', '.') }}</th>
                        </tr>
                    </thead>

                    <!-- Operating Expenses -->
                    <thead class="bg-light">
                        <tr>
                            <th colspan="2" class="text-uppercase text-danger">{{ __('Operating Expenses (Biaya Operasional)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ __('General Expenses') }}</td>
                            <td class="text-end text-danger">-{{ number_format($operatingExpenses, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>

                    <!-- Net Profit -->
                    <thead class="bg-primary text-white">
                        <tr>
                            <th class="h5 mb-0">{{ __('Net Profit (Laba Bersih / Investor Share)') }}</th>
                            <th class="text-end h5 mb-0">{{ number_format($netProfit, 0, ',', '.') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
