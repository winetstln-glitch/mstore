@extends('layouts.app')

@section('title', __('Transaction History'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Transaction History') }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('atk.transactions.export.pdf', request()->all()) }}" class="btn btn-danger">
                <i class="fa-solid fa-file-pdf me-2"></i> {{ __('Export PDF') }}
            </a>
            <a href="{{ route('atk.transactions.export.excel', request()->all()) }}" class="btn btn-success">
                <i class="fa-solid fa-file-excel me-2"></i> {{ __('Export Excel') }}
            </a>
            <a href="{{ route('atk.pos') }}" class="btn btn-primary">
                <i class="fa-solid fa-cash-register me-2"></i> {{ __('New Transaction') }}
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form action="{{ route('atk.transactions.index') }}" method="GET" class="row g-3 align-items-center">
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
                    <a href="{{ route('atk.transactions.index') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Transaction No') }}</th>
                            <th>{{ __('Cashier') }}</th>
                            <th>{{ __('Total') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $transaction->transaction_number }}</td>
                            <td>{{ $transaction->user->name ?? '-' }}</td>
                            <td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                            <td><span class="badge bg-secondary">{{ strtoupper($transaction->payment_method) }}</span></td>
                            <td>
                                <a href="{{ route('atk.transactions.show', $transaction) }}" class="btn btn-sm btn-info">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('atk.transactions.receipt', $transaction) }}" target="_blank" class="btn btn-sm btn-warning">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
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
