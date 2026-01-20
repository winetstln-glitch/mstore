@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold">{{ __('Device Settings') }}: {{ $customer->onu_serial }}</h4>
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> {{ __('Back to Edit') }}
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row g-4">
                <!-- WAN Settings -->
                <div class="col-md-6">
                    <div class="card shadow-sm h-100 border-top border-4 border-primary">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0"><i class="fa-solid fa-network-wired me-2"></i>{{ __('WAN Settings') }}</h5>
                        </div>
                        <div class="card-body">
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

                            <form action="{{ route('customers.settings.wan', $customer) }}" method="POST">
                                @csrf
                                <input type="hidden" name="device_id" value="{{ $deviceId }}">
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

                                    <div class="col-12">
                                        <div class="alert alert-info py-2 mb-0">
                                            <small><strong>{{ __('Current Status') }}:</strong> {{ $wanSettings['status'] ?? 'Unknown' }}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> {{ __('Save WAN Settings') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- WLAN Settings -->
                <div class="col-md-6">
                    <div class="card shadow-sm h-100 border-top border-4 border-success">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0"><i class="fa-solid fa-wifi me-2"></i>{{ __('WLAN Settings') }}</h5>
                        </div>
                        <div class="card-body">
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
                                        <form action="{{ route('customers.settings.wlan', $customer) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="device_id" value="{{ $deviceId }}">
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
                                            </div>

                                            <div class="mt-4 text-end">
                                                <button type="submit" class="btn btn-success"><i class="fa-solid fa-save me-1"></i> {{ __('Save WLAN Settings') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(id) {
        var input = document.getElementById(id);
        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }
    }
</script>
@endsection
