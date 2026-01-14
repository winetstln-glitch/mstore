@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">Import Customers from GenieACS</h5>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                </a>
            </div>

            <div class="card-body p-4">
                @if($newDevices->isEmpty())
                    <div class="alert alert-info shadow-sm border-0">
                        <i class="fa-solid fa-circle-info me-2"></i> No new devices found to import. All devices in GenieACS are already linked to customers.
                    </div>
                @else
                    <div class="alert alert-light border shadow-sm mb-4">
                        <i class="fa-solid fa-lightbulb text-warning me-2"></i> 
                        Found <strong>{{ $newDevices->count() }}</strong> devices in GenieACS that are not yet registered as customers.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-body-tertiary">
                                <tr>
                                    <th scope="col" class="ps-3">Serial Number</th>
                                    <th scope="col">Model / SSID</th>
                                    <th scope="col">IP Address</th>
                                    <th scope="col">Last Inform</th>
                                    <th scope="col" class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($newDevices as $device)
                                    <tr>
                                        <td class="ps-3 fw-medium">{{ $device->serial }}</td>
                                        <td>
                                            <div class="small fw-bold">{{ $device->device_model }}</div>
                                            <div class="small text-muted">{{ $device->ssid_name }}</div>
                                        </td>
                                        <td>{{ $device->ip }}</td>
                                        <td>{{ $device->lastInform }}</td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('customers.create', [
                                                'onu_serial' => $device->serial, 
                                                'ip_address' => $device->ip, 
                                                'name' => $device->name,
                                                'device_model' => $device->device_model,
                                                'ssid_name' => $device->ssid_name,
                                                'ssid_password' => $device->ssid_password
                                            ]) }}" class="btn btn-primary btn-sm">
                                                <i class="fa-solid fa-plus me-1"></i> Add as Customer
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
