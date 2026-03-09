@extends('layouts.app')

@section('title', __('Transaction Details'))

@php
// Logic as per receipt requirements
$generalStoreName = \App\Models\Setting::getValue('store_name', config('app.name', 'MStore'));
$generalStoreAddress = \App\Models\Setting::getValue('store_address', 'Jl. Contoh No. 1');
$generalStorePhone = \App\Models\Setting::getValue('store_phone', '081234567890');
$generalStoreLogo = \App\Models\Setting::getValue('store_logo', '');
$generalStoreLogo = $generalStoreLogo && !str_starts_with($generalStoreLogo, 'http') && !str_starts_with($generalStoreLogo, 'data:') && !str_starts_with($generalStoreLogo, '/')
? asset($generalStoreLogo)
: $generalStoreLogo;

$receiptStoreName = \App\Models\Setting::getValue('wash_store_name', $generalStoreName ?: 'AUTO WASH');

$customerName = trim((string) ($transaction->customer_name ?? ''));
$customerName = $customerName !== '' ? $customerName : '-';
$vehiclePlate = strtoupper(trim((string) ($transaction->vehicle_plate ?? '')));
$vehiclePlate = $vehiclePlate !== '' ? $vehiclePlate : '-';


@endphp

@section('content')

<div class="container-fluid py-4">
<!-- Header Section -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
<div>
<nav aria-label="breadcrumb">
<ol class="breadcrumb mb-1">
<li class="breadcrumb-item"><a href="{{ route('wash.transactions.index') }}" class="text-decoration-none text-muted">Transaksi</a></li>
<li class="breadcrumb-item active" aria-current="page">Detail #{{ $transaction->transaction_number }}</li>
</ol>
</nav>
<h1 class="h3 font-weight-bold text-gray-800 mb-0">
{{ __('Transaction Details') }}
<span class="text-primary">#{{ $transaction->transaction_number }}</span>
</h1>
</div>
<div class="d-flex gap-2">
<a href="{{ route('wash.transactions.index') }}" class="btn btn-outline-secondary px-4 shadow-sm border-0 bg-white">
<i class="fa-solid fa-arrow-left me-2"></i>
{{ __('Back') }}
</a>
<a href="{{ route('wash.transactions.receipt', $transaction) }}" target="_blank" class="btn btn-primary px-4 shadow-sm border-0">
<i class="fa-solid fa-print me-2"></i>
{{ __('Print Receipt') }}
</a>
</div>
</div>

<div class="row g-4">
    <!-- Main Content: Items Table -->
    <div class="col-lg-8">
        <!-- Customer & Queue Info Card -->
        <div class="card border-0 shadow-sm rounded-lg mb-4">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-7 border-end-md">
                        <div class="d-flex align-items-center">
                            <div class="p-3 bg-soft-primary rounded-lg me-3 text-primary">
                                <i class="fa-solid fa-user-tag fa-lg"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">{{ __('Customer / Vehicle') }}</small>
                                <span class="h6 mb-0 font-weight-bold text-dark">{{ $customerName }} / <span class="text-primary">{{ $vehiclePlate }}</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 mt-3 mt-md-0">
                        @if(!empty($transaction->queue_number))
                        <div class="d-flex align-items-center justify-content-md-center">
                            <div class="p-3 bg-soft-warning rounded-lg me-3 text-warning">
                                <i class="fa-solid fa-list-ol fa-lg"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">{{ __('Queue Number') }}</small>
                                <span class="h5 mb-0 font-weight-bold text-dark">#{{ $transaction->queue_number }}</span>
                            </div>
                        </div>
                        @else
                        <div class="text-center text-muted italic small">
                            {{ __('No queue assigned') }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

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
                                <th class="ps-4 py-3">{{ __('Service') }}</th>
                                <th class="text-center">{{ __('Quantity') }}</th>
                                <th class="text-end">{{ __('Price') }}</th>
                                <th class="text-end pe-4">{{ __('Subtotal') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaction->items as $item)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="font-weight-bold text-dark">{{ $item->service_name }}</div>
                                    <small class="text-muted">ID Item: #{{ $item->id }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-pill bg-light text-dark border px-3 py-2 font-weight-bold">{{ (float)$item->quantity }}</span>
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
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted">{{ __('Store Location') }}</span>
                    <span class="font-weight-bold">{{ $receiptStoreName }}</span>
                </div>
                @if(($transaction->discount_amount ?? 0) > 0)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">{{ __('Discount') }}</span>
                    <span class="text-danger font-weight-bold">-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
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
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; font-size: 11px;">
                                {{ strtoupper(substr($transaction->user->name ?? 'A', 0, 1)) }}
                            </div>
                            <span class="font-weight-bold text-dark">{{ $transaction->user->name ?? 'Unknown' }}</span>
                        </div>
                    </div>
                    <div class="list-group-item px-0 py-3 border-0">
                        <label class="small text-muted mb-1 d-block">{{ __('Payment Status') }}</label>
                        <span class="badge px-3 py-2 bg-soft-success text-success border border-success rounded-pill">
                            <i class="fa-solid fa-circle-check me-1"></i> {{ __('Paid & Completed') }}
                        </span>
                    </div>
                    <div class="list-group-item px-0 py-3 border-0">
                        <label class="small text-muted mb-1 d-block">{{ __('Payment Method') }}</label>
                        <span class="badge px-3 py-2 bg-dark text-white rounded-pill shadow-sm">
                            <i class="fa-solid fa-credit-card me-1 small"></i> {{ strtoupper($transaction->payment_method ?? 'CASH') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if(($transaction->cash_amount ?? 0) > 0)
        <div class="card border-0 shadow-sm rounded-lg bg-primary text-white overflow-hidden position-relative">
            <!-- Decorative background icon -->
            <i class="fa-solid fa-wallet position-absolute opacity-10" style="right: -10px; bottom: -10px; font-size: 80px;"></i>
            
            <div class="card-body p-4 position-relative">
                <div class="d-flex justify-content-between mb-2 opacity-75">
                    <span>{{ __('Cash Received') }}</span>
                    <span class="font-weight-bold">Rp {{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
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
/* Modern UI Custom Utility Classes */
.bg-soft-success { background-color: #f0fff4 !important; }
.bg-soft-primary { background-color: #eef2ff !important; }
.bg-soft-warning { background-color: #fffbeb !important; }
.rounded-lg { border-radius: 12px !important; }
.font-weight-bold { font-weight: 700 !important; }
.table thead th { border-top: 0; font-weight: 600; border-bottom-width: 1px; }
.card-header { background-color: transparent !important; }
.list-group-item { background-color: transparent !important; }
.opacity-10 { opacity: 0.1; }

@media (min-width: 768px) {
    .border-end-md { border-right: 1px solid #e2e8f0; }
}

/* Hover effect for cleaner look */
.card {
    transition: box-shadow 0.2s ease-in-out;
}
.card:hover {
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important;
}


</style>

@endsection