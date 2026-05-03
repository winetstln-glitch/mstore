@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-warning">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Manajemen Pemasangan') }}</h5>
                    <div class="toolbar-scroll">
                        <a href="{{ route('installations.create') }}" class="btn btn-primary" data-bs-toggle="tooltip" title="{{ __('Tambah Pemasangan') }}">
                            <i class="fa-solid fa-plus"></i> <span class="d-none d-sm-inline ms-1">{{ __('Tambah Pemasangan') }}</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Search and Filter -->
                <form method="GET" action="{{ route('installations.index') }}" class="w-100 mb-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-2">
                            <select name="status" class="form-select">
                                <option value="">{{ __('Semua Status') }}</option>
                                @foreach(['registered', 'survey', 'approved', 'installation', 'completed', 'cancelled'] as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ __(ucfirst($status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <select name="technician_id" class="form-select">
                                <option value="">{{ __('Semua Teknisi') }}</option>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>
                                        {{ $tech->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <select name="coordinator_id" class="form-select">
                                <option value="">{{ __('All Pengurus') }}</option>
                                @foreach(($coordinators ?? collect()) as $coordinator)
                                    <option value="{{ $coordinator->id }}" {{ request('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                        {{ $coordinator->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-12 col-md-4">
                            <div class="input-group">
                                <span class="input-group-text  border-end-0"><i class="fa-solid fa-search text-body-secondary"></i></span>
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="{{ __('Cari pelanggan...') }}">
                            </div>
                        </div>
                        <div class="col-12 col-md-2">
                            <input type="date" name="date" value="{{ request('date') }}" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-dark w-100">{{ __('Terapkan') }}</button>
                        </div>
                        <div class="col-12 col-md-2">
                            <a href="{{ route('installations.index') }}" class="btn btn-outline-secondary w-100">{{ __('Reset Filter') }}</a>
                        </div>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-responsive-mobile">
                        <thead class="">
                            <tr>
                                <th scope="col" class="ps-3">ID</th>
                                <th scope="col">{{ __('Pelanggan') }}</th>
                                <th scope="col">SN</th>
                                <th scope="col">MAC</th>
                                <th scope="col">{{ __('Tanggal Rencana') }}</th>
                                <th scope="col">{{ __('Teknisi') }}</th>
                                <th scope="col">{{ __('Pengurus') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($installations as $installation)
                                <tr>
                                    <td class="ps-3 text-body-secondary fw-medium">#{{ $installation->id }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $installation->customer->name }}</div>
                                        <div class="small text-body-secondary">{{ Str::limit($installation->customer->address, 30) }}</div>
                                    </td>
                                    <td class="text-body-secondary">{{ $installation->serial_number ?: ($installation->customer->onu_serial ?: '-') }}</td>
                                    <td class="text-body-secondary">{{ $installation->mac_address ?: ($installation->customer->wan_mac ?: '-') }}</td>
                                    <td>
                                        <div>{{ $installation->plan_date ? $installation->plan_date->translatedFormat('d M Y') : __('Belum Diatur') }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $installation->technician ? $installation->technician->name : __('Belum Ditugaskan') }}</div>
                                        @if($installation->modemRecord && $installation->modemRecord->user)
                                            <div class="small text-success mt-1" title="{{ __('Teknisi Pendata Modem') }}">
                                                <i class="fa-solid fa-microchip me-1"></i>{{ $installation->modemRecord->user->name }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ optional($ticketCoordinatorsByCustomer->get($installation->customer_id))->name ?: '-' }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($installation->status) {
                                                'completed' => 'bg-success-subtle text-success border-success-subtle',
                                                'cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
                                                'installation' => 'bg-primary-subtle text-primary border-primary-subtle',
                                                'survey' => 'bg-warning-subtle text-warning border-warning-subtle',
                                                'approved' => 'bg-info-subtle text-info border-info-subtle',
                                                default => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                            };
                                        @endphp
                                        <span class="badge border {{ $statusClass }}">
                                            {{ __(ucfirst($installation->status)) }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('installations.show', $installation) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Lihat') }}">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="{{ route('installations.edit', $installation) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Ubah') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <form action="{{ route('installations.destroy', $installation) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Yakin ingin menghapus data pemasangan ini?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger ms-1" title="{{ __('Hapus') }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-body-secondary">
                                        <div class="mb-2"><i class="fa-solid fa-network-wired fa-2x opacity-25"></i></div>
                                        {{ __('Tidak ada data pemasangan.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($installations instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-4">
                    {{ $installations->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
