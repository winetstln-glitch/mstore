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
                        <button type="button" class="btn btn-success text-white btn-sm flex-grow-1 flex-md-grow-0" data-bs-toggle="modal" data-bs-target="#ticketNotifyModal">
                            <i class="fa-brands fa-whatsapp me-1"></i> <span class="d-none d-sm-inline">{{ __('Notifikasi') }}</span>
                        </button>
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
                @php
                    $elapsedMinutes = $ticket->created_at ? $ticket->created_at->diffInMinutes(now()) : 0;
                    $hasEstimate = !is_null($ticket->estimated_duration_minutes);
                    $isOverEstimate = $hasEstimate
                        && !in_array($ticket->status, ['solved', 'closed'])
                        && $elapsedMinutes > $ticket->estimated_duration_minutes;
                @endphp
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
                    <div class="col-6 col-md-3">
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">{{ __('Estimasi') }}</small>
                        @if($hasEstimate)
                            <span class="d-block mt-1 fw-medium small">{{ $ticket->estimated_duration_minutes }} {{ __('menit') }}</span>
                            <small class="{{ $isOverEstimate ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ __('Durasi berjalan') }}: {{ $elapsedMinutes }} {{ __('menit') }}
                            </small>
                        @else
                            <span class="d-block mt-1 fw-medium small text-muted">{{ __('Belum diatur') }}</span>
                        @endif
                    </div>
                </div>
                @if($isOverEstimate)
                    <div class="alert alert-warning small py-2">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        {{ __('Durasi pengerjaan sudah melewati estimasi. Mohon percepat proses atau lakukan eskalasi jika kendala belum teratasi.') }}
                    </div>
                @endif

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
                        <div class="alert alert-info small">
                            <h6 class="fw-bold mb-2"><i class="fa-solid fa-book me-1"></i> {{ __('Panduan Menyelesaikan Tiket') }}</h6>
                            <ol class="mb-2 ps-3">
                                <li>{{ __('Konfirmasi keluhan pelanggan dan validasi lokasi perangkat/ODP.') }}</li>
                                <li>{{ __('Lakukan pemeriksaan fisik dan teknis (kabel, adaptor, LOS, ONU/router, serta jalur distribusi).') }}</li>
                                <li>{{ __('Jalankan tindakan perbaikan sesuai hasil diagnosa, lalu uji koneksi hingga layanan kembali normal.') }}</li>
                                <li>{{ __('Ambil bukti foto sesudah perbaikan (wajib), dan foto sebelum perbaikan jika tersedia.') }}</li>
                                <li>{{ __('Isi catatan penyelesaian dengan ringkas: penyebab, tindakan, dan hasil uji akhir.') }}</li>
                            </ol>
                            <div class="mb-0">
                                <strong>{{ __('Checklist sebelum klik "Selesaikan Tiket":') }}</strong>
                                {{ __('Layanan normal, pelanggan terkonfirmasi, bukti foto tersedia, dan catatan solusi sudah diisi.') }}
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ticketSopModal">
                                    <i class="fa-solid fa-book-open me-1"></i> {{ __('Lihat SOP Lengkap Teknisi WiFi') }}
                                </button>
                                <a href="{{ route('tickets.sop.pdf', $ticket) }}" class="btn btn-sm btn-outline-success">
                                    <i class="fa-solid fa-file-pdf me-1"></i> {{ __('Unduh SOP PDF') }}
                                </a>
                            </div>
                        </div>
                        <div class=" p-3 rounded border border-success-subtle">
                            <h6 class="fw-bold mb-3 text-success"><i class="fa-solid fa-check-circle me-1"></i> {{ __('Tandai Selesai') }}</h6>
                            @php
                                $showCompletionOnuScan = $ticket->customer && (
                                    $ticket->type === 'pasang_baru'
                                    || Str::contains(strtolower((string) $ticket->type), ['pergantian', 'pergati', 'ganti_onu', 'penggantian_onu'])
                                );
                            @endphp
                            <form id="completeTicketForm" action="{{ route('tickets.complete', $ticket) }}" method="POST" enctype="multipart/form-data">
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
                                @if($showCompletionOnuScan)
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label for="completion_onu_serial" class="form-label small fw-bold">{{ __('ONU SN') }} <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control @error('completion_onu_serial') is-invalid @enderror" id="completion_onu_serial" name="completion_onu_serial" required value="{{ old('completion_onu_serial', $ticket->customer->onu_serial ?? '') }}" placeholder="{{ __('Contoh: ZTEGC1234567') }}">
                                                <button class="btn btn-outline-primary" type="button" id="startCompleteOnuQrScan">
                                                    <i class="fa-solid fa-qrcode me-1"></i>{{ __('Scan SN') }}
                                                </button>
                                            </div>
                                            @error('completion_onu_serial')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div id="completionOnuQrScanStatus" class="small text-muted mt-2"></div>
                                            <div id="completionOnuQrScannerWrapper" class="mt-2 d-none">
                                                <div id="completion-onu-qr-reader" class="ticket-qr-reader" style="width: 100%; max-width: 520px;"></div>
                                                <button class="btn btn-sm btn-outline-danger mt-2" type="button" id="stopCompleteOnuQrScan">
                                                    <i class="fa-solid fa-stop me-1"></i>{{ __('Hentikan Scan') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="completion_wan_mac" class="form-label small fw-bold">{{ __('WAN MAC') }} <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control @error('completion_wan_mac') is-invalid @enderror" id="completion_wan_mac" name="completion_wan_mac" required value="{{ old('completion_wan_mac', $ticket->customer->wan_mac ?? '') }}" placeholder="{{ __('Contoh: AA:BB:CC:DD:EE:FF') }}">
                                                <button class="btn btn-outline-primary" type="button" id="startCompleteMacQrScan">
                                                    <i class="fa-solid fa-qrcode me-1"></i>{{ __('Scan MAC') }}
                                                </button>
                                            </div>
                                            @error('completion_wan_mac')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div id="completionMacQrScanStatus" class="small text-muted mt-2"></div>
                                            <div id="completionMacQrScannerWrapper" class="mt-2 d-none">
                                                <div id="completion-mac-qr-reader" class="ticket-qr-reader" style="width: 100%; max-width: 520px;"></div>
                                                <button class="btn btn-sm btn-outline-danger mt-2" type="button" id="stopCompleteMacQrScan">
                                                    <i class="fa-solid fa-stop me-1"></i>{{ __('Hentikan Scan') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    @if($showCompletionOnuScan)
                                        <div class="alert alert-warning small py-2">
                                            <i class="fa-solid fa-circle-info me-1"></i>
                                            {{ __('Untuk instalasi baru/pergantian, SN ONU dan WAN MAC wajib diisi sebelum tiket diselesaikan.') }}
                                        </div>
                                    @endif
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
                        @php
                            $isOnuProvisioningTicket = $ticket->type === 'pasang_baru'
                                || Str::contains(strtolower((string) $ticket->type), ['pergantian', 'pergati', 'ganti_onu', 'penggantian_onu']);
                        @endphp
                        @if($isOnuProvisioningTicket && !in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete') || $ticket->technicians->contains('id', Auth::id())))
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

@if(Auth::user()->hasRole('admin'))
@php
    $ticketNotifyTemplate = \App\Models\Setting::getValue('whatsapp_ticket_template', \App\Notifications\TicketAssignedNotification::defaultTemplate());
@endphp
<div class="modal fade" id="ticketNotifyModal" tabindex="-1" aria-labelledby="ticketNotifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketNotifyModalLabel">{{ __('Template Notifikasi Teknisi') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tickets.notify', $ticket) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-2">{{ __('Pesan akan dikirim ke masing-masing teknisi yang ditugaskan. Placeholder akan diisi otomatis.') }}</p>
                    <textarea class="form-control" name="notification_template" rows="12">{{ $ticketNotifyTemplate }}</textarea>
                    <div class="form-text mt-2">
                        Placeholder: <code>{technician_name}</code>, <code>{ticket_number}</code>, <code>{subject}</code>, <code>{customer_name}</code>, <code>{location}</code>, <code>{priority}</code>, <code>{description}</code>, <code>{url}</code>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Kirim Ke Teknisi Ditugaskan') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($ticket->customer && $isOnuProvisioningTicket && !in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete') || $ticket->technicians->contains('id', Auth::id())))
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCustomerModalLabel">{{ __('Ubah Pelanggan') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCustomerForm" action="{{ route('tickets.updateCustomer', $ticket) }}" method="POST">
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
                            <label for="cust_onu_serial" class="form-label">{{ __('ONU Serial') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="cust_onu_serial" name="onu_serial" required value="{{ $ticket->customer->onu_serial }}">
                                <button class="btn btn-outline-primary" type="button" id="startOnuQrScan">
                                    <i class="fa-solid fa-qrcode me-1"></i>{{ __('Scan QR') }}
                                </button>
                            </div>
                            <div id="onuQrScanStatus" class="small text-muted mt-2"></div>
                            <div id="onuQrScannerWrapper" class="mt-2 d-none">
                                <div id="ticket-onu-qr-reader" class="ticket-qr-reader" style="width: 100%; max-width: 520px;"></div>
                                <button class="btn btn-sm btn-outline-danger mt-2" type="button" id="stopOnuQrScan">
                                    <i class="fa-solid fa-stop me-1"></i>{{ __('Hentikan Scan') }}
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="cust_wan_mac" class="form-label">{{ __('WAN MAC') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="cust_wan_mac" name="wan_mac" required value="{{ $ticket->customer->wan_mac }}" placeholder="{{ __('AA:BB:CC:DD:EE:FF') }}">
                                <button class="btn btn-outline-primary" type="button" id="startCustMacQrScan">
                                    <i class="fa-solid fa-qrcode me-1"></i>{{ __('Scan QR MAC') }}
                                </button>
                            </div>
                            <div id="custMacQrScanStatus" class="small text-muted mt-2"></div>
                            <div id="custMacQrScannerWrapper" class="mt-2 d-none">
                                <div id="ticket-cust-mac-qr-reader" class="ticket-qr-reader" style="width: 100%; max-width: 520px;"></div>
                                <button class="btn btn-sm btn-outline-danger mt-2" type="button" id="stopCustMacQrScan">
                                    <i class="fa-solid fa-stop me-1"></i>{{ __('Hentikan Scan') }}
                                </button>
                            </div>
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
                            <button class="btn btn-outline-primary" type="button" id="startQrScan">
                                <i class="fa-solid fa-qrcode me-1"></i>{{ __('Scan QR') }}
                            </button>
                        </div>
                        <div class="form-text">{{ __('Klik ikon bidik untuk mengambil lokasi saat ini atau gunakan Scan QR.') }}</div>
                        <div id="qrScanStatus" class="small text-muted mt-2"></div>
                    </div>
                    <div id="qrScannerWrapper" class="mt-2 d-none">
                        <div id="ticket-qr-reader" class="ticket-qr-reader" style="width: 100%; max-width: 520px;"></div>
                        <button class="btn btn-sm btn-outline-danger mt-2" type="button" id="stopQrScan">
                            <i class="fa-solid fa-stop me-1"></i>{{ __('Hentikan Scan') }}
                        </button>
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

<div class="modal fade" id="ticketSopModal" tabindex="-1" aria-labelledby="ticketSopModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketSopModalLabel">{{ __('Standard Operating Procedure (SOP) Teknisi Jaringan WiFi') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Tutup') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary small">
                    {{ __('Dokumen SOP ini mencakup seluruh siklus kerja teknisi: persiapan, instalasi, pemeliharaan, troubleshooting, hingga penutupan tiket.') }}
                </div>

                <h6 class="fw-bold">{{ __('1. Persiapan Sebelum Berangkat') }}</h6>
                <ul class="small">
                    <li>{{ __('Pelajari detail tiket: jenis gangguan, alamat, kontak pelanggan, riwayat tiket, ODP, dan catatan teknisi sebelumnya.') }}</li>
                    <li>{{ __('Hubungi pelanggan untuk konfirmasi jadwal kunjungan dan pastikan akses lokasi tersedia.') }}</li>
                    <li>{{ __('Siapkan peralatan kerja: tangga, toolkit, tang crimping, LAN tester, power meter, OTDR (jika ada), adaptor, konektor, patchcord, ONU/router cadangan.') }}</li>
                    <li>{{ __('Gunakan APD standar: helm, rompi, sarung tangan, sepatu safety, dan perlengkapan kerja di ketinggian bila diperlukan.') }}</li>
                </ul>

                <h6 class="fw-bold">{{ __('2. SOP Instalasi / Aktivasi') }}</h6>
                <ul class="small">
                    <li>{{ __('Lakukan survey titik penarikan kabel paling aman, rapi, dan minim risiko gangguan.') }}</li>
                    <li>{{ __('Pasang kabel sesuai standar: jalur rapi, tidak tertekuk tajam, dan diberi pengikat/label.') }}</li>
                    <li>{{ __('Instal ONU/router, konfigurasi SSID dan password, lalu lakukan uji koneksi internet.') }}</li>
                    <li>{{ __('Edukasi pelanggan tentang cara penggunaan dasar, restart perangkat, dan kontak bantuan.') }}</li>
                    <li>{{ __('Dokumentasikan foto instalasi sebelum/akhir untuk bukti pekerjaan.') }}</li>
                </ul>

                <h6 class="fw-bold">{{ __('3. SOP Pemeliharaan Berkala') }}</h6>
                <ul class="small">
                    <li>{{ __('Periksa kualitas sinyal, performa koneksi, dan kondisi fisik perangkat serta kabel.') }}</li>
                    <li>{{ __('Bersihkan perangkat dari debu/kelembapan dan rapikan kembali instalasi jika diperlukan.') }}</li>
                    <li>{{ __('Lakukan update konfigurasi jika ada perubahan kebijakan jaringan atau keamanan.') }}</li>
                    <li>{{ __('Catat hasil pemeriksaan secara detail di log tiket untuk histori perawatan.') }}</li>
                </ul>

                <h6 class="fw-bold">{{ __('4. SOP Penanganan Gangguan (Troubleshooting)') }}</h6>
                <ul class="small">
                    <li>{{ __('Identifikasi gejala: LOS merah, internet lambat, sering putus, tidak bisa autentikasi, atau perangkat mati.') }}</li>
                    <li>{{ __('Cek berurutan dari sisi pelanggan ke jaringan inti: listrik/adaptor -> ONU/router -> kabel dropcore -> ODP -> uplink.') }}</li>
                    <li>{{ __('Lakukan tindakan korektif: ganti adaptor/perangkat rusak, re-terminasi konektor, perbaikan jalur kabel, reconfig PPPoE/SSID.') }}</li>
                    <li>{{ __('Uji hasil perbaikan: ping, browsing, speed test seperlunya, dan verifikasi stabilitas minimal beberapa menit.') }}</li>
                    <li>{{ __('Jika eskalasi diperlukan, sertakan bukti teknis jelas: foto, nilai pengukuran, gejala, serta tindakan yang sudah dicoba.') }}</li>
                </ul>

                <h6 class="fw-bold">{{ __('5. Penutupan Tiket') }}</h6>
                <ul class="small">
                    <li>{{ __('Pastikan layanan sudah normal dan pelanggan menyatakan masalah selesai.') }}</li>
                    <li>{{ __('Unggah foto sesudah perbaikan (wajib) dan foto sebelum (jika ada).') }}</li>
                    <li>{{ __('Isi catatan penyelesaian yang mencakup akar masalah, tindakan, material yang dipakai, dan hasil akhir.') }}</li>
                    <li>{{ __('Klik "Selesaikan Tiket" hanya setelah semua data pendukung lengkap.') }}</li>
                </ul>

                <h6 class="fw-bold">{{ __('6. Standar Keselamatan Kerja') }}</h6>
                <ul class="small mb-0">
                    <li>{{ __('Dilarang bekerja di titik berbahaya tanpa APD dan tanpa pengamanan area kerja.') }}</li>
                    <li>{{ __('Utamakan keselamatan manusia dibanding kecepatan penyelesaian pekerjaan.') }}</li>
                    <li>{{ __('Laporkan segera jika ada potensi bahaya listrik, ketinggian, atau kondisi lingkungan tidak aman.') }}</li>
                </ul>
            </div>
            <div class="modal-footer">
                <a href="{{ route('tickets.sop.pdf', $ticket) }}" class="btn btn-success">
                    <i class="fa-solid fa-download me-1"></i> {{ __('Unduh PDF') }}
                </a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
            </div>
        </div>
    </div>
</div>



@push('styles')
<style>
    .ticket-qr-reader video {
        width: 100% !important;
        height: auto !important;
        object-fit: cover;
        border-radius: 0.5rem;
        /* Sedikit tingkatkan kontras agar pola QR/barcode lebih mudah dikenali kamera */
        filter: brightness(1.18) contrast(1.15) saturate(1.08);
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const getCurrentLocationBtn = document.getElementById('getCurrentLocation');
        const locationInput = document.getElementById('location');
        const startQrScanBtn = document.getElementById('startQrScan');
        const stopQrScanBtn = document.getElementById('stopQrScan');
        const qrScannerWrapper = document.getElementById('qrScannerWrapper');
        const qrScanStatus = document.getElementById('qrScanStatus');
        const qrReaderElementId = 'ticket-qr-reader';
        const onuSerialInput = document.getElementById('cust_onu_serial');
        const custWanMacInput = document.getElementById('cust_wan_mac');
        const startOnuQrScanBtn = document.getElementById('startOnuQrScan');
        const stopOnuQrScanBtn = document.getElementById('stopOnuQrScan');
        const onuQrScannerWrapper = document.getElementById('onuQrScannerWrapper');
        const onuQrScanStatus = document.getElementById('onuQrScanStatus');
        const onuQrReaderElementId = 'ticket-onu-qr-reader';
        const startCustMacQrScanBtn = document.getElementById('startCustMacQrScan');
        const stopCustMacQrScanBtn = document.getElementById('stopCustMacQrScan');
        const custMacQrScannerWrapper = document.getElementById('custMacQrScannerWrapper');
        const custMacQrScanStatus = document.getElementById('custMacQrScanStatus');
        const custMacQrReaderElementId = 'ticket-cust-mac-qr-reader';
        const completionOnuInput = document.getElementById('completion_onu_serial');
        const completionWanMacInput = document.getElementById('completion_wan_mac');
        const startCompleteOnuQrScanBtn = document.getElementById('startCompleteOnuQrScan');
        const stopCompleteOnuQrScanBtn = document.getElementById('stopCompleteOnuQrScan');
        const completionOnuQrScannerWrapper = document.getElementById('completionOnuQrScannerWrapper');
        const completionOnuQrScanStatus = document.getElementById('completionOnuQrScanStatus');
        const completionOnuQrReaderElementId = 'completion-onu-qr-reader';
        const startCompleteMacQrScanBtn = document.getElementById('startCompleteMacQrScan');
        const stopCompleteMacQrScanBtn = document.getElementById('stopCompleteMacQrScan');
        const completionMacQrScannerWrapper = document.getElementById('completionMacQrScannerWrapper');
        const completionMacQrScanStatus = document.getElementById('completionMacQrScanStatus');
        const completionMacQrReaderElementId = 'completion-mac-qr-reader';
        let qrScanner = null;
        let isQrScannerRunning = false;
        let onuQrScanner = null;
        let isOnuQrScannerRunning = false;
        let custMacQrScanner = null;
        let isCustMacQrScannerRunning = false;
        let completionOnuQrScanner = null;
        let isCompletionOnuQrScannerRunning = false;
        let completionMacQrScanner = null;
        let isCompletionMacQrScannerRunning = false;
        const scannerFormats = (typeof Html5QrcodeSupportedFormats !== 'undefined') ? [
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
            Html5QrcodeSupportedFormats.ITF,
            Html5QrcodeSupportedFormats.DATA_MATRIX,
            Html5QrcodeSupportedFormats.AZTEC,
            Html5QrcodeSupportedFormats.PDF_417
        ] : [];
        const buildScannerConfig = (readerElementId) => {
            const readerElement = document.getElementById(readerElementId);
            const elementWidth = readerElement ? readerElement.clientWidth : 360;
            const qrboxSize = Math.max(220, Math.min(360, Math.floor(elementWidth * 0.82)));
            const config = {
                fps: 14,
                qrbox: { width: qrboxSize, height: qrboxSize },
                aspectRatio: 1.7778,
                disableFlip: false,
            };
            if (scannerFormats.length > 0) {
                config.formatsToSupport = scannerFormats;
            }
            return config;
        };

        const getPreferredCameraList = async () => {
            if (typeof Html5Qrcode === 'undefined' || typeof Html5Qrcode.getCameras !== 'function') {
                return [];
            }

            const cameras = await Html5Qrcode.getCameras();
            if (!Array.isArray(cameras) || cameras.length === 0) {
                return [];
            }

            const backCameraKeywords = /(back|rear|environment|belakang|traseira|trasera)/i;
            const preferredBack = cameras.find((camera) => backCameraKeywords.test(String(camera.label || '')));
            if (!preferredBack) {
                return cameras;
            }

            return [preferredBack, ...cameras.filter((camera) => camera.id !== preferredBack.id)];
        };

        const enhanceReaderVideo = (readerElementId) => {
            const root = document.getElementById(readerElementId);
            if (!root) return;
            const video = root.querySelector('video');
            if (!video) return;
            video.setAttribute('playsinline', 'true');
            video.setAttribute('autoplay', 'true');
            video.setAttribute('muted', 'true');
            video.style.filter = 'brightness(1.18) contrast(1.15) saturate(1.08)';
        };

        const applyTrackConstraints = async (track, constraints) => {
            if (!track || typeof track.applyConstraints !== 'function') {
                return false;
            }
            try {
                await track.applyConstraints(constraints);
                return true;
            } catch (error) {
                return false;
            }
        };

        const optimizeScannerTrack = async (scanner) => {
            const runningTrack = scanner?.getRunningTrack?.() || null;
            if (!runningTrack) {
                return;
            }

            const optimizations = [
                { advanced: [{ focusMode: 'continuous' }] },
                { advanced: [{ exposureMode: 'continuous' }] },
                { advanced: [{ whiteBalanceMode: 'continuous' }] },
                { advanced: [{ brightness: 0.2 }] },
                { advanced: [{ contrast: 0.3 }] }
            ];

            for (const constraint of optimizations) {
                await applyTrackConstraints(runningTrack, constraint);
            }

            let capabilities = {};
            if (typeof runningTrack.getCapabilities === 'function') {
                capabilities = runningTrack.getCapabilities() || {};
            }
            if (capabilities.torch) {
                await applyTrackConstraints(runningTrack, { advanced: [{ torch: true }] });
            }
        };

        const startScannerWithBestCamera = async (scanner, readerElementId, onDecodeSuccess, onDecodeError) => {
            const config = buildScannerConfig(readerElementId);
            const preferredCameras = await getPreferredCameraList();

            for (const camera of preferredCameras) {
                try {
                    await scanner.start(camera.id, config, onDecodeSuccess, onDecodeError);
                    setTimeout(() => enhanceReaderVideo(readerElementId), 250);
                    setTimeout(() => {
                        optimizeScannerTrack(scanner);
                    }, 320);
                    return;
                } catch (error) {
                    // Coba kamera berikutnya.
                }
            }

            await scanner.start(
                {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                },
                config,
                onDecodeSuccess,
                onDecodeError
            );
            setTimeout(() => enhanceReaderVideo(readerElementId), 250);
            setTimeout(() => {
                optimizeScannerTrack(scanner);
            }, 320);
        };

        const setQrStatus = (message, type = 'muted') => {
            if (!qrScanStatus) return;
            qrScanStatus.classList.remove('text-muted', 'text-danger', 'text-success');
            if (type === 'error') {
                qrScanStatus.classList.add('text-danger');
            } else if (type === 'success') {
                qrScanStatus.classList.add('text-success');
            } else {
                qrScanStatus.classList.add('text-muted');
            }
            qrScanStatus.textContent = message;
        };

        const parseCoordinatesFromQr = (rawText) => {
            if (!rawText) return null;
            const text = String(rawText).trim();

            const strictPair = text.match(/(-?\d{1,3}(?:\.\d+)?)[,\s]+(-?\d{1,3}(?:\.\d+)?)/);
            if (strictPair) {
                const lat = Number(strictPair[1]);
                const lng = Number(strictPair[2]);
                if (!Number.isNaN(lat) && !Number.isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                    return `${lat}, ${lng}`;
                }
            }

            try {
                const url = new URL(text);
                const mapQuery = url.searchParams.get('query') || url.searchParams.get('q');
                if (mapQuery) {
                    const queryPair = mapQuery.match(/(-?\d{1,3}(?:\.\d+)?)[,\s]+(-?\d{1,3}(?:\.\d+)?)/);
                    if (queryPair) {
                        return `${Number(queryPair[1])}, ${Number(queryPair[2])}`;
                    }
                }
                const atMarker = url.pathname.match(/@(-?\d{1,3}(?:\.\d+)?),(-?\d{1,3}(?:\.\d+)?)/);
                if (atMarker) {
                    return `${Number(atMarker[1])}, ${Number(atMarker[2])}`;
                }
            } catch (e) {
                // Bukan URL, lanjut cek format lain.
            }

            const geoPair = text.match(/^geo:(-?\d{1,3}(?:\.\d+)?),(-?\d{1,3}(?:\.\d+)?)/i);
            if (geoPair) {
                return `${Number(geoPair[1])}, ${Number(geoPair[2])}`;
            }

            return null;
        };

        const setOnuQrStatus = (message, type = 'muted') => {
            if (!onuQrScanStatus) return;
            onuQrScanStatus.classList.remove('text-muted', 'text-danger', 'text-success');
            if (type === 'error') {
                onuQrScanStatus.classList.add('text-danger');
            } else if (type === 'success') {
                onuQrScanStatus.classList.add('text-success');
            } else {
                onuQrScanStatus.classList.add('text-muted');
            }
            onuQrScanStatus.textContent = message;
        };

        const parseOnuSerialFromQr = (rawText) => {
            if (!rawText) return null;
            const text = String(rawText).trim();
            const firstLine = text.split(/\r?\n/).map((line) => line.trim()).find((line) => line !== '') || '';
            const source = firstLine || text;

            const taggedMatch = source.match(/(?:SN|SERIAL|ONU)[\s:=-]*([A-Za-z0-9._:-]+)/i);
            if (taggedMatch && taggedMatch[1]) {
                return taggedMatch[1].trim();
            }

            try {
                const url = new URL(source);
                const serialParam = url.searchParams.get('serial') || url.searchParams.get('onu_serial') || url.searchParams.get('sn');
                if (serialParam) {
                    return serialParam.trim();
                }
                const pathnamePart = url.pathname.split('/').filter(Boolean).pop();
                if (pathnamePart) {
                    return pathnamePart.trim();
                }
            } catch (e) {
                // Bukan URL, lanjutkan sebagai teks biasa.
            }

            return source.trim() !== '' ? source.trim() : null;
        };

        const parseWanMacFromQr = (rawText) => {
            if (!rawText) return null;
            const text = String(rawText).trim();

            const toMacWithColon = (macRaw) => {
                const normalized = macRaw.replace(/[^0-9A-Fa-f]/g, '');
                if (normalized.length !== 12) return null;
                return normalized.match(/.{1,2}/g).join(':').toUpperCase();
            };

            const directMatch = text.match(/([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}/);
            if (directMatch && directMatch[0]) {
                return toMacWithColon(directMatch[0]);
            }

            const compactMatch = text.match(/\b[0-9A-Fa-f]{12}\b/);
            if (compactMatch && compactMatch[0]) {
                return toMacWithColon(compactMatch[0]);
            }

            try {
                const url = new URL(text);
                const macParam = url.searchParams.get('mac')
                    || url.searchParams.get('wan_mac')
                    || url.searchParams.get('mac_address');
                if (macParam) {
                    return toMacWithColon(macParam);
                }
            } catch (e) {
                // Bukan URL, lanjutkan sebagai teks biasa.
            }

            return null;
        };

        const normalizeMacInputValue = (value) => {
            if (!value) return '';
            const normalized = String(value).replace(/[^0-9A-Fa-f]/g, '');
            if (normalized.length !== 12) return String(value).trim().toUpperCase();
            return normalized.match(/.{1,2}/g).join(':').toUpperCase();
        };

        const normalizeMacAsTyping = (value) => {
            if (!value) return '';
            const hexOnly = String(value).replace(/[^0-9A-Fa-f]/g, '').toUpperCase().slice(0, 12);
            return hexOnly.match(/.{1,2}/g)?.join(':') ?? hexOnly;
        };

        const stopQrScanner = async () => {
            if (!qrScanner || !isQrScannerRunning) return;
            try {
                await qrScanner.stop();
                await qrScanner.clear();
            } catch (error) {
                console.warn('Stop QR scanner error:', error);
            } finally {
                isQrScannerRunning = false;
                if (qrScannerWrapper) qrScannerWrapper.classList.add('d-none');
                if (startQrScanBtn) startQrScanBtn.disabled = false;
            }
        };

        const stopOnuQrScanner = async () => {
            if (!onuQrScanner || !isOnuQrScannerRunning) return;
            try {
                await onuQrScanner.stop();
                await onuQrScanner.clear();
            } catch (error) {
                console.warn('Stop ONU QR scanner error:', error);
            } finally {
                isOnuQrScannerRunning = false;
                if (onuQrScannerWrapper) onuQrScannerWrapper.classList.add('d-none');
                if (startOnuQrScanBtn) startOnuQrScanBtn.disabled = false;
            }
        };

        const setCustMacQrStatus = (message, type = 'muted') => {
            if (!custMacQrScanStatus) return;
            custMacQrScanStatus.classList.remove('text-muted', 'text-danger', 'text-success');
            if (type === 'error') {
                custMacQrScanStatus.classList.add('text-danger');
            } else if (type === 'success') {
                custMacQrScanStatus.classList.add('text-success');
            } else {
                custMacQrScanStatus.classList.add('text-muted');
            }
            custMacQrScanStatus.textContent = message;
        };

        const stopCustMacQrScanner = async () => {
            if (!custMacQrScanner || !isCustMacQrScannerRunning) return;
            try {
                await custMacQrScanner.stop();
                await custMacQrScanner.clear();
            } catch (error) {
                console.warn('Stop customer MAC scanner error:', error);
            } finally {
                isCustMacQrScannerRunning = false;
                if (custMacQrScannerWrapper) custMacQrScannerWrapper.classList.add('d-none');
                if (startCustMacQrScanBtn) startCustMacQrScanBtn.disabled = false;
            }
        };

        const setCompletionOnuQrStatus = (message, type = 'muted') => {
            if (!completionOnuQrScanStatus) return;
            completionOnuQrScanStatus.classList.remove('text-muted', 'text-danger', 'text-success');
            if (type === 'error') {
                completionOnuQrScanStatus.classList.add('text-danger');
            } else if (type === 'success') {
                completionOnuQrScanStatus.classList.add('text-success');
            } else {
                completionOnuQrScanStatus.classList.add('text-muted');
            }
            completionOnuQrScanStatus.textContent = message;
        };

        const setCompletionMacQrStatus = (message, type = 'muted') => {
            if (!completionMacQrScanStatus) return;
            completionMacQrScanStatus.classList.remove('text-muted', 'text-danger', 'text-success');
            if (type === 'error') {
                completionMacQrScanStatus.classList.add('text-danger');
            } else if (type === 'success') {
                completionMacQrScanStatus.classList.add('text-success');
            } else {
                completionMacQrScanStatus.classList.add('text-muted');
            }
            completionMacQrScanStatus.textContent = message;
        };

        const stopCompletionOnuQrScanner = async () => {
            if (!completionOnuQrScanner || !isCompletionOnuQrScannerRunning) return;
            try {
                await completionOnuQrScanner.stop();
                await completionOnuQrScanner.clear();
            } catch (error) {
                console.warn('Stop completion ONU scanner error:', error);
            } finally {
                isCompletionOnuQrScannerRunning = false;
                if (completionOnuQrScannerWrapper) completionOnuQrScannerWrapper.classList.add('d-none');
                if (startCompleteOnuQrScanBtn) startCompleteOnuQrScanBtn.disabled = false;
            }
        };

        const stopCompletionMacQrScanner = async () => {
            if (!completionMacQrScanner || !isCompletionMacQrScannerRunning) return;
            try {
                await completionMacQrScanner.stop();
                await completionMacQrScanner.clear();
            } catch (error) {
                console.warn('Stop completion MAC scanner error:', error);
            } finally {
                isCompletionMacQrScannerRunning = false;
                if (completionMacQrScannerWrapper) completionMacQrScannerWrapper.classList.add('d-none');
                if (startCompleteMacQrScanBtn) startCompleteMacQrScanBtn.disabled = false;
            }
        };

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

        if (startQrScanBtn && locationInput) {
            startQrScanBtn.addEventListener('click', async function() {
                if (typeof Html5Qrcode === 'undefined') {
                    setQrStatus('{{ __('Library QR scanner tidak tersedia.') }}', 'error');
                    return;
                }
                if (isQrScannerRunning) {
                    setQrStatus('{{ __('Scanner sudah aktif.') }}', 'muted');
                    return;
                }

                try {
                    await stopOnuQrScanner();
                    await stopCustMacQrScanner();
                    await stopCompletionOnuQrScanner();
                    await stopCompletionMacQrScanner();
                    qrScanner = qrScanner || new Html5Qrcode(qrReaderElementId);
                    if (qrScannerWrapper) qrScannerWrapper.classList.remove('d-none');
                    setQrStatus('{{ __('Arahkan kamera ke QR berisi koordinat.') }}', 'muted');
                    startQrScanBtn.disabled = true;

                    await startScannerWithBestCamera(
                        qrScanner,
                        qrReaderElementId,
                        async (decodedText) => {
                            const coords = parseCoordinatesFromQr(decodedText);
                            if (!coords) {
                                setQrStatus('{{ __('QR terdeteksi, tetapi format koordinat tidak dikenali.') }}', 'error');
                                return;
                            }
                            locationInput.value = coords;
                            setQrStatus('{{ __('Koordinat berhasil dibaca dari QR.') }}', 'success');
                            await stopQrScanner();
                        },
                        () => {
                            // Diamkan callback error per frame agar tidak spam.
                        }
                    );
                    isQrScannerRunning = true;
                } catch (error) {
                    setQrStatus('{{ __('Gagal mengakses kamera. Pastikan izin kamera diberikan.') }}', 'error');
                    if (startQrScanBtn) startQrScanBtn.disabled = false;
                    console.error('Start QR scanner error:', error);
                }
            });
        }

        if (stopQrScanBtn) {
            stopQrScanBtn.addEventListener('click', async function() {
                await stopQrScanner();
                setQrStatus('{{ __('Scanner dihentikan.') }}', 'muted');
            });
        }

        if (startOnuQrScanBtn && onuSerialInput) {
            startOnuQrScanBtn.addEventListener('click', async function() {
                if (typeof Html5Qrcode === 'undefined') {
                    setOnuQrStatus('{{ __('Library QR scanner tidak tersedia.') }}', 'error');
                    return;
                }
                if (isOnuQrScannerRunning) {
                    setOnuQrStatus('{{ __('Scanner ONU sudah aktif.') }}', 'muted');
                    return;
                }

                try {
                    await stopQrScanner();
                    await stopCustMacQrScanner();
                    await stopCompletionOnuQrScanner();
                    await stopCompletionMacQrScanner();
                    onuQrScanner = onuQrScanner || new Html5Qrcode(onuQrReaderElementId);
                    if (onuQrScannerWrapper) onuQrScannerWrapper.classList.remove('d-none');
                    setOnuQrStatus('{{ __('Arahkan kamera ke QR label ONU.') }}', 'muted');
                    startOnuQrScanBtn.disabled = true;

                    await startScannerWithBestCamera(
                        onuQrScanner,
                        onuQrReaderElementId,
                        async (decodedText) => {
                            const serial = parseOnuSerialFromQr(decodedText);
                            if (!serial) {
                                setOnuQrStatus('{{ __('QR terdeteksi, tetapi serial ONU tidak terbaca.') }}', 'error');
                                return;
                            }
                            onuSerialInput.value = serial;
                            setOnuQrStatus('{{ __('ONU Serial berhasil dibaca dari QR.') }}', 'success');
                            if (custWanMacInput && !custWanMacInput.value) {
                                custWanMacInput.focus();
                            }
                            await stopOnuQrScanner();
                        },
                        () => {
                            // Diamkan callback error per frame agar tidak spam.
                        }
                    );
                    isOnuQrScannerRunning = true;
                } catch (error) {
                    setOnuQrStatus('{{ __('Gagal mengakses kamera. Pastikan izin kamera diberikan.') }}', 'error');
                    if (startOnuQrScanBtn) startOnuQrScanBtn.disabled = false;
                    console.error('Start ONU QR scanner error:', error);
                }
            });
        }

        if (startCustMacQrScanBtn && custWanMacInput) {
            startCustMacQrScanBtn.addEventListener('click', async function() {
                if (typeof Html5Qrcode === 'undefined') {
                    setCustMacQrStatus('{{ __('Library QR scanner tidak tersedia.') }}', 'error');
                    return;
                }
                if (isCustMacQrScannerRunning) {
                    setCustMacQrStatus('{{ __('Scanner MAC sudah aktif.') }}', 'muted');
                    return;
                }

                try {
                    await stopQrScanner();
                    await stopOnuQrScanner();
                    await stopCompletionOnuQrScanner();
                    await stopCompletionMacQrScanner();
                    custMacQrScanner = custMacQrScanner || new Html5Qrcode(custMacQrReaderElementId);
                    if (custMacQrScannerWrapper) custMacQrScannerWrapper.classList.remove('d-none');
                    setCustMacQrStatus('{{ __('Arahkan kamera ke QR MAC Address.') }}', 'muted');
                    startCustMacQrScanBtn.disabled = true;

                    await startScannerWithBestCamera(
                        custMacQrScanner,
                        custMacQrReaderElementId,
                        async (decodedText) => {
                            const mac = parseWanMacFromQr(decodedText);
                            if (!mac) {
                                setCustMacQrStatus('{{ __('QR terdeteksi, tetapi MAC Address tidak terbaca.') }}', 'error');
                                return;
                            }
                            custWanMacInput.value = normalizeMacInputValue(mac);
                            setCustMacQrStatus('{{ __('MAC Address berhasil dibaca dari QR.') }}', 'success');
                            await stopCustMacQrScanner();
                        },
                        () => {
                            // Diamkan callback error per frame agar tidak spam.
                        }
                    );
                    isCustMacQrScannerRunning = true;
                } catch (error) {
                    setCustMacQrStatus('{{ __('Gagal mengakses kamera. Pastikan izin kamera diberikan.') }}', 'error');
                    if (startCustMacQrScanBtn) startCustMacQrScanBtn.disabled = false;
                    console.error('Start customer MAC scanner error:', error);
                }
            });
        }

        if (stopCustMacQrScanBtn) {
            stopCustMacQrScanBtn.addEventListener('click', async function() {
                await stopCustMacQrScanner();
                setCustMacQrStatus('{{ __('Scanner MAC dihentikan.') }}', 'muted');
            });
        }

        if (startCompleteOnuQrScanBtn && completionOnuInput) {
            startCompleteOnuQrScanBtn.addEventListener('click', async function() {
                if (typeof Html5Qrcode === 'undefined') {
                    setCompletionOnuQrStatus('{{ __('Library QR scanner tidak tersedia.') }}', 'error');
                    return;
                }
                if (isCompletionOnuQrScannerRunning) {
                    setCompletionOnuQrStatus('{{ __('Scanner ONU SN sudah aktif.') }}', 'muted');
                    return;
                }

                try {
                    await stopQrScanner();
                    await stopOnuQrScanner();
                    await stopCompletionMacQrScanner();
                    completionOnuQrScanner = completionOnuQrScanner || new Html5Qrcode(completionOnuQrReaderElementId);
                    if (completionOnuQrScannerWrapper) completionOnuQrScannerWrapper.classList.remove('d-none');
                    setCompletionOnuQrStatus('{{ __('Arahkan kamera ke QR ONU SN.') }}', 'muted');
                    startCompleteOnuQrScanBtn.disabled = true;

                    await startScannerWithBestCamera(
                        completionOnuQrScanner,
                        completionOnuQrReaderElementId,
                        async (decodedText) => {
                            const serial = parseOnuSerialFromQr(decodedText);
                            if (!serial) {
                                setCompletionOnuQrStatus('{{ __('QR terdeteksi, tetapi ONU SN tidak terbaca.') }}', 'error');
                                return;
                            }
                            completionOnuInput.value = serial;
                            setCompletionOnuQrStatus('{{ __('ONU SN berhasil dibaca dari QR.') }}', 'success');
                            if (completionWanMacInput && !completionWanMacInput.value) {
                                completionWanMacInput.focus();
                            }
                            await stopCompletionOnuQrScanner();
                        },
                        () => {
                            // Diamkan callback error per frame agar tidak spam.
                        }
                    );
                    isCompletionOnuQrScannerRunning = true;
                } catch (error) {
                    setCompletionOnuQrStatus('{{ __('Gagal mengakses kamera. Pastikan izin kamera diberikan.') }}', 'error');
                    if (startCompleteOnuQrScanBtn) startCompleteOnuQrScanBtn.disabled = false;
                    console.error('Start completion ONU scanner error:', error);
                }
            });
        }

        if (stopCompleteOnuQrScanBtn) {
            stopCompleteOnuQrScanBtn.addEventListener('click', async function() {
                await stopCompletionOnuQrScanner();
                setCompletionOnuQrStatus('{{ __('Scanner ONU SN dihentikan.') }}', 'muted');
            });
        }

        if (startCompleteMacQrScanBtn && completionWanMacInput) {
            startCompleteMacQrScanBtn.addEventListener('click', async function() {
                if (typeof Html5Qrcode === 'undefined') {
                    setCompletionMacQrStatus('{{ __('Library QR scanner tidak tersedia.') }}', 'error');
                    return;
                }
                if (isCompletionMacQrScannerRunning) {
                    setCompletionMacQrStatus('{{ __('Scanner MAC sudah aktif.') }}', 'muted');
                    return;
                }

                try {
                    await stopQrScanner();
                    await stopOnuQrScanner();
                    await stopCompletionOnuQrScanner();
                    completionMacQrScanner = completionMacQrScanner || new Html5Qrcode(completionMacQrReaderElementId);
                    if (completionMacQrScannerWrapper) completionMacQrScannerWrapper.classList.remove('d-none');
                    setCompletionMacQrStatus('{{ __('Arahkan kamera ke QR MAC Address.') }}', 'muted');
                    startCompleteMacQrScanBtn.disabled = true;

                    await startScannerWithBestCamera(
                        completionMacQrScanner,
                        completionMacQrReaderElementId,
                        async (decodedText) => {
                            const mac = parseWanMacFromQr(decodedText);
                            if (!mac) {
                                setCompletionMacQrStatus('{{ __('QR terdeteksi, tetapi MAC Address tidak terbaca.') }}', 'error');
                                return;
                            }
                            completionWanMacInput.value = normalizeMacInputValue(mac);
                            setCompletionMacQrStatus('{{ __('MAC Address berhasil dibaca dari QR.') }}', 'success');
                            await stopCompletionMacQrScanner();
                        },
                        () => {
                            // Diamkan callback error per frame agar tidak spam.
                        }
                    );
                    isCompletionMacQrScannerRunning = true;
                } catch (error) {
                    setCompletionMacQrStatus('{{ __('Gagal mengakses kamera. Pastikan izin kamera diberikan.') }}', 'error');
                    if (startCompleteMacQrScanBtn) startCompleteMacQrScanBtn.disabled = false;
                    console.error('Start completion MAC scanner error:', error);
                }
            });
        }

        if (stopCompleteMacQrScanBtn) {
            stopCompleteMacQrScanBtn.addEventListener('click', async function() {
                await stopCompletionMacQrScanner();
                setCompletionMacQrStatus('{{ __('Scanner MAC dihentikan.') }}', 'muted');
            });
        }

        const bindMacAutoFormat = (input) => {
            if (!input) return;
            input.setAttribute('autocomplete', 'off');
            input.addEventListener('input', function() {
                input.value = normalizeMacAsTyping(input.value);
            });
            input.addEventListener('blur', function() {
                input.value = normalizeMacInputValue(input.value);
            });
            input.addEventListener('paste', function() {
                setTimeout(function() {
                    input.value = normalizeMacAsTyping(input.value);
                }, 0);
            });
        };

        bindMacAutoFormat(custWanMacInput);
        bindMacAutoFormat(completionWanMacInput);

        const editCustomerForm = document.getElementById('editCustomerForm');
        if (editCustomerForm && custWanMacInput) {
            editCustomerForm.addEventListener('submit', function() {
                custWanMacInput.value = normalizeMacInputValue(custWanMacInput.value);
            });
        }

        const completeTicketForm = document.getElementById('completeTicketForm');
        if (completeTicketForm && completionWanMacInput) {
            completeTicketForm.addEventListener('submit', function() {
                completionWanMacInput.value = normalizeMacInputValue(completionWanMacInput.value);
            });
        }

        if (stopOnuQrScanBtn) {
            stopOnuQrScanBtn.addEventListener('click', async function() {
                await stopOnuQrScanner();
                setOnuQrStatus('{{ __('Scanner ONU dihentikan.') }}', 'muted');
            });
        }

        const editLocationModal = document.getElementById('editLocationModal');
        if (editLocationModal) {
            editLocationModal.addEventListener('hidden.bs.modal', function() {
                stopQrScanner();
            });
        }

        const editCustomerModal = document.getElementById('editCustomerModal');
        if (editCustomerModal) {
            editCustomerModal.addEventListener('hidden.bs.modal', function() {
                stopOnuQrScanner();
                stopCustMacQrScanner();
            });
        }
    });
</script>
@endpush
@endsection
