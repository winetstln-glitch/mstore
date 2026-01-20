@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-info">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('olt.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <h5 class="mb-0 fw-bold">
                        {{ $olt->name }} - ONUs
                        <span class="badge bg-secondary-subtle text-secondary ms-2 rounded-pill">{{ $olt->onus->count() }} {{ __('devices') }}</span>
                    </h5>
                </div>
                
                <form action="{{ route('olt.onus.sync', $olt->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-sync me-1"></i> {{ __('Sync from OLT') }}
                    </button>
                </form>
            </div>

            <div class="card-body">
                {{-- Alerts handled by SweetAlert in Layout --}}

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-info-circle me-1"></i> {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3">ID</th>
                                <th scope="col">{{ __('Customer Name') }}</th>
                                <th scope="col">{{ __('MAC Address') }} / SN</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Rx Power') }}</th>
                                <th scope="col">{{ __('Down Reason') }}</th>
                                <th scope="col">{{ __('Uptime') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($onus as $onu)
                                <tr>
                                    <td class="ps-3 text-muted small">
                                        {{ $onu->interface ?? ('#' . $onu->id) }}
                                    </td>
                                    <td class="fw-medium text-body">
                                        {{ $onu->name ?? '-' }}
                                    </td>
                                    <td class="text-muted small font-monospace">
                                        @if($onu->mac_address)
                                            {{ $onu->mac_address }}
                                        @elseif($onu->serial_number)
                                            {{ $onu->serial_number }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-body">
                                        @php
                                            $statusClass = match($onu->status) {
                                                'online' => 'bg-success-subtle text-success border-success-subtle',
                                                'los' => 'bg-danger-subtle text-danger border-danger-subtle',
                                                'power_fail' => 'bg-warning-subtle text-warning border-warning-subtle',
                                                default => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                            };
                                            $statusLabel = match($onu->status) {
                                                'online' => __('Online'),
                                                'los' => __('LOS'),
                                                'power_fail' => __('Power Fail'),
                                                default => __('Offline')
                                            };
                                        @endphp
                                        <span class="badge border {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="text-body">
                                        @php
                                            $signalValue = null;
                                            if ($onu->signal !== null && $onu->signal !== '') {
                                                $signalValue = is_numeric($onu->signal) ? (float) $onu->signal : null;
                                            }
                                            $rxClass = 'text-muted';
                                            if ($signalValue !== null) {
                                                if ($signalValue <= -27) {
                                                    $rxClass = 'text-danger';
                                                } elseif ($signalValue <= -23) {
                                                    $rxClass = 'text-warning';
                                                } else {
                                                    $rxClass = 'text-success';
                                                }
                                            }
                                        @endphp
                                        @if($signalValue === null)
                                            <span class="text-muted">-</span>
                                        @else
                                            <span class="{{ $rxClass }}">
                                                <i class="fa-solid fa-signal me-1"></i>{{ number_format($signalValue, 4) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-body">
                                        @php
                                            $reasonLabel = $onu->status === 'online' ? __('Normal') : __('Unknown');
                                            $reasonClass = $onu->status === 'online'
                                                ? 'text-success'
                                                : 'text-muted';
                                        @endphp
                                        <span class="{{ $reasonClass }}">
                                            <i class="fa-regular fa-circle-dot me-1"></i>{{ $reasonLabel }}
                                        </span>
                                    </td>
                                    <td class="text-body">
                                        @php
                                            $uptimeText = '-';
                                            if ($onu->status === 'online' && $onu->updated_at) {
                                                $diff = now()->diff($onu->updated_at);
                                                $days = $diff->days;
                                                $hours = $diff->h;
                                                $minutes = $diff->i;
                                                $parts = [];
                                                if ($days > 0) {
                                                    $parts[] = $days . 'd';
                                                }
                                                if ($hours > 0 || $days > 0) {
                                                    $parts[] = $hours . 'j';
                                                }
                                                $parts[] = $minutes . 'm';
                                                $uptimeText = implode(' ', $parts);
                                            }
                                        @endphp
                                        {{ $uptimeText }}
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-secondary" disabled>
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" disabled>
                                                <i class="fa-solid fa-power-off"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" disabled>
                                                <i class="fa-solid fa-eye-slash"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" disabled>
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-body-secondary">
                                        <div class="mb-2"><i class="fa-solid fa-network-wired fa-2x opacity-25"></i></div>
                                        {{ __('No ONUs found. Click "Sync from OLT" to fetch devices.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $onus->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
