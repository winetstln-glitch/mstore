@extends('layouts.app')

@section('title', 'Transaction Details')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Transaction Details</h1>
        <a href="{{ route('wash.transactions.index') }}" class="btn btn-sm btn-secondary shadow-sm" title="Back">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i>
            <span class="d-none d-md-inline ms-1">Back</span>
        </a>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Info</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Transaction #</th>
                            <td>{{ $transaction->transaction_number }}</td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Customer</th>
                            <td>{{ $transaction->customer_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Plate No</th>
                            <td>{{ $transaction->vehicle_plate ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Cashier</th>
                            <td>{{ $transaction->user->name ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <th>Payment Method</th>
                            <td>{{ ucfirst($transaction->payment_method) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Items</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transaction->items as $item)
                                    <tr>
                                        <td>{{ $item->service_name }}</td>
                                        <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</th>
                                </tr>
                                @if($transaction->cash_amount)
                                <tr>
                                    <th colspan="3" class="text-end">Cash</th>
                                    <th class="text-end">Rp {{ number_format($transaction->cash_amount, 0, ',', '.') }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Change</th>
                                    <th class="text-end">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</th>
                                </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
