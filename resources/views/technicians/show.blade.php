@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Detail Teknisi') }}: {{ $technician->name }}</h5>
                <div class="btn-group">
                    <a href="{{ route('technicians.edit', $technician) }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-edit me-1"></i> {{ __('Ubah') }}
                    </a>
                    <a href="{{ route('technicians.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali ke Daftar') }}
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-4">
                    <!-- Informasi pribadi -->
                    <div class="col-md-6">
                        <div class="card h-100 border ">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">{{ __('Informasi Pribadi') }}</h6>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">{{ __('Email') }}</small>
                                        <span class="fw-medium">{{ $technician->email }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">{{ __('Nomor HP') }}</small>
                                        <span class="fw-medium">{{ $technician->phone ?? '-' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">{{ __('ID Chat Telegram') }}</small>
                                        <span class="fw-medium">{{ $technician->telegram_chat_id ?? '-' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">{{ __('Status') }}</small>
                                        <span class="badge {{ $technician->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                                            {{ $technician->is_active ? __('Aktif') : __('Tidak Aktif') }}
                                        </span>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">{{ __('Bergabung') }}</small>
                                        <span class="fw-medium">{{ $technician->created_at->translatedFormat('d M Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan -->
                    <div class="col-md-6">
                        <div class="card h-100 border ">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">{{ __('Ringkasan Gaji & Kinerja') }}</h6>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="p-3 bg-body rounded border text-center">
                                            <small class="text-muted d-block mb-1">{{ __('Gaji Pokok Bulanan') }}</small>
                                            <span class="h5 fw-bold text-primary mb-0">{{ 'Rp ' . number_format(($technician->employee->monthly_salary ?? $technician->monthly_salary ?? 0), 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-body rounded border text-center">
                                            <small class="text-muted d-block mb-1">{{ __('Gaji Harian') }}</small>
                                            <span class="h5 fw-bold text-success mb-0">{{ 'Rp ' . number_format(($technician->employee->daily_salary ?? $technician->daily_salary ?? 0), 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-body rounded border text-center">
                                            <small class="text-muted d-block mb-1">{{ __('Total Tiket') }}</small>
                                            <span class="h5 fw-bold text-primary mb-0">{{ $technician->tickets()->count() }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-body rounded border text-center">
                                            <small class="text-muted d-block mb-1">{{ __('Total Instalasi') }}</small>
                                            <span class="h5 fw-bold text-success mb-0">{{ $technician->installations()->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Penugasan aktif -->
                <div class="mt-4">
                    <h5 class="fw-bold mb-3">{{ __('Penugasan Aktif') }}</h5>
                    
                    @if($technician->tickets()->whereIn('status', ['assigned', 'in_progress'])->count() > 0 || $technician->installations()->whereIn('status', ['assigned', 'survey', 'installation'])->count() > 0)
                        <div class="row g-4">
                            <!-- Tiket aktif -->
                            <div class="col-lg-6">
                                <div class="card border">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0 fw-bold">{{ __('Tiket Aktif') }}</h6>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        @forelse($technician->tickets()->whereIn('status', ['assigned', 'in_progress'])->get() as $ticket)
                                            <a href="{{ route('tickets.show', $ticket) }}" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                                    <h6 class="mb-0 text-primary text-truncate" style="max-width: 70%;">{{ $ticket->subject }}</h6>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle text-uppercase" style="font-size: 0.7rem;">
                                                        {{ $ticket->status }}
                                                    </span>
                                                </div>
                                                <small class="text-muted">{{ $ticket->customer->name ?? __('Pelanggan Tidak Diketahui') }}</small>
                                            </a>
                                        @empty
                                            <div class="list-group-item text-center text-muted py-3">{{ __('Tidak ada tiket aktif.') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <!-- Instalasi aktif -->
                            <div class="col-lg-6">
                                <div class="card border">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0 fw-bold">{{ __('Instalasi Aktif') }}</h6>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        @forelse($technician->installations()->whereIn('status', ['assigned', 'survey', 'installation'])->get() as $installation)
                                            <a href="{{ route('installations.show', $installation) }}" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                                    <h6 class="mb-0 text-primary text-truncate" style="max-width: 70%;">{{ $installation->customer->name ?? __('Pelanggan Tidak Diketahui') }}</h6>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle text-uppercase" style="font-size: 0.7rem;">
                                                        {{ $installation->status }}
                                                    </span>
                                                </div>
                                                <small class="text-muted">{{ $installation->service_package ?? '-' }}</small>
                                            </a>
                                        @empty
                                            <div class="list-group-item text-center text-muted py-3">{{ __('Tidak ada instalasi aktif.') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle me-1"></i> {{ __('Saat ini tidak ada penugasan aktif.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection