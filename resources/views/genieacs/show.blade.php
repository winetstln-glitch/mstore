@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold text-body-emphasis">
            {{ __('Device Management') }}: <span class="text-primary">{{ $device['_deviceId']['_SerialNumber'] ?? 'Unknown' }}</span>
        </h4>
        <div class="btn-group">
            @if(isset($deviceIp) && $deviceIp)
                <a href="http://{{ $deviceIp }}" target="_blank" class="btn btn-outline-primary shadow-sm" title="{{ __('Open Device Web Interface') }}">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> {{ __('Web GUI') }}
                </a>
            @endif
            <form action="{{ route('genieacs.refresh', ['id' => $id, 'server_id' => $serverId]) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary shadow-sm" title="{{ __('Summon (Connection Request)') }}">
                    <i class="fa-solid fa-bolt me-1"></i> {{ __('Summon') }}
                </button>
            </form>
            <form action="{{ route('genieacs.reboot', ['id' => $id, 'server_id' => $serverId]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to reboot this device?') }}');">
                @csrf
                <button type="submit" class="btn btn-outline-danger shadow-sm">
                    <i class="fa-solid fa-power-off me-1"></i> {{ __('Reboot') }}
                </button>
            </form>
            <a href="{{ route('genieacs.index') }}" class="btn btn-secondary shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
            </a>
        </div>
    </div>
</div>

