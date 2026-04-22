@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">{{ __('Detail Pelanggan') }}</h5>
                    <div class="btn-group flex-wrap gap-2">
                        @if($customer->onu_serial)
                            @can('customer.edit')
                            <a href="{{ route('customers.settings', $customer) }}" class="btn btn-info btn-sm text-white">
                                <i class="fa-solid fa-sliders"></i> {{ __('Pengaturan Perangkat') }}
                            </a>
                            <form action="{{ route('customers.notify_status', $customer) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa-brands fa-telegram"></i> {{ __('Kirim Status') }}
                                </button>
                            </form>
                            @endcan
                        @endif
                        @can('customer.edit')
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning btn-sm text-white">
                            <i class="fa-solid fa-pen-to-square"></i> {{ __('Ubah') }}
                        </a>
                        @endcan
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> {{ __('Kembali') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Informasi pribadi -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">{{ __('Informasi Pribadi') }}</h6>
                            <div class="p-3  rounded border dark:bg-dark dark:border-secondary">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">{{ __('Nama Lengkap') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->name }}</dd>

                                    <dt class="col-sm-4">{{ __('Status') }}</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge 
                                            {{ $customer->status === 'active' ? 'text-bg-success' : 
                                               ($customer->status === 'suspend' ? 'text-bg-warning' : 'text-bg-danger') }}">
                                            {{ $customer->status === 'active' ? __('Aktif') : ($customer->status === 'suspend' ? __('Suspend') : __('Berhenti')) }}
                                        </span>
                                    </dd>

                                    <dt class="col-sm-4">{{ __('Nomor HP') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->phone ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('Alamat') }}</dt>
                                    <dd class="col-sm-8">
                                        {{ $customer->address ?? '-' }}
                                        @if($customer->latitude && $customer->longitude)
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" class="btn btn-sm btn-outline-danger ms-2" title="{{ __('Lihat Lokasi') }}">
                                                <i class="fa-solid fa-map-location-dot"></i> {{ __('Peta') }}
                                            </a>
                                        @elseif($customer->address)
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($customer->address) }}" target="_blank" class="btn btn-sm btn-outline-secondary ms-2" title="{{ __('Cari Lokasi') }}">
                                                <i class="fa-solid fa-magnifying-glass-location"></i> {{ __('Peta') }}
                                            </a>
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        </div>

                        <!-- Informasi teknis -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">{{ __('Informasi Layanan') }}</h6>
                            <div class="p-3  rounded border dark:bg-dark dark:border-secondary">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">{{ __('Package') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->package ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('Alamat IP') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->ip_address ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('VLAN') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->vlan ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('ODP') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->odp ?? '-' }}</dd>

                                    <dt class="col-sm-4 border-top pt-2 mt-2">{{ __('ONU Serial') }}</dt>
                                    <dd class="col-sm-8 border-top pt-2 mt-2 font-monospace small">{{ $customer->onu_serial ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('Model Perangkat') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->device_model ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('Nama SSID') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->ssid_name ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('Password SSID') }}</dt>
                                    <dd class="col-sm-8 font-monospace">{{ $customer->ssid_password ?? '-' }}</dd>
                                    
                                    <dt class="col-sm-4 border-top pt-2 mt-2">{{ __('Modem Status') }}</dt>
                                    <dd class="col-sm-8 border-top pt-2 mt-2">
                                        @if(($modemStatus['online'] ?? false) === true)
                                            <span class="badge text-bg-success">{{ __('Online') }}</span>
                                        @else
                                            <span class="badge text-bg-danger">{{ __('Offline') }}</span>
                                        @endif
                                        @if(!empty($modemStatus['last_inform']))
                                            <div class="small text-muted mt-1">{{ __('Inform Terakhir') }}: {{ \Carbon\Carbon::parse($modemStatus['last_inform'])->diffForHumans() }}</div>
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top">
                        <div class="row g-4">
                            <!-- Tiket -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold mb-3">{{ __('Tiket Terbaru') }}</h6>
                                <div class="table-responsive border rounded">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('Subjek') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Tanggal') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($customer->tickets->take(5) as $ticket)
                                                <tr>
                                                    <td>{{ $ticket->subject }}</td>
                                                    <td>
                                                        <span class="badge 
                                                            {{ $ticket->status === 'open' ? 'text-bg-danger' : 'text-bg-success' }}">
                                                            {{ __(ucfirst($ticket->status)) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-muted small">{{ $ticket->created_at->format('d M Y') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">{{ __('Tidak ada tiket.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Instalasi -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold mb-3">{{ __('Riwayat Instalasi') }}</h6>
                                <div class="table-responsive border rounded">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Jadwal') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($customer->installations->take(5) as $install)
                                                <tr>
                                                    <td>
                                                        <span class="badge text-bg-primary">
                                                            {{ __(ucfirst($install->status)) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-muted small">
                                                        {{ $install->scheduled_date ? \Carbon\Carbon::parse($install->scheduled_date)->format('d M Y') : __('Belum Dijadwalkan') }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted py-3">{{ __('Tidak ada data instalasi.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
