@extends('layouts.app')

@section('title', __('Finance Dashboard'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Finance Dashboard') }}</h1>
        <div class="d-flex flex-wrap gap-2">
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
            <a href="{{ route('finance.manager_report') }}" class="btn btn-warning">
                <i class="fa-solid fa-user-tie me-1"></i> {{ __('Neraca Awal') }}
            </a>
            <a href="{{ route('finance.profit_loss') }}" class="btn btn-info">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i> {{ __('Profit & Loss Report') }}
            </a>
            <a href="{{ route('finance.settings') }}" class="btn btn-secondary">
                <i class="fa-solid fa-cog me-1"></i> {{ __('Settings') }}
            </a>
            @endif
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('Add Transaction') }}
            </button>
        </div>
    </div>

    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
        <div class="row">
            <!-- Total Income -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    {{ __('Total Income') }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalIncome, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Expenses -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    {{ __('Total Pengeluaran') }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalExpense, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-file-invoice-dollar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Balance -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    {{ __('Saldo Perusahaan') }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($balance, 0, ',', '.') }}</div>
                                <small class="text-muted" title="{{ __('Gross Share') }} - {{ __('General Expenses') }}">
                                    {{ number_format($totalCompanyGrossShare, 0, ',', '.') }} - {{ number_format($totalGeneralExpenses, 0, ',', '.') }}
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-building fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Investor Funds Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-secondary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                    {{ __('Dana Peralatan') }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalInvestorFunds ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-briefcase fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fund Status Cards -->
        <div class="row mb-4">
            <!-- ISP Fund Card -->
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    {{ __('Pembayaran ISP') }} ({{ $ispRate }}%)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalIspShare, 0, ',', '.') }}</div>
                                <small class="text-muted">{{ __('Total Accumulated Allocation') }}</small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-server fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool Fund Card -->
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    {{ __('Dana Peralatan') }} ({{ $toolRate }}%)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalToolFund, 0, ',', '.') }}</div>
                                <small class="text-muted">{{ __('Total Accumulated Allocation') }}</small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-toolbox fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">{{ __('Coordinator Balance Sheet') }}</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>{{ __('Pengurus') }}</th>
                                <th>{{ __('Total Pendapatan') }}</th>
                                <th>{{ __('Komisi Pengurus') }}</th>
                                <th>{{ __('Dana ISP') }}</th>
                                <th>{{ __('Dana Alat') }}</th>
                                <th>{{ __('Pengeluaran') }}</th>
                                <th>{{ __('Sisa Saldo') }}</th>
                                <th>{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coordinatorSummaries as $summary)
                            <tr>
                                <td>{{ $summary->name }}</td>
                                <td class="text-end">{{ number_format($summary->gross_revenue, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">-{{ number_format($summary->commission, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">-{{ number_format($summary->isp_share, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">-{{ number_format($summary->tools_cost, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">-{{ number_format($summary->expenses, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold {{ $summary->net_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($summary->net_balance, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('finance.coordinator.detail', $summary->id) }}" class="btn btn-sm btn-info text-white" title="{{ __('View Details') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        @php
            $summary = $coordinatorSummaries[0] ?? null;
        @endphp

        @if($summary)
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        {{ __('Total Pendapatan') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary->gross_revenue, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-money-bill-wave fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        {{ __('Komisi Pengurus') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary->commission, 0, ',', '.') }}</div>
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
                                        {{ __('Pengeluaran Pengurus') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary->expenses, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-arrow-trend-down fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-left-secondary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                        {{ __('Uang Kas') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary->investor_cash, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-piggy-bank fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        {{ __('Total Sisa Disetor') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary->net_balance, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-wallet fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Bagi Hasil Investor per Koordinator') }}</h6>
            <a href="{{ route('finance.investor_share.pdf') }}" class="btn btn-sm btn-danger shadow-sm">
                <i class="fas fa-file-pdf fa-sm text-white-50"></i> {{ __('Unduh PDF') }}
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Koordinator') }}</th>
                            <th>{{ __('Bagi Hasil Investor (Setelah Kas)') }}</th>
                            <th>{{ __('Dana Kas Investor') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($coordinatorSummaries as $summary)
                        <tr>
                            <td>{{ $summary->name }}</td>
                            <td class="text-end text-danger">-{{ number_format($summary->investor_share, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($summary->investor_cash, 0, ',', '.') }}</td>
                        </tr>
                        @php
                            $investorDetails = $investorDetailsByCoordinator[$summary->id] ?? [];
                        @endphp
                        @foreach($investorDetails as $detail)
                        <tr>
                            <td>&nbsp;&nbsp;- {{ $detail['investor_name'] }}</td>
                            <td class="text-end text-muted">-{{ number_format($detail['profit_share'], 0, ',', '.') }}</td>
                            <td class="text-end text-muted">-{{ number_format($detail['cash_fund'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-info">{{ __('Rincian Pendapatan (10 Terakhir)') }}</h6>
            <a href="{{ route('finance.income_breakdown.pdf') }}" class="btn btn-sm btn-danger shadow-sm">
                <i class="fas fa-file-pdf fa-sm text-white-50"></i> {{ __('Unduh PDF') }}
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Tanggal') }}</th>
                            <th>{{ __('Koordinator') }}</th>
                            <th>{{ __('Investor') }}</th>
                            <th class="text-end">{{ __('Pendapatan Kotor') }}</th>
                            <th class="text-end">{{ __('Komisi Pengurus') }} ({{ $coordRate }}%)</th>
                            <th class="text-end">{{ __('Iuran Internet') }} ({{ $ispRate }}%)</th>
                            <th class="text-end">{{ __('Manajemen') }} ({{ $toolRate }}%)</th>
                            <th class="text-end">{{ __('Pendapatan Pengelola') }} ({{ $managerRate }}%)</th>
                            <th class="text-end text-white" style="background-color: #f6c23e">{{ __('Sisa Bersih (Net Balance)') }}</th>
                            <th class="text-end">{{ __('Dana Kas') }} ({{ $investorCashRate }}%)</th>
                            <th class="text-end">{{ __('Income Bersih') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incomeBreakdowns as $breakdown)
                        <tr>
                            <td>{{ $breakdown->date->format('d M Y') }}</td>
                            <td>{{ $breakdown->coordinator_name }}</td>
                            <td><small>{{ $breakdown->investor_names }}</small></td>
                            <td class="text-end fw-bold">{{ number_format($breakdown->gross_amount, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($breakdown->commission, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($breakdown->isp_share, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($breakdown->tool_fund, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-danger">-{{ number_format($breakdown->manager_income, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold bg-light">{{ number_format($breakdown->net_balance, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">{{ $breakdown->cash_fund > 0 ? '-' . number_format($breakdown->cash_fund, 0, ',', '.') : '0' }}</td>
                            <td class="text-end text-success">{{ number_format($breakdown->investor_share, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary me-3">{{ __('Transaction History') }}</h6>
                    <button type="button" id="toggleSelectMode" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fa-solid fa-list-check me-1"></i> {{ __('Select Mode') }}
                    </button>
                    <button type="button" id="bulkDeleteBtn" class="btn btn-sm btn-danger d-none" onclick="submitBulkDelete()">
                        <i class="fa-solid fa-trash me-1"></i> {{ __('Delete Selected') }}
                    </button>
                </div>
                <form action="{{ route('finance.index') }}" method="GET" class="d-flex align-items-center">
                    <input type="month" name="month" class="form-control form-control-sm me-2" value="{{ request('month') }}">
                    
                    <select name="coordinator_id" class="form-select form-select-sm me-2" style="max-width: 150px;">
                        <option value="">{{ __('All Coordinators') }}</option>
                        @foreach($coordinators as $coordinator)
                            <option value="{{ $coordinator->id }}" {{ request('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                {{ $coordinator->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="type" class="form-select form-select-sm me-2">
                        <option value="">{{ __('All Types') }}</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>{{ __('Income') }}</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>{{ __('Expense') }}</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-secondary">{{ __('Filter') }}</button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center select-column d-none" width="40">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Coordinator') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                                <th>{{ __('Ref') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                            <tr>
                                <td class="text-center align-middle select-column d-none">
                                    <input type="checkbox" name="ids[]" value="{{ $transaction->id }}" class="form-check-input select-row">
                                </td>
                                <td class="align-middle">{{ $transaction->transaction_date->format('d M Y') }}</td>
                                <td class="align-middle">
                                    <span class="badge bg-{{ $transaction->type == 'income' ? 'success' : 'danger' }}">
                                        {{ $transaction->type == 'income' ? __('Income') : __('Expense') }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    @php
                                        $categoryLabelKey = match($transaction->category) {
                                            'Operational' => 'Server Expense',
                                            'Transport' => 'Transport',
                                            'Consumption' => 'Consumption',
                                            'Repair' => 'Repair',
                                            default => $transaction->category,
                                        };
                                    @endphp
                                    <span class="badge bg-secondary text-white">{{ ucfirst(__($categoryLabelKey)) }}</span>
                                </td>
                                <td class="align-middle">{{ $transaction->description }}</td>
                                <td class="align-middle">
                                    @if($transaction->coordinator)
                                        <span class="badge bg-info text-dark">{{ $transaction->coordinator->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="align-middle text-end fw-bold {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->type == 'income' ? '+' : '-' }} {{ number_format($transaction->amount, 0, ',', '.') }}
                                </td>
                                <td class="align-middle small">{{ $transaction->reference_number }}</td>
                                <td class="align-middle text-center row-actions">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary"
                                            data-bs-toggle="modal"  
                                            data-bs-target="#editTransactionModal"
                                            data-id="{{ $transaction->id }}"
                                            data-type="{{ $transaction->type }}"
                                            data-category="{{ $transaction->category }}"
                                            data-amount="{{ $transaction->amount }}"
                                            data-date="{{ $transaction->transaction_date->format('Y-m-d') }}"
                                            data-coordinator="{{ $transaction->coordinator_id }}"
                                            data-description="{{ $transaction->description }}"
                                            data-ref="{{ $transaction->reference_number }}"
                                            data-action="{{ route('finance.update', $transaction->id) }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form action="{{ route('finance.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this transaction?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">{{ __('No transactions found.') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    @else
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">{{ __('Grafik Pendapatan Per Bulan') }}</h6>
            </div>
            <div class="card-body">
                @php
                    $maxIncome = $monthlyIncome->max('total') ?? 0;
                @endphp
                @if($monthlyIncome->isEmpty())
                    <p class="text-muted mb-0">{{ __('No income data available.') }}</p>
                @else
                    <div class="mb-2 small text-muted">
                        {{ __('Pendapatan per bulan berdasarkan transaksi Member dan Voucher Income.') }}
                    </div>
                    <div>
                        @foreach($monthlyIncome as $row)
                            @php
                                $percent = $maxIncome > 0 ? ($row->total / $maxIncome) * 100 : 0;
                                $label = \Carbon\Carbon::parse($row->ym . '-01')->translatedFormat('M Y');
                            @endphp
                            <div class="d-flex align-items-center mb-2">
                                <div style="width: 90px;">
                                    {{ $label }}
                                </div>
                                <div class="flex-grow-1 bg-light" style="height: 16px;">
                                    <div class="bg-success" style="width: {{ $percent }}%; height: 100%;"></div>
                                </div>
                                <div class="ms-2" style="width: 140px; text-align: right;">
                                    {{ number_format($row->total, 0, ',', '.') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

<form id="bulkDeleteForm" action="{{ route('finance.bulkDestroy') }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Transaction') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('finance.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="type" class="form-select" required>
                            <option value="income">{{ __('Income') }}</option>
                            <option value="expense">{{ __('Expense') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category" class="form-select" required>
                            <option value="">{{ __('Select Category') }}</option>
                            <optgroup label="{{ __('Income') }}">
                                <option value="Member Income">{{ __('Member Income') }}</option>
                                <option value="Voucher Income">{{ __('Voucher Income') }}</option>
                            </optgroup>
                            <optgroup label="{{ __('Expense') }}">
                                <option value="Salary">{{ __('Salary') }}</option>
                                <option value="Operational">{{ __('Server Expense') }}</option>
                                <option value="Transport">{{ __('Transport') }}</option>
                                <option value="Consumption">{{ __('Consumption') }}</option>
                                <option value="Repair">{{ __('Repair') }}</option>
                                <option value="Maintenance">{{ __('Maintenance') }}</option>
                                <option value="Pembayaran ISP">{{ __('Pembayaran ISP') }}</option>
                                <option value="Pembelian Alat">{{ __('Pembelian Alat') }}</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Coordinator (Optional)') }}</label>
                        <select name="coordinator_id" class="form-select">
                            <option value="">{{ __('Select Coordinator') }}</option>
                            @foreach($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}">{{ $coordinator->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reference Number') }}</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Transaction') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Transaction') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTransactionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="type" class="form-select" required>
                            <option value="income">{{ __('Income') }}</option>
                            <option value="expense">{{ __('Expense') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category" class="form-select" required>
                            <option value="">{{ __('Select Category') }}</option>
                            <optgroup label="{{ __('Income') }}">
                                <option value="Member Income">{{ __('Member Income') }}</option>
                                <option value="Voucher Income">{{ __('Voucher Income') }}</option>
                            </optgroup>
                            <optgroup label="{{ __('Expense') }}">
                                <option value="Salary">{{ __('Salary') }}</option>
                                <option value="Operational">{{ __('Server Expense') }}</option>
                                <option value="Transport">{{ __('Transport') }}</option>
                                <option value="Consumption">{{ __('Consumption') }}</option>
                                <option value="Repair">{{ __('Repair') }}</option>
                                <option value="Maintenance">{{ __('Maintenance') }}</option>
                                <option value="Pembayaran ISP">{{ __('Pembayaran ISP') }}</option>
                                <option value="Pembelian Alat">{{ __('Pembelian Alat') }}</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="date" name="transaction_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Coordinator (Optional)') }}</label>
                        <select name="coordinator_id" class="form-select">
                            <option value="">{{ __('Select Coordinator') }}</option>
                            @foreach($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}">{{ $coordinator->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reference Number') }}</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Update Transaction') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit Modal Logic
        var editTransactionModal = document.getElementById('editTransactionModal');
        if (editTransactionModal) {
            editTransactionModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var type = button.getAttribute('data-type');
                var category = button.getAttribute('data-category');
                var amount = button.getAttribute('data-amount');
                var date = button.getAttribute('data-date');
                var coordinator = button.getAttribute('data-coordinator');
                var description = button.getAttribute('data-description');
                var ref = button.getAttribute('data-ref');
                var action = button.getAttribute('data-action');

                var form = document.getElementById('editTransactionForm');
                form.action = action;

                form.querySelector('[name="type"]').value = type;
                form.querySelector('[name="category"]').value = category;
                form.querySelector('[name="amount"]').value = amount;
                form.querySelector('[name="transaction_date"]').value = date;
                form.querySelector('[name="coordinator_id"]').value = coordinator || '';
                
                form.querySelector('[name="description"]').value = description;
                form.querySelector('[name="reference_number"]').value = ref;
            });
        }

        // Bulk Delete Logic
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.select-row');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');
        const toggleSelectModeBtn = document.getElementById('toggleSelectMode');
        const selectColumns = document.querySelectorAll('.select-column');
        const rowActions = document.querySelectorAll('.row-actions');

        function updateBulkDeleteVisibility() {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            if (bulkDeleteBtn) {
                if (anyChecked) {
                    bulkDeleteBtn.classList.remove('d-none');
                } else {
                    bulkDeleteBtn.classList.add('d-none');
                }
            }
        }

        if (toggleSelectModeBtn) {
            toggleSelectModeBtn.addEventListener('click', function () {
                const isActive = this.classList.toggle('active');

                selectColumns.forEach(col => {
                    if (isActive) {
                        col.classList.remove('d-none');
                    } else {
                        col.classList.add('d-none');
                    }
                });

                rowActions.forEach(cell => {
                    if (isActive) {
                        cell.classList.add('d-none');
                    } else {
                        cell.classList.remove('d-none');
                    }
                });

                if (!isActive) {
                    if (selectAll) {
                        selectAll.checked = false;
                    }
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                    });
                    updateBulkDeleteVisibility();
                }
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateBulkDeleteVisibility();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteVisibility);
        });

        window.submitBulkDelete = function() {
            if (confirm('{{ __('Are you sure you want to delete the selected transactions?') }}')) {
                // Clear previous inputs (except token and method)
                const inputs = bulkDeleteForm.querySelectorAll('input[name="ids[]"]');
                inputs.forEach(input => input.remove());
                
                // Add checked inputs to form
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = cb.value;
                        bulkDeleteForm.appendChild(input);
                    }
                });
                
                bulkDeleteForm.submit();
            }
        }
    });
</script>

@endsection