{{-- Alerts handled by SweetAlert in Layout --}}

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-body-tertiary border-bottom-0 pt-3 px-3">
                <ul class="nav nav-tabs card-header-tabs" id="deviceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">
                            <i class="fa-solid fa-info-circle me-1"></i> {{ __('Overview') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="wan-tab" data-bs-toggle="tab" data-bs-target="#wan" type="button" role="tab" aria-controls="wan" aria-selected="false">
                            <i class="fa-solid fa-network-wired me-1"></i> {{ __('WAN Configuration') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="wlan-tab" data-bs-toggle="tab" data-bs-target="#wlan" type="button" role="tab" aria-controls="wlan" aria-selected="false">
                            <i class="fa-solid fa-wifi me-1"></i> {{ __('WiFi Settings') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab" aria-controls="clients" aria-selected="false">
                            <i class="fa-solid fa-users me-1"></i> {{ __('WiFi Clients') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="diag-tab" data-bs-toggle="tab" data-bs-target="#diag" type="button" role="tab" aria-controls="diag" aria-selected="false">
                            <i class="fa-solid fa-stethoscope me-1"></i> {{ __('Diagnostics') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="params-tab" data-bs-toggle="tab" data-bs-target="#params" type="button" role="tab" aria-controls="params" aria-selected="false">
                            <i class="fa-solid fa-list me-1"></i> {{ __('All Parameters') }}
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body p-4">
                <div class="tab-content" id="deviceTabsContent">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                        <h5 class="fw-bold mb-4">{{ __('Device Overview') }}</h5>
                        <div class="row g-4">
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-body-tertiary rounded border">
                                    <small class="text-body-secondary d-block mb-1">{{ __('Product Class') }}</small>
                                    <span class="fw-bold fs-5">{{ $device['_deviceId']['_ProductClass'] ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-body-tertiary rounded border">
                                    <small class="text-body-secondary d-block mb-1">{{ __('Serial Number') }}</small>
                                    <span class="fw-bold fs-5">{{ $device['_deviceId']['_SerialNumber'] ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-body-tertiary rounded border">
                                    <small class="text-body-secondary d-block mb-1">{{ __('Manufacturer') }}</small>
                                    <span class="fw-bold fs-5">{{ $device['_deviceId']['_Manufacturer'] ?? $device['_deviceId']['_OUI'] ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-body-tertiary rounded border">
                                    <small class="text-body-secondary d-block mb-1">{{ __('IP Address') }}</small>
                                    <div class="d-flex align-items-center">
                                        <span class="fw-bold fs-5 me-2">{{ $deviceIp ?? '-' }}</span>
                                        @if(isset($deviceIp) && $deviceIp)
                                            <a href="http://{{ $deviceIp }}" target="_blank" class="text-decoration-none small">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-body-tertiary rounded border">
                                    <small class="text-body-secondary d-block mb-1">{{ __('Last Inform') }}</small>
                                    <span class="fw-bold fs-5">{{ $device['_lastInform'] ?? __('Never') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WAN Tab -->
                    <div class="tab-pane fade" id="wan" role="tabpanel" aria-labelledby="wan-tab">
                        <h5 class="fw-bold mb-4">{{ __('WAN Configuration') }}</h5>
                        
                        @if(isset($wanConnections) && count($wanConnections) > 0)
                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('Select WAN Connection') }}</label>
                                <select class="form-select" onchange="window.location.href = this.value">
                                    @foreach($wanConnections as $conn)
                                        <option value="{{ request()->fullUrlWithQuery(['wan_path' => $conn['path']]) }}" {{ (($selectedWanPath == $conn['path']) || (!$selectedWanPath && $loop->first)) ? 'selected' : '' }}>
                                            {{ $conn['name'] }} ({{ $conn['type'] }} {{ $conn['index'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <form action="{{ route('genieacs.updateWan', ['id' => $id, 'server_id' => $serverId]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="wan_path" value="{{ $wanSettings['path'] ?? '' }}">
                            
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="wan_enable" name="enable" value="1" {{ ($wanSettings['enable'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="wan_enable">{{ __('Enable WAN') }}</label>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Connection Name') }}</label>
                                    <input type="text" class="form-control" name="conn_name" value="{{ $wanSettings['conn_name'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('VLAN ID') }}</label>
                                    <input type="text" class="form-control" name="vlan" value="{{ $wanSettings['vlan'] ?? '' }}">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Connection Type') }}</label>
                                    <select class="form-select" name="conn_type">
                                        <option value="IP_Routed" {{ ($wanSettings['conn_type'] ?? '') == 'IP_Routed' ? 'selected' : '' }}>IP_Routed (Route)</option>
                                        <option value="IP_Bridged" {{ ($wanSettings['conn_type'] ?? '') == 'IP_Bridged' ? 'selected' : '' }}>IP_Bridged (Bridge)</option>
                                        <option value="PPPoE_Routed" {{ ($wanSettings['conn_type'] ?? '') == 'PPPoE_Routed' ? 'selected' : '' }}>PPPoE_Routed</option>
                                        <option value="PPPoE_Bridged" {{ ($wanSettings['conn_type'] ?? '') == 'PPPoE_Bridged' ? 'selected' : '' }}>PPPoE_Bridged</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Service List') }}</label>
                                    <input type="text" class="form-control" name="service" value="{{ $wanSettings['service'] ?? 'INTERNET' }}">
                                    <div class="form-text">e.g. INTERNET, TR069, VOIP</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Username') }}</label>
                                    <input type="text" class="form-control" name="username" value="{{ $wanSettings['username'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Password') }}</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" id="wan_password" value="{{ $wanSettings['password'] ?? '' }}">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('wan_password')"><i class="fa-solid fa-eye"></i></button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="nat" name="nat" value="1" {{ ($wanSettings['nat'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="nat">{{ __('Enable NAT') }}</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('LAN Binding') }}</label>
                                    <input type="text" class="form-control" name="lan_bind" value="{{ $wanSettings['lan_bind'] ?? '' }}">
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-save me-1"></i> {{ __('Update WAN Settings') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- WiFi Tab -->
                    <div class="tab-pane fade" id="wlan" role="tabpanel" aria-labelledby="wlan-tab">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">{{ __('WiFi Configuration') }}</h5>
                        </div>

                        <!-- SSID Mode Select Pills -->
                        <ul class="nav nav-pills mb-4" id="ssidTabs" role="tablist">
                            @foreach(range(1, 4) as $index)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $index === 1 ? 'active' : '' }}" id="ssid{{ $index }}-tab" data-bs-toggle="pill" data-bs-target="#ssid{{ $index }}" type="button" role="tab" aria-controls="ssid{{ $index }}" aria-selected="{{ $index === 1 ? 'true' : 'false' }}">
                                        SSID {{ $index }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content" id="ssidTabsContent">
                            @foreach([$wlanSettings1, $wlanSettings2, $wlanSettings3, $wlanSettings4] as $key => $wlanSettings)
                                @php $index = $key + 1; @endphp
                                <div class="tab-pane fade {{ $index === 1 ? 'show active' : '' }}" id="ssid{{ $index }}" role="tabpanel" aria-labelledby="ssid{{ $index }}-tab">
                                    <form action="{{ route('genieacs.updateWlan', ['id' => $id, 'server_id' => $serverId]) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="index" value="{{ $index }}">

                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="wlan_enable_{{ $index }}" name="enable" value="1" {{ ($wlanSettings['enable'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="wlan_enable_{{ $index }}">{{ __('Enable SSID') }} {{ $index }}</label>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label class="form-label">{{ __('SSID Name') }}</label>
                                                <input type="text" class="form-control" name="ssid" value="{{ $wlanSettings['ssid'] ?? '' }}">
                                            </div>

                                            <div class="col-md-12">
                                                <label class="form-label">{{ __('WLAN Passphrase') }}</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" name="password" id="wlan_password_{{ $index }}" value="{{ $wlanSettings['password'] ?? '' }}">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('wlan_password_{{ $index }}')"><i class="fa-solid fa-eye"></i></button>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Security Mode') }}</label>
                                                <select class="form-select" name="security">
                                                    <option value="WPA/WPA2-PSK" {{ ($wlanSettings['security'] ?? '') == 'WPA/WPA2-PSK' ? 'selected' : '' }}>WPA/WPA2-PSK</option>
                                                    <option value="WPA2-PSK" {{ ($wlanSettings['security'] ?? '') == 'WPA2-PSK' ? 'selected' : '' }}>WPA2-PSK</option>
                                                    <option value="WPA-PSK" {{ ($wlanSettings['security'] ?? '') == 'WPA-PSK' ? 'selected' : '' }}>WPA-PSK</option>
                                                    <option value="None" {{ ($wlanSettings['security'] ?? '') == 'None' ? 'selected' : '' }}>None</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('TX Power') }}</label>
                                                <select class="form-select" name="power">
                                                    <option value="100" {{ ($wlanSettings['power'] ?? '') == '100' ? 'selected' : '' }}>100%</option>
                                                    <option value="80" {{ ($wlanSettings['power'] ?? '') == '80' ? 'selected' : '' }}>80%</option>
                                                    <option value="60" {{ ($wlanSettings['power'] ?? '') == '60' ? 'selected' : '' }}>60%</option>
                                                    <option value="40" {{ ($wlanSettings['power'] ?? '') == '40' ? 'selected' : '' }}>40%</option>
                                                    <option value="20" {{ ($wlanSettings['power'] ?? '') == '20' ? 'selected' : '' }}>20%</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Channel') }}</label>
                                                <select class="form-select" name="channel">
                                                    <option value="0" {{ ($wlanSettings['channel'] ?? '') == '0' ? 'selected' : '' }}>Auto</option>
                                                    @for($i = 1; $i <= 13; $i++)
                                                        <option value="{{ $i }}" {{ ($wlanSettings['channel'] ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                                    @endfor
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" id="auto_channel_{{ $index }}" name="auto_channel" value="1" {{ ($wlanSettings['auto_channel'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="auto_channel_{{ $index }}">{{ __('Auto Channel') }}</label>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="d-flex justify-content-between text-muted small mt-2">
                                                    <span><strong>{{ __('BSSID') }}:</strong> {{ $wlanSettings['bssid'] ?? 'N/A' }}</span>
                                                    <span><strong>{{ __('Connected Devices') }}:</strong> {{ $wlanSettings['connected_devices'] ?? 0 }}</span>
                                                </div>
                                            </div>

                                            <div class="col-12 mt-4">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fa-solid fa-save me-1"></i> {{ __('Update SSID') }} {{ $index }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Diagnostics Tab -->
                    <div class="tab-pane fade" id="diag" role="tabpanel" aria-labelledby="diag-tab">
                        <h5 class="fw-bold mb-4">{{ __('Diagnostics') }}</h5>
                        <form action="{{ route('genieacs.ping', ['id' => $id, 'server_id' => $serverId]) }}" method="POST" class="row g-3">
                            @csrf
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Ping Host (IP)') }}</label>
                                <input type="text" name="host" class="form-control" placeholder="8.8.8.8">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fa-solid fa-paper-plane me-1"></i> {{ __('Send Ping') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- ODP & Mapping Tab -->
                    <div class="tab-pane fade" id="odp" role="tabpanel" aria-labelledby="odp-tab">
                        <h5 class="fw-bold mb-4">ODP Connection & Mapping</h5>
                        
                        @if(!$customer)
                            <div class="alert alert-warning">
                                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                                No Customer linked to this device (Serial: {{ $device['_deviceId']['_SerialNumber'] ?? 'Unknown' }}).
                                <br>
                                Please link this device to a customer in the Customer Management section first.
                            </div>
                        @else
                            <div class="row g-4">
                                <!-- Customer Info -->
                                <div class="col-md-4">
                                    <div class="card h-100 border-info-subtle">
                                        <div class="card-header bg-info-subtle fw-bold text-info-emphasis">
                                            <i class="fa-solid fa-user me-2"></i> Customer Details
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-1"><small class="text-muted">Name:</small><br><strong>{{ $customer->name }}</strong></p>
                                            <p class="mb-1"><small class="text-muted">Address:</small><br>{{ $customer->address }}</p>
                                            <p class="mb-1"><small class="text-muted">Phone:</small><br>{{ $customer->phone ?? '-' }}</p>
                                            <p class="mb-0"><small class="text-muted">Coordinates:</small><br>
                                                @if($customer->latitude && $customer->longitude)
                                                    <a href="https://www.google.com/maps?q={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank">
                                                        {{ $customer->latitude }}, {{ $customer->longitude }}
                                                    </a>
                                                @else
                                                    <span class="text-danger">Not Set</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- ODP Connection -->
                                <div class="col-md-8">
                                    <div class="card h-100 border-success-subtle">
                                        <div class="card-header bg-success-subtle fw-bold text-success-emphasis d-flex justify-content-between align-items-center">
                                            <span><i class="fa-solid fa-server me-2"></i> Connected ODP</span>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createOdpModal">
                                                <i class="fa-solid fa-plus me-1"></i> New ODP
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <form id="updateCustomerOdpForm" class="row g-3 align-items-end">
                                                <div class="col-md-8">
                                                    <label class="form-label">Assigned ODP</label>
                                                    <select class="form-select" id="customer_odp_id" name="odp_id">
                                                        <option value="">-- Select ODP --</option>
                                                        @foreach($odps as $odp)
                                                            <option value="{{ $odp->id }}" {{ $customer->odp_id == $odp->id ? 'selected' : '' }}>
                                                                {{ $odp->name }} ({{ $odp->region->name ?? 'No Region' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="button" class="btn btn-success w-100" onclick="saveCustomerOdp()">
                                                        <i class="fa-solid fa-save me-1"></i> Save Connection
                                                    </button>
                                                </div>
                                            </form>

                                            <hr>

                                            <div id="odpDetails" class="{{ $customer->odp_id ? '' : 'd-none' }}">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="fw-bold text-primary mb-1" id="displayOdpName">
                                                            {{ $customer->odp ? $customer->odp : 'Unknown ODP' }}
                                                        </h6>
                                                        <small class="text-muted" id="displayOdpRegion">
                                                            @if($customer->odp_id && $odps->find($customer->odp_id))
                                                                {{ $odps->find($customer->odp_id)->region->name ?? '' }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-outline-secondary" onclick="editSelectedOdp()">
                                                            <i class="fa-solid fa-pen"></i> Edit
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSelectedOdp()">
                                                            <i class="fa-solid fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-3 p-3 bg-light rounded border">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fa-solid fa-route me-2 text-primary"></i>
                                                        <span class="fw-bold">Cable Status:</span>
                                                        <span class="ms-2 badge {{ (isset($device['_lastInform']) && (time() - strtotime($device['_lastInform'])) < 300) ? 'bg-success' : 'bg-danger' }}">
                                                            {{ (isset($device['_lastInform']) && (time() - strtotime($device['_lastInform'])) < 300) ? 'Connected' : 'Disconnected' }}
                                                        </span>
                                                    </div>
                                                    <small class="text-muted d-block">
                                                        Map visualization available in <a href="{{ route('map.index') }}">Network Map</a>.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- All Parameters Tab -->
                    <div class="tab-pane fade" id="params" role="tabpanel" aria-labelledby="params-tab">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">All Device Parameters</h5>
                            <input type="text" id="paramSearch" class="form-control w-25" placeholder="Search parameters...">
                        </div>
                        
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-sm table-striped table-hover font-monospace small border" id="paramsTable">
                                <thead class="bg-body-tertiary sticky-top z-1">
                                    <tr>
                                        <th scope="col" style="width: 50%;">Parameter</th>
                                        <th scope="col" style="width: 40%;">Value</th>
                                        <th scope="col" style="width: 10%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($parameters as $param)
                                        <tr>
                                            <td class="text-break align-middle py-1">
                                                <span class="param-path">{{ $param['path'] }}</span>
                                                @if($param['writable'])
                                                    <i class="fa-solid fa-pen-to-square text-muted ms-1" style="font-size: 0.7rem;" title="Writable"></i>
                                                @endif
                                            </td>
                                            <td class="text-break align-middle py-1 param-value">{{ $param['value'] }}</td>
                                            <td class="text-center align-middle py-1">
                                                @if($param['writable'])
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editParamModal" 
                                                        data-path="{{ $param['path'] }}" 
                                                        data-value="{{ $param['value'] }}">
                                                        Edit
                                                    </button>
                                                @else
                                                    <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-body-secondary p-4">
                                                No parameters found.
                                            </td>
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

<!-- Edit Parameter Modal -->
<div class="modal fade" id="editParamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('genieacs.updateParam', $id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Parameter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Parameter</label>
                        <input type="text" name="parameter_name" id="modalParamPath" class="form-control bg-body-tertiary" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Value</label>
                        <textarea name="parameter_value" id="modalParamValue" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Parameter Search
        const searchInput = document.getElementById('paramSearch');
        const table = document.getElementById('paramsTable');
        
        if (searchInput && table) {
            searchInput.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                const rows = table.getElementsByTagName('tr');
                
                for (let i = 1; i < rows.length; i++) { // Start from 1 to skip header
                    const pathCol = rows[i].getElementsByClassName('param-path')[0];
                    const valCol = rows[i].getElementsByClassName('param-value')[0];
                    
                    if (pathCol && valCol) {
                        const pathText = pathCol.textContent || pathCol.innerText;
                        const valText = valCol.textContent || valCol.innerText;
                        
                        if (pathText.toLowerCase().indexOf(filter) > -1 || valText.toLowerCase().indexOf(filter) > -1) {
                            rows[i].style.display = "";
                        } else {
                            rows[i].style.display = "none";
                        }
                    }
                }
            });
        }

        // Edit Modal Population
        const editModal = document.getElementById('editParamModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const path = button.getAttribute('data-path');
                const value = button.getAttribute('data-value');
                
                const modalPathInput = editModal.querySelector('#modalParamPath');
                const modalValueInput = editModal.querySelector('#modalParamValue');
                
                modalPathInput.value = path;
                modalValueInput.value = value;
            });
        }});
    });
</script>

<!-- Create ODP Modal -->
<div class="modal fade" id="createOdpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New ODP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createOdpForm">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Region</label>
                        <select class="form-select" name="region_id" required>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Latitude</label>
                            <input type="text" class="form-control" name="latitude">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Longitude</label>
                            <input type="text" class="form-control" name="longitude">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" class="form-control" name="capacity" value="8">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Filled</label>
                            <input type="number" class="form-control" name="filled" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveNewOdp()">Create ODP</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit ODP Modal -->
<div class="modal fade" id="editOdpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit ODP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editOdpForm">
                    <input type="hidden" name="id" id="edit_odp_id">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" id="edit_odp_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Region</label>
                        <select class="form-select" name="region_id" id="edit_odp_region_id" required>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Latitude</label>
                            <input type="text" class="form-control" name="latitude" id="edit_odp_latitude">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Longitude</label>
                            <input type="text" class="form-control" name="longitude" id="edit_odp_longitude">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" class="form-control" name="capacity" id="edit_odp_capacity">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Filled</label>
                            <input type="number" class="form-control" name="filled" id="edit_odp_filled">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_odp_description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateOdp()">Update ODP</button>
            </div>
        </div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function saveCustomerOdp() {
        const odpId = document.getElementById('customer_odp_id').value;
        const customerId = {{ $customer->id ?? 'null' }};
        
        if (!customerId) return;
        
        fetch(`/customers/${customerId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ odp_id: odpId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Connection saved!');
                location.reload(); 
            } else {
                alert('Failed to save connection.');
            }
        });
    }

    function saveNewOdp() {
        const form = document.getElementById('createOdpForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        fetch('/odps', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('ODP Created!');
                location.reload();
            } else {
                alert('Error creating ODP: ' + JSON.stringify(result.errors || result.message));
            }
        });
    }

    function editSelectedOdp() {
        const odpId = document.getElementById('customer_odp_id').value;
        if (!odpId) {
            alert('No ODP selected');
            return;
        }

        // Fetch ODP details
        fetch(`/odps/${odpId}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const odp = result.data;
                document.getElementById('edit_odp_id').value = odp.id;
                document.getElementById('edit_odp_name').value = odp.name;
                document.getElementById('edit_odp_region_id').value = odp.region_id;
                document.getElementById('edit_odp_latitude').value = odp.latitude;
                document.getElementById('edit_odp_longitude').value = odp.longitude;
                document.getElementById('edit_odp_capacity').value = odp.capacity;
                document.getElementById('edit_odp_filled').value = odp.filled;
                document.getElementById('edit_odp_description').value = odp.description;
                
                const modal = new bootstrap.Modal(document.getElementById('editOdpModal'));
                modal.show();
            }
        });
    }

    function updateOdp() {
        const odpId = document.getElementById('edit_odp_id').value;
        const form = document.getElementById('editOdpForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        fetch(`/odps/${odpId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('ODP Updated!');
                location.reload();
            } else {
                alert('Error updating ODP');
            }
        });
    }

    function deleteSelectedOdp() {
        const odpId = document.getElementById('customer_odp_id').value;
        if (!odpId) return;

        if (!confirm('Are you sure you want to delete this ODP? This cannot be undone.')) return;

        fetch(`/odps/${odpId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('ODP Deleted!');
                location.reload();
            } else {
                alert('Error deleting ODP');
            }
        });
    }
</script>
@endsection
