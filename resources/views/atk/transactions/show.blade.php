@extends('layouts.app')

@section('title', __('Transaction Details'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Transaction Details') }} #{{ $transaction->transaction_number }}</h1>
        <div>
            <a href="{{ route('atk.transactions.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-2"></i> {{ __('Back') }}
            </a>
            <a href="{{ route('atk.transactions.receipt', $transaction) }}" target="_blank" class="btn btn-warning">
                <i class="fa-solid fa-print me-2"></i> {{ __('Print Receipt') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Items') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Price') }}</th>
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Subtotal') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transaction->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">{{ __('Total Amount') }}</th>
                                    <th>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Transaction Info') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Cashier') }}</th>
                            <td>{{ $transaction->user->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Payment Method') }}</th>
                            <td><span class="badge bg-info">{{ strtoupper($transaction->payment_method) }}</span></td>
                        </tr>
                        @if($transaction->cash_amount)
                        <tr>
                            <th>{{ __('Cash Received') }}</th>
                            <td>Rp {{ number_format($transaction->cash_amount, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Change') }}</th>
                            <td>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
