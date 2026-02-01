@extends('layouts.app')

@section('title', __('Transaction History'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Transaction History') }}</h1>
        <a href="{{ route('atk.pos') }}" class="btn btn-primary">
            <i class="fa-solid fa-cash-register me-2"></i> {{ __('New Transaction') }}
        </a>
    </div>

    <div class="card shadow mb-4">
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
