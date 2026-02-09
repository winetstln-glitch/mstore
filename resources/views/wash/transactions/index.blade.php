@extends('layouts.app')

@section('title', 'Wash Transactions')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Wash Transactions</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('wash.transactions.export.pdf', request()->all()) }}" class="btn btn-sm btn-danger shadow-sm">
                <i class="fas fa-file-pdf fa-sm text-white-50"></i> Generate PDF
            </a>
            <a href="{{ route('wash.transactions.export.excel', request()->all()) }}" class="btn btn-sm btn-success shadow-sm">
                <i class="fas fa-file-excel fa-sm text-white-50"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0 font-weight-bold text-primary">Transaction List</h6>
            </div>
            <form action="{{ route('wash.transactions.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="start_date" class="col-form-label">{{ __('Start Date') }}</label>
                </div>
                <div class="col-auto">
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-auto">
                    <label for="end_date" class="col-form-label">{{ __('End Date') }}</label>
                </div>
                <div class="col-auto">
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('wash.transactions.index') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction #</th>
                            <th>Customer</th>
                            <th>Plate No</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Cashier</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $transaction->transaction_number }}</td>
                                <td>{{ $transaction->customer_name ?? '-' }}</td>
                                <td>{{ $transaction->vehicle_plate ?? '-' }}</td>
                                <td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                                <td>{{ ucfirst($transaction->payment_method) }}</td>
                                <td>{{ $transaction->user->name ?? 'Unknown' }}</td>
                                <td>
                                    <a href="{{ route('wash.transactions.show', $transaction->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No transactions found.</td>
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
</div>
@endsection
