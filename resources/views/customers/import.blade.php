@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-12 px-3 px-lg-0">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <!-- Responsive Header -->
            <div class="card-header  border-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <h5 class="mb-0 fw-bold text-body-emphasis text-truncate" style="max-width: 100%;">
                        <i class="fa-solid fa-cloud-arrow-down me-2 text-primary"></i>
                        {{ __('Import from GenieACS') }}
                    </h5>
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm w-100 w-md-auto">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <!-- Search Form -->
                <form action="{{ route('customers.import') }}" method="GET" class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text "><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="{{ __('Search Serial, Username, Model...') }}" value="{{ $search ?? '' }}">
                        <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                        @if(request('search'))
                            <a href="{{ route('customers.import') }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>

                @if($newDevices->isEmpty())
                    <div class="alert alert-info shadow-sm border-0">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-circle-info fa-2x me-3 text-info opacity-50"></i>
                            <div>
                                @if(request('search'))
                                    <strong>{{ __('No devices found.') }}</strong><br>
                                    <span class="small">{{ __('No new devices matching your search.') }}</span>
                                @else
                                    <strong>{{ __('All Synced.') }}</strong><br>
                                    <span class="small">{{ __('No new devices found in GenieACS.') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-light border shadow-sm mb-4">
                        <i class="fa-solid fa-lightbulb text-warning me-2"></i> 
                        {{ __('Found :count devices.', ['count' => $newDevices->count()]) }}
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="">
                                <tr>
                                    <th scope="col" class="ps-3">{{ __('Serial') }}</th>
                                    <!-- Hidden on mobile to save space -->
                                    <th scope="col" class="d-none d-md-table-cell">{{ __('Model / SSID') }}</th>
                                    <th scope="col" class="d-none d-md-table-cell">{{ __('IP Address') }}</th>
                                    <th scope="col" class="d-none d-md-table-cell">{{ __('Last Inform') }}</th>
                                    <th scope="col" class="text-end pe-3" style="width: 140px;">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($newDevices as $device)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold font-monospace text-break">{{ $device->serial }}</div>
                                            <!-- Visible only on mobile below Serial -->
                                            <div class="d-md-none small text-muted mt-1">
                                                {{ $device->device_model ?? 'Unknown Model' }}
                                            </div>
                                        </td>
                                        
                                        <!-- Desktop Only -->
                                        <td class="d-none d-md-table-cell">
                                            <div class="fw-bold small">{{ $device->device_model }}</div>
                                            <div class="small text-muted text-truncate" style="max-width: 200px;">{{ $device->ssid_name }}</div>
                                        </td>

                                        <td class="d-none d-md-table-cell text-muted small font-monospace">{{ $device->ip }}</td>
                                        <td class="d-none d-md-table-cell text-muted small">{{ $device->lastInform }}</td>
                                        
                                        <td class="text-end pe-3">
                                            <a href="{{ route('customers.create', [
                                                'onu_serial' => $device->serial, 
                                                'ip_address' => $device->ip, 
                                                'name' => $device->name,
                                                'device_model' => $device->device_model,
                                                'ssid_name' => $device->ssid_name,
                                                'ssid_password' => $device->ssid_password
                                            ]) }}" class="btn btn-primary btn-sm d-block w-100">
                                                <i class="fa-solid fa-plus me-1"></i> {{ __('Add Customer') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection