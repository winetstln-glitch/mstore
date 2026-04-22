@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">{{ __('Kehadiran Karyawan Harian') }}</h5>
                        <a href="{{ route('attendance.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-list-check me-1"></i> {{ __('Kembali ke Rekap') }}
                        </a>
                    </div>
                    
                    <form action="{{ route('attendance.daily') }}" method="GET" class="w-100">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-auto">
                                <label class="form-label small fw-bold">{{ __('Pilih Tanggal') }}</label>
                                <input type="date" name="date" value="{{ $date }}" class="form-control" onchange="this.form.submit()">
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-filter me-1"></i> {{ __('Terapkan') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body">
                <div class="alert alert-info py-2">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    {{ __('Menampilkan status kehadiran seluruh karyawan untuk tanggal:') }} <strong>{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</strong>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Karyawan') }}</th>
                                <th>{{ __('Peran') }}</th>
                                <th>{{ __('Jam Masuk') }}</th>
                                <th>{{ __('Jam Keluar') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Keterangan') }}</th>
                                <th class="text-end">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @foreach($users as $user)
                                @php 
                                    $attendance = $attendances->get($user->id);
                                @endphp
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($user->avatar)
                                                <img src="{{ Storage::url($user->avatar) }}" class="rounded-circle me-2" width="32" height="32" alt="">
                                            @else
                                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                    {{ substr($user->name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="fw-bold">{{ $user->name }}</div>
                                                <div class="small text-muted">{{ $user->username ?? $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $user->role->name ?? '-' }}</span>
                                    </td>
                                    <td>
                                        @if($attendance && $attendance->clock_in)
                                            <div class="fw-bold">{{ $attendance->clock_in->format('H:i') }}</div>
                                            <a href="https://maps.google.com/?q={{ $attendance->lat_clock_in }},{{ $attendance->lng_clock_in }}" target="_blank" class="text-decoration-none x-small">
                                                <i class="fa-solid fa-location-dot"></i> {{ __('Lokasi') }}
                                            </a>
                                        @else
                                            <span class="text-muted">--:--</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance && $attendance->clock_out)
                                            <div class="fw-bold">{{ $attendance->clock_out->format('H:i') }}</div>
                                            <a href="https://maps.google.com/?q={{ $attendance->lat_clock_out }},{{ $attendance->lng_clock_out }}" target="_blank" class="text-decoration-none x-small">
                                                <i class="fa-solid fa-location-dot"></i> {{ __('Lokasi') }}
                                            </a>
                                        @else
                                            <span class="text-muted">--:--</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance)
                                            @php
                                                $statusClass = match($attendance->status) {
                                                    'present' => 'bg-success',
                                                    'late' => 'bg-warning text-dark',
                                                    'leave', 'permit', 'sick' => 'bg-info',
                                                    'alpha' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $statusClass }}">
                                                {{ __(ucfirst($attendance->status)) }}
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                {{ __('Belum Absen') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $attendance->notes ?? '-' }}</small>
                                    </td>
                                    <td class="text-end">
                                        @if($attendance)
                                            <form method="POST" action="{{ route('attendance.notify', $attendance) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" title="{{ __('Kirim WhatsApp') }}" onclick="return confirm('Kirim notifikasi WhatsApp?')">
                                                    <i class="fa-brands fa-whatsapp"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .x-small { font-size: 0.75rem; }
</style>
@endpush
