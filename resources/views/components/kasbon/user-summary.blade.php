@props([
    'user',
    'totalKasbonBiasa',
    'totalPinjamanAktif',
    'totalCicilan',
    'sisaPinjaman',
])

<div class="card h-100 shadow-sm border-0 transition-all duration-200 hover:translate-y-[-2px] hover:shadow-md">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <div class="p-3 rounded-circle bg-primary-subtle text-primary me-3">
                <i class="fas fa-user-tie fs-4"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-semibold">{{ $user->name }}</h5>
                <small class="text-muted">{{ $user->role->name ?? 'Teknisi' }}</small>
            </div>
        </div>
        
        <div class="row g-2">
            <div class="col-12">
                <div class="border rounded p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Kasbon</small>
                        <x-kasbon.money :amount="$totalKasbonBiasa" />
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="border rounded p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Loan Aktif</small>
                        <x-kasbon.money :amount="$totalPinjamanAktif" />
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="border rounded p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Sudah Dicicil</small>
                        <x-kasbon.money :amount="$totalCicilan" />
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="border rounded p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Outstanding</small>
                        <span class="fw-bold text-danger">
                            Rp {{ number_format($sisaPinjaman, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>