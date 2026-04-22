@extends('layouts.app')

@section('content')
<div class="row">
    <!-- Info tiket utama -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0 mb-3 mb-md-4">
            <div class="card-body p-3 p-md-4">
                <!-- Header responsif -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1 text-truncate" style="max-width: 100%;">{{ $ticket->subject }}</h4>
                        <p class="text-muted small mb-0">{{ __('Tiket') }} #{{ $ticket->ticket_number }}</p>
                    </div>
                    <div class="d-flex gap-2 w-100 w-md-auto justify-content-md-end">
                        @if(Auth::user()->hasRole('admin'))
                        <form action="{{ route('tickets.notify', $ticket) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success text-white btn-sm flex-grow-1 flex-md-grow-0" onclick="return confirm('{{ __('Kirim notifikasi WhatsApp?') }}')">
                                <i class="fa-brands fa-whatsapp me-1"></i> <span class="d-none d-sm-inline">{{ __('Notifikasi') }}</span>
                            </button>
                        </form>
                        @endif
                        @can('ticket.edit')
                        <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-warning text-white btn-sm flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-pen-to-square me-1"></i> <span class="d-none d-sm-inline">{{ __('Ubah') }}</span>
                        </a>
                        @endcan
                        <a href="{{ route('tickets.index') }}" class="btn btn-light border btn-sm flex-grow-1 flex-md-grow-0">
                            {{ __('Kembali') }}
                        </a>
                    </div>
                </div>

                <!-- Ringkasan info -->
                <div class="row g-2 g-md-3 mb-4">
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">{{ __('Status') }}</small>
                        @php
                            $statusClass = match($ticket->status) {
                                'open' => 'bg-danger-subtle text-danger',
                                'solved' => 'bg-success-subtle text-success',
                                'closed' => 'bg-secondary-subtle text-secondary',
                                'in_progress' => 'bg-info-subtle text-info',
                                default => 'bg-warning-subtle text-warning'
                            };
                        @endphp
                        <div class="mt-1">
                            <span class="badge {{ $statusClass }} w-100">{{ __(ucfirst(str_replace('_', ' ', $ticket->status))) }}</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">{{ __('Prioritas') }}</small>
                        @php
                            $priorityClass = match($ticket->priority) {
                                'high' => 'bg-danger-subtle text-danger',
                                'medium' => 'bg-warning-subtle text-warning',
                                default => 'bg-primary-subtle text-primary'
                            };
                        @endphp
                        <div class="mt-1">
                            <span class="badge {{ $priorityClass }} w-100">{{ __(ucfirst($ticket->priority)) }}</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">{{ __('Jenis') }}</small>
                        <span class="d-block mt-1 fw-medium small text-truncate">{{ __(ucfirst(str_replace('_', ' ', $ticket->type))) }}</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">{{ __('Dibuat') }}</small>
                        <span class="d-block mt-1 fw-medium small">{{ $ticket->created_at->format('d M Y') }}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">{{ __('Deskripsi') }}</h6>
                    <div class=" p-3 rounded text-body-secondary small" style="white-space: pre-line;">
                        {{ $ticket->description ?? __('Tidak ada deskripsi.') }}
                    </div>
                </div>

                @if($ticket->location)
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold mb-0 small text-uppercase">{{ __('Lokasi') }}</h6>
                            @if(!in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete') || $ticket->technicians->contains('id', Auth::id())))
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small fw-bold" data-bs-toggle="modal" data-bs-target="#editLocationModal">
                                    <i class="fa-solid fa-pen-to-square"></i> {{ __('Ubah') }}
                                </button>
                            @endif
                        </div>
                        <p class="text-body-secondary small mb-0">
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($ticket->location) }}" target="_blank" class="text-decoration-none">
                                <i class="fa-solid fa-map-location-dot me-1"></i> {{ Str::limit($ticket->location, 50) }} <i class="fa-solid fa-arrow-up-right-from-square ms-1 small"></i>
                            </a>
                        </p>
                    </div>
                @elseif(!in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete') || $ticket->technicians->contains('id', Auth::id())))
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold mb-0 small text-uppercase">{{ __('Lokasi') }}</h6>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small fw-bold" data-bs-toggle="modal" data-bs-target="#editLocationModal">
                                <i class="fa-solid fa-plus"></i> {{ __('Tambah Lokasi') }}
                            </button>
                        </div>
                        <p class="text-muted small fst-italic">{{ __('Lokasi belum diatur.') }}</p>
                    </div>
                @endif

                <!-- Bagian foto penyelesaian -->
                <div class="mb-4">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">{{ __('Foto Penyelesaian') }}</h6>
                    
                    @if($ticket->photo_before || $ticket->photo_proof)
                        <div class="row g-2 g-md-3 mb-3">
                            @if($ticket->photo_before)
                            <div class="col-6 col-md-6">
                                <div class="card h-100">
                                    <div class="card-header  py-2">
                                        <small class="fw-bold text-uppercase">{{ __('Sebelum') }}</small>
                                    </div>
                                    <div class="card-body p-1 text-center">
                                        <img src="{{ Storage::url($ticket->photo_before) }}" class="img-fluid rounded border shadow-sm w-100" style="max-height: 250px; object-fit: cover;">
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($ticket->photo_proof)
                            <div class="col-6 col-md-6">
                                <div class="card h-100">
                                    <div class="card-header  py-2">
                                        <small class="fw-bold text-uppercase">{{ __('Sesudah') }}</small>
                                    </div>
                                    <div class="card-body p-1 text-center">
                                        <img src="{{ Storage::url($ticket->photo_proof) }}" class="img-fluid rounded border shadow-sm w-100" style="max-height: 250px; object-fit: cover;">
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    @elseif(in_array($ticket->status, ['solved', 'closed']))
                        <div class="alert alert-secondary py-2 small">
                            <i class="fa-solid fa-info-circle me-1"></i> {{ __('Tiket berstatus :status. Tidak ada foto tersedia.', ['status' => __(ucfirst($ticket->status))]) }}
                        </div>
                    @endif

                    @if(!in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete') || $ticket->technicians->contains('id', Auth::id())))
                        <div class=" p-3 rounded border border-success-subtle">
                            <h6 class="fw-bold mb-3 text-success"><i class="fa-solid fa-check-circle me-1"></i> {{ __('Tandai Selesai') }}</h6>
                            <form action="{{ route('tickets.complete', $ticket) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-2 mb-md-0">
                                        <label for="photo_before" class="form-label small fw-bold">{{ __('Foto Sebelum') }} <span class="text-muted small fw-normal">({{ __('Opsional') }})</span></label>
                                        <input type="file" class="form-control" id="photo_before" name="photo_before" accept="image/*">
                                        @error('photo_before')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="photo_proof" class="form-label small fw-bold">{{ __('Foto Sesudah') }} <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control" id="photo_proof" name="photo_proof" required accept="image/*">
                                        @error('photo_proof')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label small fw-bold">{{ __('Catatan Penyelesaian') }} ({{ __('Opsional') }})</label>
                                    <textarea class="form-control" id="description" name="description" rows="2" placeholder="{{ __('Jelaskan solusi yang dilakukan...') }}"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100 py-2 fw-bold" onclick="return confirm('{{ __('Selesaikan tiket ini?') }}')">
                                    <i class="fa-solid fa-check me-1"></i> {{ __('Selesaikan Tiket') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Log aktivitas -->
        <div class="card shadow-sm border-0">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">{{ __('Log Aktivitas') }}</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush border-start border-3 ms-2">
                    @forelse($ticket->logs->sortByDesc('created_at') as $log)
                        <li class="list-group-item border-0 ps-4 py-3 position-relative">
                            <div class="position-absolute top-0 start-0 translate-middle-x mt-4 bg-body border border-2 border-primary rounded-circle" style="width: 12px; height: 12px; left: -1.5px;"></div>
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="w-100">
                                    <h6 class="mb-1 fw-bold text-body-emphasis text-break">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</h6>
                                    <p class="mb-1 text-body-secondary small text-break">{{ $log->description }}</p>
                                    <small class="text-body-secondary fst-italic">{{ __('oleh') }} {{ $log->user->name ?? __('Sistem') }}</small>
                                </div>
                                <small class="text-body-secondary text-nowrap ms-2">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item border-0 text-body-secondary fst-italic">{{ __('Belum ada aktivitas tercatat.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Informasi samping -->
    <div class="col-12 col-lg-4">
        <!-- Informasi pelanggan -->
        <div class="card shadow-sm border-0 mb-3 mb-md-4">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">{{ __('Detail Pelanggan') }}</h6>
            </div>
            <div class="card-body">
                @if($ticket->customer)
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                            <i class="fa-solid fa-user fa-lg"></i>
                        </div>
                        <div class="overflow-hidden">
                            <h6 class="mb-0 fw-bold text-truncate">{{ $ticket->customer->name }}</h6>
                            <small class="text-body-secondary">{{ __('Pelanggan') }}</small>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($ticket->customer->address) }}" target="_blank" class="text-decoration-none text-body-secondary">
                                <i class="fa-solid fa-location-dot me-2"></i> <span class="text-break">{{ Str::limit($ticket->customer->address, 40) }}</span>
                            </a>
                        </li>
                        <li class="mb-2"><i class="fa-solid fa-phone me-2 text-body-secondary"></i> <a href="tel:{{ $ticket->customer->phone }}" class="text-decoration-none">{{ $ticket->customer->phone }}</a></li>
                        <li class="mb-2"><i class="fa-solid fa-box me-2 text-body-secondary"></i> {{ $ticket->customer->package }}</li>
                    </ul>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ route('customers.edit', $ticket->customer) }}" class="btn btn-outline-primary btn-sm">{{ __('Lihat Pelanggan') }}</a>
                        @if($ticket->type === 'pasang_baru' && !in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete') || $ticket->technicians->contains('id', Auth::id())))
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                            <i class="fa-solid fa-user-pen me-1"></i> {{ __('Ubah Pelanggan') }}
                        </button>
                        @endif
                    </div>
                @else
                    <p class="text-body-secondary small mb-0">{{ __('Belum ada pelanggan terkait.') }}</p>
                @endif
            </div>
        </div>

        <!-- Informasi jaringan -->
        <div class="card shadow-sm border-0 mb-3 mb-md-4">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">{{ __('Detail Jaringan') }}</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">{{ __('ODP') }}</small>
                        @if($ticket->odp)
                            <span class="fw-medium">{{ $ticket->odp->name }}</span>
                            @if($ticket->odp->region)
                                <br><small class="text-muted">{{ $ticket->odp->region->name }}</small>
                            @endif
                        @else
                            <span class="text-muted small fst-italic">{{ __('Belum Ditugaskan') }}</span>
                        @endif
                    </li>
                    <li>
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">{{ __('Pengurus') }}</small>
                        @if($ticket->coordinator)
                            <div class="fw-medium">{{ $ticket->coordinator->name }}</div>
                            @if($ticket->coordinator->phone)
                                <small class="d-block"><a href="tel:{{ $ticket->coordinator->phone }}" class="text-decoration-none"><i class="fa-solid fa-phone me-1 text-muted"></i> {{ $ticket->coordinator->phone }}</a></small>
                            @endif
                            @if($ticket->coordinator->region)
                                <small class="d-block text-muted">{{ $ticket->coordinator->region->name }}</small>
                            @endif
                        @else
                            <span class="text-muted small fst-italic">{{ __('Belum Ditugaskan') }}</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>

        <!-- Informasi teknisi -->
        <div class="card shadow-sm border-0">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">{{ __('Teknisi Ditugaskan') }}</h6>
            </div>
            <div class="card-body">
                @if($ticket->technicians->count() > 0)
                    @foreach($ticket->technicians as $tech)
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                                <i class="fa-solid fa-screwdriver-wrench fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $tech->name }}</h6>
                                <small class="text-body-secondary">{{ __('Teknisi') }}</small>
                            </div>
                        </div>
                        <div class="d-grid mb-3">
                            <a href="mailto:{{ $tech->email }}" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-envelope me-1"></i> {{ __('Email') }}</a>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-3 text-body-secondary">
                        <i class="fa-solid fa-user-xmark fa-2x mb-2 opacity-25"></i>
                        <p class="small mb-0">{{ __('Belum ada teknisi ditugaskan.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($ticket->customer && $ticket->type === 'pasang_baru' && !in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete') || $ticket->technicians->contains('id', Auth::id())))
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCustomerModalLabel">{{ __('Ubah Pelanggan') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tickets.updateCustomer', $ticket) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cust_name" class="form-label">{{ __('Nama') }}</label>
                            <input type="text" class="form-control" id="cust_name" name="name" value="{{ $ticket->customer->name }}">
                        </div>
                        <div class="col-md-6">
                            <label for="cust_phone" class="form-label">{{ __('Nomor HP') }}</label>
                            <input type="tel" class="form-control" id="cust_phone" name="phone" value="{{ $ticket->customer->phone }}">
                        </div>
                        <div class="col-12">
                            <label for="cust_address" class="form-label">{{ __('Alamat') }}</label>
                            <input type="text" class="form-control" id="cust_address" name="address" value="{{ $ticket->customer->address }}">
                        </div>
                        <div class="col-md-6">
                            <label for="cust_package" class="form-label">{{ __('Paket') }}</label>
                            <input type="text" class="form-control" id="cust_package" name="package" value="{{ $ticket->customer->package }}">
                        </div>
                        <div class="col-md-6">
                            <label for="cust_onu_serial" class="form-label">{{ __('ONU Serial') }}</label>
                            <input type="text" class="form-control" id="cust_onu_serial" name="onu_serial" value="{{ $ticket->customer->onu_serial }}">
                        </div>
                        <div class="col-md-6">
                            <label for="cust_device_model" class="form-label">{{ __('Model Perangkat') }}</label>
                            <input type="text" class="form-control" id="cust_device_model" name="device_model" value="{{ $ticket->customer->device_model }}">
                        </div>
                        <div class="col-md-6">
                            <label for="cust_pppoe_user" class="form-label">{{ __('User PPPoE') }}</label>
                            <input type="text" class="form-control" id="cust_pppoe_user" name="pppoe_user" value="{{ $ticket->customer->pppoe_user }}">
                        </div>
                        <div class="col-md-6">
                            <label for="cust_pppoe_password" class="form-label">{{ __('Password PPPoE') }}</label>
                            <input type="text" class="form-control" id="cust_pppoe_password" name="pppoe_password" value="{{ $ticket->customer->pppoe_password }}">
                        </div>
                        <div class="col-md-6">
                            <label for="cust_ssid_name" class="form-label">{{ __('Nama SSID') }}</label>
                            <input type="text" class="form-control" id="cust_ssid_name" name="ssid_name" value="{{ $ticket->customer->ssid_name }}">
                        </div>
                        <div class="col-md-6">
                            <label for="cust_ssid_password" class="form-label">{{ __('Password SSID') }}</label>
                            <input type="text" class="form-control" id="cust_ssid_password" name="ssid_password" value="{{ $ticket->customer->ssid_password }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan Perubahan') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if(!in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete') || $ticket->technicians->contains('id', Auth::id())))
<div class="modal fade" id="editLocationModal" tabindex="-1" aria-labelledby="editLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLocationModalLabel">{{ __('Ubah Lokasi') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tickets.updateLocation', $ticket) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="location" class="form-label">{{ __('Koordinat (Lintang, Bujur)') }}</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="location" name="location" value="{{ $ticket->location }}" placeholder="-6.200000, 106.816666">
                            <button class="btn btn-outline-secondary" type="button" id="getCurrentLocation">
                                <i class="fa-solid fa-crosshairs"></i>
                            </button>
                        </div>
                        <div class="form-text">{{ __('Klik ikon bidik untuk mengambil lokasi saat ini.') }}</div>
                    </div>
                    <div class="mt-3">
                        <div class="form-text text-muted mb-2">{{ __('Ketuk peta untuk menentukan lokasi.') }}</div>
                        <div id="ticket-map-picker" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan Perubahan') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif



@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const getCurrentLocationBtn = document.getElementById('getCurrentLocation');
        const locationInput = document.getElementById('location');

        if (getCurrentLocationBtn && locationInput) {
            getCurrentLocationBtn.addEventListener('click', function() {
                if (navigator.geolocation) {
                    getCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        locationInput.value = `${lat}, ${lng}`;
                        getCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-crosshairs"></i>';
                        // Perbarui nilai input agar peta bisa ikut menggunakan koordinat terbaru.
                    }, function(error) {
                        alert('{{ __('Kesalahan') }}: ' + error.message);
                        getCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-crosshairs"></i>';
                    });
                } else {
                    alert('{{ __('Geolocation tidak didukung.') }}');
                }
            });
        }
    });
</script>
@endpush
@endsection
