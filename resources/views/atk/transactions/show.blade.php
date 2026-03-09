@extends('layouts.app')

@section('title', __('Transaction Details'))

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('atk.transactions.index') }}" class="text-decoration-none text-muted">Transaksi</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detail #{{ $transaction->transaction_number }}</li>
                </ol>
            </nav>
            <h1 class="h3 font-weight-bold text-gray-800 mb-0">
                {{ __('Transaction Details') }} 
                <span class="text-primary">#{{ $transaction->transaction_number }}</span>
            </h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('atk.transactions.index') }}" class="btn btn-outline-secondary px-4 shadow-sm border-0 bg-white">
                <i class="fa-solid fa-arrow-left me-2"></i>
                {{ __('Back') }}
            </a>
            <a href="{{ route('atk.transactions.receipt', $transaction) }}" target="_blank" class="btn btn-primary px-4 shadow-sm border-0">
                <i class="fa-solid fa-print me-2"></i>
                {{ __('Print Receipt') }}
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content: Items Table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-lg overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="p-2 bg-light rounded-circle me-3">
                            <i class="fa-solid fa-box text-primary"></i>
                        </div>
                        <h6 class="m-0 font-weight-bold text-dark">{{ __('Items Purchased') }}</h6>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Product') }}</th>
                                    <th class="text-center">{{ __('Quantity') }}</th>
                                    <th class="text-end">{{ __('Price') }}</th>
                                    <th class="text-end pe-4">{{ __('Subtotal') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transaction->items as $item)
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="font-weight-bold text-dark">{{ $item->product_name }}</div>
                                        <small class="text-muted">SKU: {{ $item->product_sku ?? '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-pill badge-light px-3 text-dark border">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="text-end pe-4 font-weight-bold text-primary">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 py-4 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="text-muted mb-0 font-weight-normal">{{ __('Total Amount') }}</h5>
                        <h3 class="text-primary font-weight-bold mb-0">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Transaction Info -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-lg mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="p-2 bg-light rounded-circle me-3">
                            <i class="fa-solid fa-circle-info text-info"></i>
                        </div>
                        <h6 class="m-0 font-weight-bold text-dark">{{ __('Summary Info') }}</h6>
                    </div>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 py-3 border-0">
                            <label class="small text-muted mb-1 d-block">{{ __('Transaction Date') }}</label>
                            <span class="font-weight-bold text-dark">{{ $transaction->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        <div class="list-group-item px-0 py-3 border-0">
                            <label class="small text-muted mb-1 d-block">{{ __('Cashier') }}</label>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 10px;">
                                    {{ strtoupper(substr($transaction->user->name ?? 'A', 0, 1)) }}
                                </div>
                                <span class="font-weight-bold text-dark">{{ $transaction->user->name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="list-group-item px-0 py-3 border-0">
                            <label class="small text-muted mb-1 d-block">{{ __('Payment Status') }}</label>
                            <span class="badge px-3 py-2 bg-soft-success text-success border border-success rounded-pill" style="background-color: #e6fffa;">
                                <i class="fa-solid fa-circle-check me-1"></i> {{ __('Paid') }}
                            </span>
                        </div>
                        <div class="list-group-item px-0 py-3 border-0">
                            <label class="small text-muted mb-1 d-block">{{ __('Payment Method') }}</label>
                            <span class="badge px-3 py-2 bg-info text-white rounded-pill shadow-sm">
                                {{ strtoupper($transaction->payment_method) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            @if($transaction->cash_amount > 0)
            <div class="card border-0 shadow-sm rounded-lg bg-primary text-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2 opacity-75">
                        <span>{{ __('Cash Received') }}</span>
                        <span>Rp {{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-white-50">
                        <span class="h6 mb-0">{{ __('Change') }}</span>
                        <span class="h4 mb-0 font-weight-bold text-warning">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* Modern UI Custom Styles */
    .bg-soft-success { background-color: #f0fff4; }
    .rounded-lg { border-radius: 12px !important; }
    .font-weight-bold { font-weight: 700 !important; }
    .table thead th { border-top: 0; font-weight: 600; }
    .card-header { background-color: transparent !important; }
    .list-group-item { background-color: transparent !important; }
    
    /* Animation for smooth appearance */
    .card {
        transition: transform 0.2s ease-in-out;
    }
    .card:hover {
        transform: translateY(-2px);
    }
</style>
@endsection