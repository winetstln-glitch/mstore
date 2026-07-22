<div>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $customer->name }}</h5>
                            <div class="text-muted small">
                                {{ $customer->pppoe_user }} • {{ $customer->router?->name ?? '-' }} • {{ $customer->package }}
                            </div>
                        </div>
                        <div class="btn-group flex-wrap gap-2">
                            @if($customer->status === 'active')
                                <button type="button" class="btn btn-warning btn-sm" wire:click="suspend">
                                    <i class="fa-solid fa-pause me-1"></i> Suspend
                                </button>
                            @elseif($customer->status === 'suspend')
                                <button type="button" class="btn btn-success btn-sm" wire:click="activate">
                                    <i class="fa-solid fa-play me-1"></i> Aktifkan
                                </button>
                            @endif
                            <a href="{{ route('isp.pppoe-users.edit', $customer) }}" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                            </a>
                            <a href="{{ route('isp.pppoe-users.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <ul class="nav nav-tabs mb-4" id="pppoeTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'identity' ? 'active' : '' }}" wire:click="setTab('identity')" type="button">Identitas</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'billing' ? 'active' : '' }}" wire:click="setTab('billing')" type="button">Billing Ringkas</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'history' ? 'active' : '' }}" wire:click="setTab('history')" type="button">Riwayat</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'customer360' ? 'active' : '' }}" wire:click="setTab('customer360')" type="button">Customer360</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="pppoeTabsContent">
                            @if($activeTab === 'identity')
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-3">Informasi Pribadi</h6>
                                        <div class="p-3 rounded border">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4">Nama Lengkap</dt>
                                                <dd class="col-sm-8">{{ $customer->name }}</dd>

                                                <dt class="col-sm-4">Status</dt>
                                                <dd class="col-sm-8">
                                                    @if($customer->status === 'active')
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                                    @elseif($customer->status === 'suspend')
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Suspend</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Berhenti</span>
                                                    @endif
                                                </dd>

                                                <dt class="col-sm-4">Nomor HP</dt>
                                                <dd class="col-sm-8">{{ $customer->phone ?? '-' }}</dd>

                                                <dt class="col-sm-4">Email</dt>
                                                <dd class="col-sm-8">{{ $customer->email ?? '-' }}</dd>

                                                <dt class="col-sm-4">Alamat</dt>
                                                <dd class="col-sm-8">{{ $customer->address ?? '-' }}</dd>
                                            </dl>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-3">Informasi Layanan</h6>
                                        <div class="p-3 rounded border">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4">Username PPPoE</dt>
                                                <dd class="col-sm-8 font-monospace">{{ $customer->pppoe_user }}</dd>

                                                <dt class="col-sm-4">Password PPPoE</dt>
                                                <dd class="col-sm-8 font-monospace">{{ $customer->pppoe_password }}</dd>

                                                <dt class="col-sm-4">Router</dt>
                                                <dd class="col-sm-8">{{ $customer->router?->name ?? '-' }}</dd>

                                                <dt class="col-sm-4">Paket</dt>
                                                <dd class="col-sm-8">{{ $customer->package }}</dd>

                                                <dt class="col-sm-4">Tanggal Aktif</dt>
                                                <dd class="col-sm-8">{{ $customer->created_at->format('d M Y') }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            @elseif($activeTab === 'billing')
                                <div class="text-center py-5 text-muted">
                                    <div class="mb-3">
                                        <i class="fa-solid fa-file-invoice-dollar fa-2x opacity-25"></i>
                                    </div>
                                    Fitur Billing Ringkas akan segera tersedia.
                                </div>
                            @elseif($activeTab === 'history')
                                <div class="text-center py-5 text-muted">
                                    <div class="mb-3">
                                        <i class="fa-solid fa-clock-rotate-left fa-2x opacity-25"></i>
                                    </div>
                                    Fitur Riwayat akan segera tersedia.
                                </div>
                            @elseif($activeTab === 'customer360')
                                <div class="text-center py-5 text-muted">
                                    <div class="mb-3">
                                        <i class="fa-solid fa-chart-line fa-2x opacity-25"></i>
                                    </div>
                                    Fitur Customer360 akan segera tersedia.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>