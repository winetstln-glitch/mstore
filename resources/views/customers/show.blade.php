@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Customer Details') }}</h5>
                    <div class="btn-group">
                        @if($customer->onu_serial)
                            @can('customer.edit')
                            <a href="{{ route('customers.settings', $customer) }}" class="btn btn-info btn-sm text-white">
                                <i class="fa-solid fa-sliders"></i> {{ __('Device Settings') }}
                            </a>
                            @endcan
                        @endif
                        @can('customer.edit')
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning btn-sm text-white">
                            <i class="fa-solid fa-pen-to-square"></i> {{ __('Edit') }}
                        </a>
                        @endcan
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> {{ __('Back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Personal Info -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">{{ __('Personal Information') }}</h6>
                            <div class="p-3 bg-light rounded border dark:bg-dark dark:border-secondary">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">{{ __('Full Name') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->name }}</dd>

                                    <dt class="col-sm-4">{{ __('Status') }}</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge 
                                            {{ $customer->status === 'active' ? 'text-bg-success' : 
                                               ($customer->status === 'suspend' ? 'text-bg-warning' : 'text-bg-danger') }}">
                                            {{ ucfirst($customer->status) }}
                                        </span>
                                    </dd>

                                    <dt class="col-sm-4">{{ __('Phone Number') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->phone ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">{{ __('Address') }}</dt>
                                    <dd class="col-sm-8">
                                        {{ $customer->address ?? 'N/A' }}
                                        @if($customer->latitude && $customer->longitude)
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" class="btn btn-sm btn-outline-danger ms-2" title="{{ __('View Location') }}">
                                                <i class="fa-solid fa-map-location-dot"></i> {{ __('Map') }}
                                            </a>
                                        @elseif($customer->address)
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($customer->address) }}" target="_blank" class="btn btn-sm btn-outline-secondary ms-2" title="{{ __('Search Location') }}">
                                                <i class="fa-solid fa-magnifying-glass-location"></i> {{ __('Map') }}
                                            </a>
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        </div>

                        <!-- Technical Info -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">{{ __('Service Information') }}</h6>
                            <div class="p-3 bg-light rounded border dark:bg-dark dark:border-secondary">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">{{ __('Package') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->package ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">{{ __('IP Address') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->ip_address ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">{{ __('VLAN') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->vlan ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">{{ __('ODP') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->odp ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4 border-top pt-2 mt-2">{{ __('ONU Serial') }}</dt>
                                    <dd class="col-sm-8 border-top pt-2 mt-2 font-monospace small">{{ $customer->onu_serial ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">{{ __('Device Model') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->device_model ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">{{ __('SSID Name') }}</dt>
                                    <dd class="col-sm-8">{{ $customer->ssid_name ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">{{ __('SSID Password') }}</dt>
                                    <dd class="col-sm-8 font-monospace">{{ $customer->ssid_password ?? 'N/A' }}</dd>
                                    
                                    <dt class="col-sm-4 border-top pt-2 mt-2">{{ __('Modem Status') }}</dt>
                                    <dd class="col-sm-8 border-top pt-2 mt-2">
                                        @if(($modemStatus['online'] ?? false) === true)
                                            <span class="badge text-bg-success">{{ __('Online') }}</span>
                                        @else
                                            <span class="badge text-bg-danger">{{ __('Offline') }}</span>
                                        @endif
                                        @if(!empty($modemStatus['last_inform']))
                                            <div class="small text-muted mt-1">{{ __('Last Inform') }}: {{ \Carbon\Carbon::parse($modemStatus['last_inform'])->diffForHumans() }}</div>
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top">
                        <div class="row g-4">
                            <!-- Tickets -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold mb-3">Recent Tickets</h6>
                                <div class="table-responsive border rounded">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Subject</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($customer->tickets->take(5) as $ticket)
                                                <tr>
                                                    <td>{{ $ticket->subject }}</td>
                                                    <td>
                                                        <span class="badge 
                                                            {{ $ticket->status === 'open' ? 'text-bg-danger' : 'text-bg-success' }}">
                                                            {{ ucfirst($ticket->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-muted small">{{ $ticket->created_at->format('d M Y') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">No tickets found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Installations -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold mb-3">Installation History</h6>
                                <div class="table-responsive border rounded">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Status</th>
                                                <th>Scheduled</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($customer->installations->take(5) as $install)
                                                <tr>
                                                    <td>
                                                        <span class="badge text-bg-primary">
                                                            {{ ucfirst($install->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-muted small">
                                                        {{ $install->scheduled_date ? \Carbon\Carbon::parse($install->scheduled_date)->format('d M Y') : 'Not Scheduled' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted py-3">No installations found.</td>
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
