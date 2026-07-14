<div>
    <div class="mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h4 class="fw-bold text-primary mb-1">PPPoE Users</h4>
                <p class="text-muted small mb-0">Kelola pelanggan PPPoE dengan mudah.</p>
            </div>

            <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end align-items-center">
                @if(count($selectedIds) > 0)
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-warning btn-sm" wire:click="bulkSuspend">
                            <i class="fa-solid fa-pause me-1"></i> Suspend Terpilih
                        </button>
                        <button type="button" class="btn btn-success btn-sm" wire:click="bulkActivate">
                            <i class="fa-solid fa-play me-1"></i> Aktifkan Terpilih
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" wire:click="bulkDelete" onclick="return confirm('Yakin ingin menghapus pelanggan terpilih?')">
                            <i class="fa-solid fa-trash me-1"></i> Hapus Terpilih
                        </button>
                    </div>
                @endif

                <a href="{{ route('pppoe-users.create') }}" class="btn btn-primary flex-grow-0">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Pelanggan
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-search text-muted"></i>
                        </span>
                        <input type="text" wire:model.live="search" class="form-control border-start-0 ps-0" placeholder="Cari nama, username, atau telepon...">
                    </div>
                </div>

                <div class="col-md-3">
                    <select wire:model.live="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="suspend">Suspend</option>
                        <option value="terminated">Berhenti</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select wire:model.live="routerId" class="form-select">
                        <option value="">Semua Router</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                                </div>
                            </th>
                            <th>Nama Pelanggan</th>
                            <th>Username</th>
                            <th>Paket</th>
                            <th>Router</th>
                            <th>Status</th>
                            <th>Online/Offline</th>
                            <th>Tanggal Aktif</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" wire:model.live="selectedIds" value="{{ $customer->id }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $customer->name }}</div>
                                    <div class="text-muted small">{{ $customer->phone }}</div>
                                </td>
                                <td>
                                    <span class="font-monospace">{{ $customer->pppoe_user }}</span>
                                </td>
                                <td>{{ $customer->package }}</td>
                                <td>{{ $customer->router?->name ?? '-' }}</td>
                                <td>
                                    @if($customer->status === 'active')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                    @elseif($customer->status === 'suspend')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Suspend</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Berhenti</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">-</span>
                                </td>
                                <td>{{ $customer->created_at->format('d M Y') }}</td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('pppoe-users.show', $customer) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('pppoe-users.edit', $customer) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        @if($customer->status === 'active')
                                            <button type="button" class="btn btn-sm btn-outline-warning" wire:click="suspend({{ $customer->id }})" title="Suspend">
                                                <i class="fa-solid fa-pause"></i>
                                            </button>
                                        @elseif($customer->status === 'suspend')
                                            <button type="button" class="btn btn-sm btn-outline-success" wire:click="activate({{ $customer->id }})" title="Aktifkan">
                                                <i class="fa-solid fa-play"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Customer360">
                                            <i class="fa-solid fa-chart-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="mb-3">
                                        <i class="fa-solid fa-users-slash fa-2x opacity-25"></i>
                                    </div>
                                    Tidak ada pelanggan PPPoE.
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="px-4 py-3 border-top">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
