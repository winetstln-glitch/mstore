<div>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold text-primary mb-1">PPPoE Users</h4>
                    <p class="text-muted small mb-0">Daftar pelanggan PPPoE</p>
                </div>
                <a href="{{ route('isp.pppoe-users.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Pelanggan
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" wire:model.live="search" class="form-control" placeholder="Cari nama, username, atau nomor HP...">
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
                    <div class="col-md-2">
                        <button type="button" wire:click="bulkSuspend" class="btn btn-outline-warning w-100" @if(empty($selectedIds)) disabled @endif>
                            <i class="fa-solid fa-pause me-1"></i> Bulk Suspend
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="w-1">
                                    <input type="checkbox" wire:model.live="selectAll" class="form-check-input">
                                </th>
                                <th>Nama Pelanggan</th>
                                <th>Username PPPoE</th>
                                <th>Router</th>
                                <th>Paket</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                                <tr>
                                    <td>
                                        <input type="checkbox" wire:model.live="selectedIds" value="{{ $customer->id }}" class="form-check-input">
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $customer->name }}</div>
                                        <div class="text-muted small">{{ $customer->phone ?? '-' }}</div>
                                    </td>
                                    <td class="font-monospace">{{ $customer->pppoe_user }}</td>
                                    <td>{{ $customer->router?->name ?? '-' }}</td>
                                    <td>{{ $customer->package }}</td>
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
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('isp.pppoe-users.show', $customer) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="{{ route('isp.pppoe-users.edit', $customer) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            @if($customer->status === 'active')
                                                <button type="button" wire:click="suspend({{ $customer->id }})" class="btn btn-sm btn-outline-warning">
                                                    <i class="fa-solid fa-pause"></i>
                                                </button>
                                            @elseif($customer->status === 'suspend')
                                                <button type="button" wire:click="activate({{ $customer->id }})" class="btn btn-sm btn-outline-success">
                                                    <i class="fa-solid fa-play"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $customers->links() }}
            </div>
        </div>
    </div>
</div>