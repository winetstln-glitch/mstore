@extends('layouts.app')

@section('title', 'Configure ' . $gateway->getName())

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('payment.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="h3 mb-0 fw-bold">Configure {{ $gateway->getName() }}</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('payment.dashboard') }}">Payment Gateway</a></li>
                            <li class="breadcrumb-item active">{{ $gateway->getName() }}</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="row g-4">
                <!-- Settings Form -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 py-3 px-4">
                            <h5 class="mb-0 fw-bold">API Configuration</h5>
                        </div>
                        <div class="card-body p-4 pt-0">
                            <form action="{{ route('payment.gateway.update', $gatewayId) }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    @foreach($gateway->getConfigKeys() as $key)
                                        @php
                                            $setting = $settings->where('key', "{$gatewayId}_{$key}")->first();
                                            $value = $setting ? $setting->value : '';
                                            $isSensitive = in_array($key, ['api_key', 'secret_key', 'server_key', 'client_secret', 'webhook_secret']);
                                        @endphp

                                        <div class="col-12">
                                            <label class="form-label fw-semibold">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                            
                                            @if($key === 'sandbox')
                                                <div class="form-check form-switch p-3 bg-light rounded-3">
                                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="sandbox" id="sandbox" value="1" {{ $value == '1' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="sandbox">Enable Sandbox Mode (Testing)</label>
                                                </div>
                                            @elseif($isSensitive)
                                                <div class="input-group">
                                                    <input type="password" class="form-control secure-input" name="{{ $key }}" value="{{ $value }}" placeholder="Enter {{ $key }}">
                                                    <button class="btn btn-outline-secondary btn-toggle-visibility" type="button">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-secondary btn-copy" type="button" data-value="{{ $value }}">
                                                        <i class="fa-solid fa-copy"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text small">Credential ini akan disimpan dengan enkripsi AES-256.</div>
                                            @else
                                                <input type="text" class="form-control" name="{{ $key }}" value="{{ $value }}" placeholder="Enter {{ $key }}">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4 rounded-3">
                                        <i class="fa-solid fa-save me-1"></i> Save Changes
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary px-4 rounded-3 btn-test-connection" data-gateway="{{ $gatewayId }}">
                                        <i class="fa-solid fa-plug-circle-check me-1"></i> Test Connection
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Webhook Section -->
                    <div class="card border-0 shadow-sm rounded-4 mt-4">
                        <div class="card-header bg-white border-0 py-3 px-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">Webhook & Callback</h5>
                                <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill small">
                                    <i class="fa-solid fa-circle-check me-1"></i> Webhook Active
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-4 pt-0">
                            <p class="text-muted small mb-3">Gunakan URL di bawah ini untuk dikonfigurasi pada dashboard provider {{ $gateway->getName() }}.</p>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Notification / Callback URL</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light font-monospace small" value="{{ $callbackUrl }}" readonly>
                                    <button class="btn btn-outline-primary btn-copy-text" type="button" data-text="{{ $callbackUrl }}">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label small fw-bold text-uppercase">Return / Finish URL</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light font-monospace small" value="{{ $returnUrl }}" readonly>
                                    <button class="btn btn-outline-primary btn-copy-text" type="button" data-text="{{ $returnUrl }}">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Sidebar -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">Status Gatekeeper</h6>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Status</span>
                                    @php 
                                        $status = \App\Models\Setting::getValue("payment_{$gatewayId}_status", 'unknown');
                                        $statusClass = $status === 'connected' ? 'text-success' : ($status === 'error' ? 'text-danger' : 'text-secondary');
                                    @endphp
                                    <span class="{{ $statusClass }} fw-bold small">
                                        <i class="fa-solid fa-circle small me-1"></i> {{ ucfirst($status) }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Last Verified</span>
                                    <span class="fw-medium small">{{ \App\Models\Setting::getValue("payment_{$gatewayId}_last_sync", 'Never') }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">TLS/SSL</span>
                                    <span class="text-success fw-bold small"><i class="fa-solid fa-lock me-1"></i> Secure</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 bg-primary text-white shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-2">Bantuan {{ $gateway->getName() }}</h6>
                            <p class="small opacity-75 mb-3">Pastikan Anda menggunakan kredensial yang sesuai dengan environment (Sandbox vs Production).</p>
                            <a href="#" class="btn btn-light btn-sm w-100 rounded-3 text-primary fw-bold">
                                <i class="fa-solid fa-book-open me-1"></i> Baca Dokumentasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle visibility
    $('.btn-toggle-visibility').click(function() {
        const input = $(this).siblings('.secure-input');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Copy to clipboard
    $('.btn-copy').click(function() {
        const value = $(this).siblings('.secure-input').val();
        if (!value) return;
        
        navigator.clipboard.writeText(value).then(() => {
            toastr.success('Credential copied to clipboard!');
        });
    });

    $('.btn-copy-text').click(function() {
        const text = $(this).data('text');
        navigator.clipboard.writeText(text).then(() => {
            toastr.success('URL copied to clipboard!');
        });
    });

    // Test connection
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
@endsection
