@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-info">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">{{ __('Network Monitor (GenieACS)') }}</h5>
                    <div class="mt-2">
                        @if(isset($activeServer))
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="fa-solid fa-circle fa-xs me-1"></i> {{ __('Connected to:') }} {{ $activeServer->name }}
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                {{ __('Using Default/Env Config') }}
                            </span>
                        @endif
                        <a href="{{ route('genieacs.servers.index') }}" class="text-decoration-none ms-2 small fw-bold">
                            {{ __('Manage Servers') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <h6 class="fw-bold mb-0">{{ __('Connected Devices (TR-069)') }}</h6>
                        <span class="badge bg-primary rounded-pill">{{ $totalDevices ?? count($devices) }} {{ __('Total') }}</span>
                        <div class="text-muted small d-flex align-items-center bg-light px-2 py-1 rounded">
                            <i class="fa-solid fa-clock-rotate-left me-2"></i>
                            <span id="autoRefreshTimer">{{ __('Next refresh:') }} 0:10</span>
                        </div>
                    </div>
                    <small class="text-muted">{{ __('Showing latest 50 devices') }}</small>
                </div>

                {{-- Alerts handled by SweetAlert in Layout --}}

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle small table-bordered border-secondary-subtle">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th scope="col" class="text-center" width="1%">{{ __('Status') }}</th>
                                <th scope="col">ID</th>
                                <th scope="col">{{ __('SN ONT') }}</th>
                                <th scope="col">SSID</th>
                                <th scope="col" class="text-center">{{ __('Active') }}</th>
                                <th scope="col" class="text-center">Hotspot</th>
                                <th scope="col" class="text-center">RX</th>
                                <th scope="col" class="text-center">Temp</th>
                                <th scope="col">{{ __('Uptime') }}</th>
                                <th scope="col">IP PPPoE</th>
                                <th scope="col">IP WAN/TR069</th>
                                <th scope="col">PON</th>
                                <th scope="col">Mac</th>
                                <th scope="col">{{ __('Product Class') }}</th>
                                <th scope="col">{{ __('Reg Time') }}</th>
                                <th scope="col">Komunikasi Terakhir</th>
                                <th scope="col" class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($devices as $device)
                                @php
                                    $lastInform = isset($device['_lastInform']) ? strtotime($device['_lastInform']) : 0;
                                    $isOnline = (time() - $lastInform) < 300;
                                    $id = $device['_id'];
                                    
                                    // Helper to get value safely
                                    $get = function($key) use ($device) {
                                        $val = data_get($device, $key . '._value');
                                        if ($val === null) {
                                            $val = data_get($device, $key);
                                        }
                                        
                                        if (is_array($val)) {
                                            return isset($val['_value']) && is_scalar($val['_value']) ? (string)$val['_value'] : '-';
                                        }
                                        
                                        return $val !== null ? (string)$val : '-';
                                    };

                                    // Mapping fields
                                    $pppoeUser = $get('VirtualParameters.pppoeUsername');
                                    $sn = $get('VirtualParameters.getSerialNumber');
                                    // Fallback for SN if VP missing
                                    if ($sn === '-') $sn = data_get($device, '_deviceId._SerialNumber') ?? '-';

                                    $ssid = $get('InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID');
                                    $active = $get('InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations');
                                    $hotspot = $get('VirtualParameters.activedevices');
                                    $rx = $get('VirtualParameters.RXPower');
                                    $temp = $get('VirtualParameters.gettemp');
                                    $uptime = $get('VirtualParameters.getdeviceuptime');
                                    $ipPppoe = $get('VirtualParameters.pppoeIP');
                                    $ipWan = $get('VirtualParameters.IPTR069');
                                    $ponMode = $get('VirtualParameters.getponmode');
                                    $ponMac = $get('VirtualParameters.PonMac'); // Or pppoeMac as per list? User listed both.
                                    
                                    $productClass = $get('DeviceID.ProductClass');
                                    if ($productClass === '-') $productClass = data_get($device, '_deviceId._ProductClass') ?? '-';

                                    $regTime = $get('Events.Registered');
                                    // Format Last Inform
                                    $lastInformStr = $device['_lastInform'] ? \Carbon\Carbon::parse($device['_lastInform'])->format('Y-m-d H:i:s') : '-';
                                @endphp
                                <tr class="text-nowrap">
                                    <td class="text-center">
                                        <i class="fa-solid fa-circle {{ $isOnline ? 'text-success' : 'text-danger' }}" title="{{ $isOnline ? __('Online') : __('Offline') }}"></i>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{ route('genieacs.show', $id) }}" class="fw-bold text-decoration-none me-2">
                                                @if(isset($aliases[$id]) && $aliases[$id])
                                                    {{ $aliases[$id] }}
                                                    <small class="text-muted d-block fw-normal" style="font-size: 0.7em">{{ $pppoeUser !== '-' ? $pppoeUser : $id }}</small>
                                                @else
                                                    {{ $pppoeUser !== '-' ? $pppoeUser : $id }}
                                                @endif
                                            </a>
                                            <button type="button" class="btn btn-sm btn-link text-muted p-0" data-bs-toggle="modal" data-bs-target="#editAliasModal{{ md5($id) }}">
                                                <i class="fa-solid fa-pen fa-xs"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="modal fade" id="editAliasModal{{ md5($id) }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form action="{{ route('genieacs.updateAlias', $id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{ __('Edit Device Alias') }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('Alias / Name') }}</label>
                                                                <input type="text" name="alias" class="form-control" value="{{ $aliases[$id] ?? '' }}" placeholder="e.g. Rumah Pak Budi">
                                                                <div class="form-text">{{ __('This name will be displayed instead of ID/PPPoE.') }}</div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted small">{{ __('Original ID') }}</label>
                                                                <input type="text" class="form-control form-control-sm" value="{{ $id }}" readonly disabled>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                                                            <button type="submit" class="btn btn-primary">{{ __('Save changes') }}</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('genieacs.show', $id) }}" class="text-decoration-none text-dark">
                                            {{ $sn }}
                                        </a>
                                    </td>
                                    <td>{{ $ssid }}</td>
                                    <td class="text-center">{{ $active }}</td>
                                    <td class="text-center">{{ $hotspot }}</td>
                                    <td class="text-center">
                                        @if($rx !== '-')
                                            <span class="badge {{ floatval($rx) < -27 ? 'text-bg-danger' : 'text-bg-success' }}">
                                                {{ $rx }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $temp }}</td>
                                    <td>{{ $uptime }}</td>
                                    <td>
                                        @if($ipPppoe !== '-' && filter_var($ipPppoe, FILTER_VALIDATE_IP))
                                            <a href="http://{{ $ipPppoe }}" target="_blank" class="text-decoration-none">
                                                {{ $ipPppoe }} <i class="fa-solid fa-external-link-alt fa-xs text-muted"></i>
                                            </a>
                                        @else
                                            {{ $ipPppoe }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($ipWan !== '-' && filter_var($ipWan, FILTER_VALIDATE_IP))
                                            <a href="http://{{ $ipWan }}" target="_blank" class="text-decoration-none">
                                                {{ $ipWan }} <i class="fa-solid fa-external-link-alt fa-xs text-muted"></i>
                                            </a>
                                        @else
                                            {{ $ipWan }}
                                        @endif
                                    </td>
                                    <td>{{ $ponMode }}</td>
                                    <td>{{ $ponMac }}</td>
                                    <td>{{ $productClass }}</td>
                                    <td>{{ $regTime }}</td>
                                    <td>
                                        <small>{{ $lastInformStr }}</small>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('genieacs.refresh', $id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-1" title="Summon (Refresh)">
                                                <i class="fa-solid fa-bolt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="17" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fa-solid fa-network-wired fa-3x mb-3"></i>
                                            <p class="mb-0">{{ __('No devices found.') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    {{ $devices->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let timeLeft = 10; // 10 seconds
        const timerDisplay = document.getElementById('autoRefreshTimer');
        
        const countdown = setInterval(function() {
            timeLeft--;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerDisplay.innerText = '{{ __('Refreshing...') }}';
                window.location.reload();
            } else {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerDisplay.innerText = `{{ __('Next refresh:') }} ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            }
        }, 1000);
    });
</script>
@endpush