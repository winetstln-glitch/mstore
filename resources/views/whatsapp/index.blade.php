@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-success">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-brands fa-whatsapp me-2"></i>{{ __('WhatsApp Settings') }}</h5>
            </div>

            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-circle-info me-2"></i>{{ __('Info Konfigurasi') }}</h6>
                    <p class="mb-0">{{ __('Pengaturan API URL dan API KEY dilakukan melalui file') }} <code>.env</code>. {{ __('Halaman ini khusus untuk mengatur template pesan.') }}</p>
                    <hr>
                    <p class="mb-0 small">
                        <strong>Current API URL:</strong> <code>{{ env('WHATSAPP_API_URL', 'Not Set') }}</code><br>
                        <strong>Current API Key:</strong> <code>{{ Str::mask(env('WHATSAPP_API_KEY', ''), '*', 3, -3) ?: 'Not Set' }}</code>
                    </p>
                </div>

                <form action="{{ route('whatsapp.update') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="whatsapp_ticket_template" class="form-label fw-bold">{{ __('Template Pesan Notifikasi Tiket') }}</label>
                        <textarea name="whatsapp_ticket_template" id="whatsapp_ticket_template" rows="10" class="form-control font-monospace">{{ $template->value }}</textarea>
                        <div class="form-text mt-2">
                            <strong>{{ __('Variables Available:') }}</strong><br>
                            <code>{technician_name}</code> - {{ __('Nama Teknisi') }}<br>
                            <code>{ticket_number}</code> - {{ __('Nomor Tiket') }}<br>
                            <code>{subject}</code> - {{ __('Judul Masalah') }}<br>
                            <code>{customer_name}</code> - {{ __('Nama Pelanggan') }}<br>
                            <code>{location}</code> - {{ __('Alamat / Koordinat') }}<br>
                            <code>{url}</code> - {{ __('Link Tiket') }}
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Save Settings') }}
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <h6 class="fw-bold"><i class="fa-solid fa-flask me-2"></i>{{ __('Test Connection') }}</h6>
                <form action="{{ route('whatsapp.test') }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label for="test_phone" class="form-label">{{ __('Test Phone Number') }}</label>
                        <input type="text" name="test_phone" id="test_phone" class="form-control" placeholder="08123456789" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-success w-100">
                            <i class="fa-regular fa-paper-plane me-1"></i> {{ __('Send Test') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
