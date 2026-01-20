@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('Finance Report') }}: {{ $coordinator->name }}</h1>
            <p class="mb-0 text-muted">{{ $coordinator->region->name ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('finance.index') }}" class="btn btn-secondary me-2">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
            </a>
            <a href="{{ route('finance.coordinator.pdf', ['coordinator' => $coordinator->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-danger">
                <i class="fa-solid fa-file-pdf me-1"></i> {{ __('Download PDF') }}
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('finance.coordinator.detail', $coordinator->id) }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <!-- Gross Revenue -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Total Pendapatan') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($grossRevenue, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commission -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('Komisi Pengurus') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($commission, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">{{ __('Pengeluaran Pengurus') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($expenses, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-arrow-trend-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Investor Cash -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">{{ __('Uang Kas') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($investorCash, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-piggy-bank fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remaining Balance -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ __('Sisa Disetor') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($netBalance, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Transaction History') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $transaction->type == 'income' ? 'success' : 'danger' }}">
                                    {{ $transaction->type == 'income' ? __('Income') : __('Expense') }}
                                </span>
                            </td>
                            <td>{{ $transaction->category }}</td>
                            <td>{{ $transaction->description }}</td>
                            <td class="text-end fw-bold {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }}">
                                {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">{{ __('No transactions found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
