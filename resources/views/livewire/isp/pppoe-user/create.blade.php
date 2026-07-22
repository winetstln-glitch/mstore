<div>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 px-3 px-lg-0">
                <div class="card shadow-sm border-0 border-top border-4 border-primary">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-body-emphasis fs-6 fs-md-5">Tambah Pelanggan PPPoE</h5>
                        <a href="{{ route('isp.pppoe-users.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>

                    <div class="card-body p-3 p-md-4">
                        <form wire:submit="save">
                            @if(session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            <h6 class="fw-bold text-body-secondary text-uppercase small mb-3">Identitas Pelanggan</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label small text-muted fw-bold">Nama Lengkap</label>
                                    <input type="text" wire:model="name" id="name" class="form-control @error('name') is-invalid @enderror" required placeholder="Masukkan nama lengkap">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label small text-muted fw-bold">Nomor HP</label>
                                    <input type="tel" wire:model="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="0812...">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label small text-muted fw-bold">Email</label>
                                    <input type="email" wire:model="email" id="email" class="form-control @error('email') is-invalid @enderror" placeholder="email@example.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="address" class="form-label small text-muted fw-bold">Alamat</label>
                                    <textarea wire:model="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="Masukkan alamat lengkap"></textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <h6 class="fw-bold text-body-secondary text-uppercase small mb-3 border-top pt-3">Login Internet</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="pppoeUser" class="form-label small text-muted fw-bold">Username PPPoE</label>
                                    <input type="text" wire:model="pppoeUser" id="pppoeUser" class="form-control @error('pppoeUser') is-invalid @enderror" required placeholder="username_pppoe">
                                    @error('pppoeUser')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="pppoePassword" class="form-label small text-muted fw-bold">Password PPPoE</label>
                                    <input type="text" wire:model="pppoePassword" id="pppoePassword" class="form-control @error('pppoePassword') is-invalid @enderror" required placeholder="password_pppoe">
                                    @error('pppoePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <h6 class="fw-bold text-body-secondary text-uppercase small mb-3 border-top pt-3">Login Portal</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12 mb-2">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" wire:model.live="createPortalUser" class="form-check-input" id="createPortalUser">
                                        <label class="form-check-label fw-bold" for="createPortalUser">Buat Akun Portal untuk Pelanggan Ini</label>
                                    </div>
                                </div>

                                @if($createPortalUser)
                                    <div class="col-md-4">
                                        <label for="customerId" class="form-label small text-muted fw-bold">ID Pelanggan</label>
                                        <input type="text" wire:model="customerId" id="customerId" class="form-control @error('customerId') is-invalid @enderror" required>
                                        @error('customerId')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-8">
                                        <label for="portalPassword" class="form-label small text-muted fw-bold">Password Portal</label>
                                        <input type="password" wire:model="portalPassword" id="portalPassword" class="form-control @error('portalPassword') is-invalid @enderror" required placeholder="minimal 8 karakter">
                                        @error('portalPassword')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                            </div>

                            <h6 class="fw-bold text-body-secondary text-uppercase small mb-3 border-top pt-3">Paket</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="routerId" class="form-label small text-muted fw-bold">Router</label>
                                    <select wire:model="routerId" id="routerId" class="form-select @error('routerId') is-invalid @enderror" required>
                                        <option value="">Pilih Router</option>
                                        @foreach($routers as $router)
                                            <option value="{{ $router->id }}">{{ $router->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('routerId')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="packageId" class="form-label small text-muted fw-bold">Paket Internet</label>
                                    <select wire:model="packageId" id="packageId" class="form-select @error('packageId') is-invalid @enderror" required>
                                        <option value="">Pilih Paket</option>
                                        @foreach($packages as $package)
                                            <option value="{{ $package->id }}">{{ $package->name }} @if($package->price) - Rp {{ number_format($package->price, 0, ',', '.') }} @endif</option>
                                        @endforeach
                                    </select>
                                    @error('packageId')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <h6 class="fw-bold text-body-secondary text-uppercase small mb-3 border-top pt-3">Aktivasi</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="status" class="form-label small text-muted fw-bold">Status</label>
                                    <select wire:model="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                        <option value="active">Aktif</option>
                                        <option value="suspend">Suspend</option>
                                        <option value="terminated">Berhenti</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="activationDate" class="form-label small text-muted fw-bold">Tanggal Aktif</label>
                                    <input type="date" wire:model="activationDate" id="activationDate" class="form-control @error('activationDate') is-invalid @enderror" required>
                                    @error('activationDate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <h6 class="fw-bold text-body-secondary text-uppercase small mb-3 border-top pt-3">Catatan</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label for="notes" class="form-label small text-muted fw-bold">Notes</label>
                                    <textarea wire:model="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Catatan tambahan..."></textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex flex-column-reverse flex-md-row justify-content-end gap-2 border-top pt-4">
                                <a href="{{ route('isp.pppoe-users.index') }}" class="btn btn-outline-secondary w-100 w-md-auto">Batal</a>
                                <button type="submit" class="btn btn-primary w-100 w-md-auto px-4">
                                    <span wire:loading.remove>Simpan Pelanggan</span>
                                    <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    <span wire:loading>Menyimpan...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>