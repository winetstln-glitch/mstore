@extends('layouts.app')

@section('title', 'Payment Gateway Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 fw-bold">Payment Gateway</h1>
            <p class="text-muted">Kelola integrasi pembayaran dan pantau performa transaksi.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <i class="fa-solid fa-shield-check me-1"></i> Secure Credential Management Active
            </span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 bg-primary-subtle text-primary p-3 rounded-3">
                            <i class="fa-solid fa-calendar-day fa-xl"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted small text-uppercase fw-bold mb-0">Hari Ini</h6>
                            <h3 class="mb-0 fw-bold">{{ $stats['today_total'] }}</h3>
                        </div>
                    </div>
                    <div class="text-muted small">Total Transaksi Masuk</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 bg-success-subtle text-success p-3 rounded-3">
                            <i class="fa-solid fa-money-bill-trend-up fa-xl"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted small text-uppercase fw-bold mb-0">Pendapatan</h6>
                            <h3 class="mb-0 fw-bold">Rp{{ number_format($stats['today_amount'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="text-muted small">Berhasil Dibayar Hari Ini</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 bg-warning-subtle text-warning p-3 rounded-3">
                            <i class="fa-solid fa-clock-rotate-left fa-xl"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted small text-uppercase fw-bold mb-0">Pending</h6>
                            <h3 class="mb-0 fw-bold">{{ $stats['pending'] }}</h3>
                        </div>
                    </div>
                    <div class="text-muted small">Menunggu Pembayaran</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 bg-info-subtle text-info p-3 rounded-3">
                            <i class="fa-solid fa-chart-line fa-xl"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted small text-uppercase fw-bold mb-0">Success Rate</h6>
                            <h3 class="mb-0 fw-bold">{{ $stats['success_rate'] }}%</h3>
                        </div>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $stats['success_rate'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gateways Grid -->
    <h5 class="fw-bold mb-3 mt-5">Active Payment Gateways</h5>
    <div class="row g-4">
        @foreach($gatewayStatuses as $id => $gw)
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 gateway-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="d-flex align-items-center">
                            <div class="gateway-logo bg-light p-2 rounded-3 me-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-building-columns fa-lg text-secondary"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $gw['name'] }}</h5>
                                <span class="text-muted small">ID: {{ $gw['id'] }}</span>
                            </div>
                        </div>
                        @php 
                            $status = \App\Models\Setting::getValue("payment_{$id}_status", 'unknown');
                            $statusClass = $status === 'connected' ? 'bg-success' : ($status === 'error' ? 'bg-danger' : 'bg-secondary');
                        @endphp
                        <span class="badge {{ $statusClass }} rounded-pill px-3">
                            <i class="fa-solid fa-circle small me-1"></i> {{ ucfirst($status === 'unknown' ? 'Not Tested' : $status) }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Environment:</span>
                            <span class="badge {{ $gw['is_sandbox'] ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} px-2">
                                {{ $gw['is_sandbox'] ? 'Sandbox Mode' : 'Production Mode' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Merchant Code:</span>
                            <span class="fw-medium small">{{ $gw['merchant_code'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Last Sync:</span>
                            <span class="text-muted small">{{ $gw['last_sync'] }}</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('payment.gateway', $id) }}" class="btn btn-primary rounded-3">
                            <i class="fa-solid fa-gear me-1"></i> Configure Gateway
                        </a>
                        <button class="btn btn-outline-secondary rounded-3 btn-test-connection" data-gateway="{{ $id }}">
                            <i class="fa-solid fa-plug-circle-check me-1"></i> Test Connection
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        <!-- Placeholder for future gateways -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-2 border-dashed bg-light opacity-75">
                <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center text-center">
                    <div class="bg-white p-3 rounded-circle mb-3 shadow-sm">
                        <i class="fa-solid fa-plus fa-2xl text-muted"></i>
                    </div>
                    <h5 class="fw-bold text-muted">Coming Soon</h5>
                    <p class="text-muted small mb-0">Xendit, Tripay, and more gateways integration are in development.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('.btn-test-connection').click(function() {
        const btn = $(this);
        const gateway = btn.data('gateway');
        const originalHtml = btn.html();
        
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Testing...').prop('disabled', true);
        
        $.ajax({
            url: `/payment-gateway/${gateway}/test`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: `${response.message} (Merchant: ${response.merchant_name}, Env: ${response.environment})`,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        title: 'Connection Failed',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while testing the connection.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush

<style>
.gateway-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.gateway-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
}
.border-dashed {
    border-style: dashed !important;
}
</style>
@endsection
