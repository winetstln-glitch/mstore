@extends('layouts.app')

@section('title', __('Network Monitor (GenieACS)'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-info">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">{{ __('Network Monitor (GenieACS)') }}</h5>
                    <div class="mt-2">
                        @if(isset($modeAll) && $modeAll)
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                <i class="fa-solid fa-circle-nodes fa-xs me-1"></i> {{ __('Connected to: All Servers') }}
                            </span>
                        @elseif(isset($activeServer))
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
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <h6 class="fw-bold mb-0">{{ __('Perangkat Terhubung (TR-069)') }}</h6>
                        <span class="badge bg-primary rounded-pill">{{ $totalDevices ?? count($devices) }} {{ __('Total') }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                        @if(isset($servers) && $servers->count())
                            @php
                                $selectedServerId = $currentServerId ?? request('server_id');
                            @endphp
                            <div class="d-flex align-items-center gap-1">
                                <span class="small text-muted">{{ __('Server') }}</span>
                                <select id="serverSelect" class="form-select form-select-sm" style="width: auto;">
                                    <option value="all" {{ $selectedServerId === 'all' ? 'selected' : '' }}>{{ __('All Servers') }}</option>
                                    @foreach($servers as $server)
                                        <option value="{{ $server->id }}" {{ (string) $selectedServerId === (string) $server->id ? 'selected' : '' }}>
                                            {{ $server->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="input-group input-group-sm" style="max-width: 260px;">
                            <span class="input-group-text bg-body-secondary border-end-0">
                                <i class="fa-solid fa-search text-body-secondary"></i>
                            </span>
                            <input type="text"
                                   id="deviceSearch"
                                   class="form-control border-start-0 ps-1"
                                   placeholder="{{ __('Search ID, SN, PPPoE, IP...') }}">
                        </div>
                        @php
                            $currentPerPage = request('per_page', $perPage ?? 50);
                        @endphp
                        <div class="d-flex align-items-center gap-1">
                            <span class="small text-muted">{{ __('Per page') }}</span>
                            <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;">
                                <option value="20" {{ (string)$currentPerPage === '20' ? 'selected' : '' }}>20</option>
                                <option value="50" {{ (string)$currentPerPage === '50' ? 'selected' : '' }}>50</option>
                                <option value="100" {{ (string)$currentPerPage === '100' ? 'selected' : '' }}>100</option>
                                <option value="all" {{ (string)$currentPerPage === 'all' ? 'selected' : '' }}>All</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshButton">
                            <i class="fa-solid fa-rotate-right me-1"></i> {{ __('Refresh') }}
                        </button>
                    </div>
                </div>

                {{-- Alerts handled by SweetAlert in Layout --}}

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle small table-bordered border-secondary-subtle" id="genieacsDevicesTable">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th scope="col" class="text-center" width="1%">{{ __('Status') }}</th>
                                <th scope="col" class="text-center">{{ __('Action') }}</th>
                                <th scope="col">ID</th>
                                <th scope="col">{{ __('SN ONT') }}</th>
                                <th scope="col">ODP</th>
                                <th scope="col">SSID</th>
                                <th scope="col" class="text-center">{{ __('Perangkat Terhubung') }}</th>
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
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $currentServerId = $activeServer ? $activeServer->id : null;
                            @endphp
                            @forelse ($devices as $device)
                                @php
                                    $lastInform = isset($device['_lastInform']) ? strtotime($device['_lastInform']) : 0;
                                    $isOnline = (time() - $lastInform) < 300;
                                    $id = $device['_id'];
                                    $serverIdForDevice = $device['_mstore_server_id'] ?? $currentServerId;
                                    
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
                                    // $active = $get('InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations');
                                    
                                    // Logic for Connected Devices (MACs)
                                    $hosts = data_get($device, 'InternetGatewayDevice.LANDevice.1.Hosts.Host');
                                    if (!$hosts) {
                                        $hosts = data_get($device, 'Device.Hosts.Host');
                                    }
                                    $connectedMacs = [];
                                    if ($hosts && is_array($hosts)) {
                                        foreach ($hosts as $host) {
                                            $isActive = data_get($host, 'Active._value') ?? data_get($host, 'Active');
                                            // Check if active (handle string 'true', '1', or boolean)
                                            if ($isActive === 'true' || $isActive === true || $isActive === '1' || $isActive === 1) {
                                                $macVal = data_get($host, 'MACAddress._value') ?? data_get($host, 'MACAddress') ?? data_get($host, 'PhysAddress._value') ?? data_get($host, 'PhysAddress');
                                                if ($macVal) {
                                                    $connectedMacs[] = $macVal;
                                                }
                                            }
                                        }
                                    }
                                    // Fallback to TotalAssociations if no hosts found but count exists
                                    $wifiCount = $get('InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations');
                                    $displayCount = count($connectedMacs) > 0 ? count($connectedMacs) : ($wifiCount !== '-' ? $wifiCount : 0);

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
                                    <td class="text-center">
                                        <form action="{{ route('genieacs.refresh', ['id' => $id, 'server_id' => $serverIdForDevice]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-1" title="Summon (Refresh)">
                                                <i class="fa-solid fa-bolt"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{ route('genieacs.show', ['id' => $id, 'server_id' => $serverIdForDevice]) }}" class="fw-bold text-decoration-none me-2">
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
                                            @if(isset($device['_mstore_server_name']))
                                                <span class="badge bg-info-subtle text-info border border-info-subtle ms-2">
                                                    {{ $device['_mstore_server_name'] }}
                                                </span>
                                            @endif
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
        <a href="{{ route('genieacs.show', ['id' => $id, 'server_id' => $serverIdForDevice]) }}" class="text-decoration-none text-dark">
                                            {{ $sn }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                @if(($device['odp_name'] ?? '-') !== '-')
                                                    <span class="badge bg-info text-dark border border-info-subtle">{{ $device['odp_name'] }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </div>
                                            <button type="button" class="btn btn-link btn-sm p-0 ms-2 btn-assign-odp" 
                                                    data-sn="{{ $sn }}"
                                                    data-pppoe="{{ $pppoeUser }}"
                                                    data-odp-id="{{ $device['odp_id'] ?? '' }}"
                                                    title="{{ __('Assign/Change ODP') }}">
                                                <i class="fa-solid fa-pencil text-warning"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>{{ $ssid }}</td>
                                    <td class="text-center">
                                        @if(isset($displayCount) && $displayCount > 0)
                                            <span class="badge bg-success mb-1">{{ $displayCount }}</span>
                                            @if(isset($connectedMacs) && count($connectedMacs) > 0)
                                                <div class="d-flex flex-column gap-1">
                                                    @foreach($connectedMacs as $mac)
                                                        <span class="badge bg-light text-dark border border-secondary-subtle" style="font-size: 0.65em; font-family: monospace;">{{ $mac }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
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

    <!-- Assign ODP Modal -->
    <div class="modal fade" id="assignOdpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('genieacs.assign_odp') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Assign/Change ODP') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Device SN') }}</label>
                            <input type="text" class="form-control" name="sn" id="modalOdpSn" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('PPPoE Username') }}</label>
                            <input type="text" class="form-control" name="pppoe" id="modalOdpPppoe" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Select ODP') }}</label>
                            <select class="form-select select2-modal" name="odp_id" id="modalOdpSelect" required style="width: 100%;">
                                <option value="">{{ __('Select ODP...') }}</option>
                                @foreach($odps as $odp)
                                    <option value="{{ $odp->id }}">{{ $odp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="alert alert-info small">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            {{ __('This will update the Customer record in MStore linked to this SN/PPPoE.') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2 for ODP inside Modal
            $('.select2-modal').select2({
                dropdownParent: $('#assignOdpModal'),
                theme: 'bootstrap-5',
                placeholder: 'Select ODP'
            });

            // Handle Assign ODP Click
            $(document).on('click', '.btn-assign-odp', function() {
                var sn = $(this).data('sn');
                var pppoe = $(this).data('pppoe');
                var odpId = $(this).data('odp-id');
                
                $('#modalOdpSn').val(sn);
                $('#modalOdpPppoe').val(pppoe);
                
                // Set ODP Select
                var odpSelect = $('#modalOdpSelect');
                odpSelect.val(odpId).trigger('change');
                
                var modalEl = document.getElementById('assignOdpModal');
                // Use window.bootstrap if available, fallback to global bootstrap
                var bs = window.bootstrap || bootstrap;
                var modal = bs.Modal.getInstance(modalEl) || new bs.Modal(modalEl);
                modal.show();
            });
        });
    </script>
    @endpush
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('deviceSearch');
        const table = document.getElementById('genieacsDevicesTable');
        const refreshButton = document.getElementById('refreshButton');
        const perPageSelect = document.getElementById('perPageSelect');
        const serverSelect = document.getElementById('serverSelect');

        if (searchInput && table) {
            const tbody = table.querySelector('tbody');
            const rows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];

            searchInput.addEventListener('input', function () {
                const term = this.value.toLowerCase();

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = term === '' || text.includes(term) ? '' : 'none';
                });
            });
        }

        if (refreshButton) {
            refreshButton.addEventListener('click', function () {
                window.location.reload();
            });
        }

        if (perPageSelect) {
            perPageSelect.addEventListener('change', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', this.value);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            });
        }

        if (serverSelect) {
            serverSelect.addEventListener('change', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('server_id', this.value);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            });
        }
    });
</script>
@endpush
