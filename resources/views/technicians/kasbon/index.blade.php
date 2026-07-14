@extends('layouts.app')

@section('content')
@php
    // Hitung Summary Data
    $totalKasbonPending = 0;
    $totalBonusPending = 0;
    $totalPinjamanAktif = 0;
    $totalOutstanding = 0;
    $totalCicilanDibayar = 0;
    $teknisiMemilikiKasbon = 0;
    $loanSelesai = 0;

    foreach ($recap as $r) {
        $totalKasbonPending += $r['total_kasbon_biasa'];
        $totalPinjamanAktif += $r['total_pinjaman_aktif'];
        $totalCicilanDibayar += $r['total_cicilan'];
        $totalOutstanding += $r['sisa_pinjaman'];
        if ($r['total_kasbon_biasa'] > 0 || $r['total_pinjaman_aktif'] > 0) {
            $teknisiMemilikiKasbon++;
        }
    }
    
    foreach ($loans as $loan) {
        if ($loan->status === 'closed') {
            $loanSelesai++;
        }
    }
    
    // Hitung total bonus pending
    foreach ($bonusAdjustments as $bonus) {
        if ($bonus->status === 'pending') {
            $totalBonusPending += $bonus->amount;
        }
    }
@endphp

<div class="container-fluid">
    {{-- Page Header --}}
    <div class="mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-coins text-warning me-2"></i>
                    {{ __('Rincian Kasbon Teknisi') }}
                </h2>
                <p class="mb-0 text-muted small">
                    {{ __('Kelola kasbon dan pinjaman teknisi dengan mudah') }}
                </p>
            </div>
            @if(Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', 'hrd-manager', 'hrd manager', 'hrd', 'manager hrd', 'direktur', 'owner', 'owner pendiri']))
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addBonusModal">
                        <i class="fas fa-plus me-1"></i>
                        {{ __('Tambah Bonus') }}
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKasbonModal">
                        <i class="fas fa-plus me-1"></i>
                        {{ __('Tambah Kasbon Biasa') }}
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addKasbonLoanModal">
                        <i class="fas fa-plus me-1"></i>
                        {{ __('Tambah Kasbon Angsuran') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Dashboard Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <x-kasbon.summary-card 
                icon="fa-gift"
                title="Total Bonus Pending"
                :value="'Rp ' . number_format($totalBonusPending, 0, ',', '.')"
                :subtitle="$bonusAdjustments->total() . ' Bonus'"
                border-color="success"
                bg-color="success"
            />
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <x-kasbon.summary-card 
                icon="fa-money-bill-wave"
                title="Total Kasbon Pending"
                :value="'Rp ' . number_format($totalKasbonPending, 0, ',', '.')"
                :subtitle="$kasbonAdjustments->total() . ' Kasbon'"
                border-color="warning"
                bg-color="warning"
            />
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <x-kasbon.summary-card 
                icon="fa-file-invoice-dollar"
                title="Total Pinjaman Aktif"
                :value="'Rp ' . number_format($totalPinjamanAktif, 0, ',', '.')"
                :subtitle="$loans->where('status', 'active')->count() . ' Pinjaman'"
                border-color="danger"
                bg-color="danger"
            />
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <x-kasbon.summary-card 
                icon="fa-hand-holding-dollar"
                title="Total Outstanding"
                :value="'Rp ' . number_format($totalOutstanding, 0, ',', '.')"
                :subtitle="'Total Hutang'"
                border-color="danger"
                bg-color="danger"
            />
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <x-kasbon.summary-card 
                icon="fa-coins"
                title="Total Cicilan Dibayar"
                :value="'Rp ' . number_format($totalCicilanDibayar, 0, ',', '.')"
                :subtitle="'Total Pembayaran'"
                border-color="success"
                bg-color="success"
            />
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <x-kasbon.summary-card 
                icon="fa-users"
                title="Teknisi Memiliki Kasbon"
                :value="$teknisiMemilikiKasbon"
                :subtitle="'Teknisi Aktif'"
                border-color="primary"
                bg-color="primary"
            />
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <x-kasbon.summary-card 
                icon="fa-check-circle"
                title="Loan Selesai"
                :value="$loanSelesai"
                :subtitle="'Pinjaman Lunas'"
                border-color="success"
                bg-color="success"
            />
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-filter text-muted me-2"></i>
                <h6 class="fw-semibold mb-0">{{ __('Filter Data') }}</h6>
            </div>
            <form action="{{ route('technicians.kasbon.index') }}" method="GET" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <label class="form-label small text-muted mb-1">{{ __('Cari Teknisi...') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="searchTechnician" placeholder="{{ __('Cari Teknisi...') }}">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <label class="form-label small text-muted mb-1">{{ __('Filter Teknisi') }}</label>
                        <select name="user_id" class="form-select" id="filterTechnician">
                            <option value="">{{ __('Semua Teknisi') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-12">
                        <label class="form-label small text-muted mb-1">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('Semua') }}</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Belum Diproses') }}</option>
                            <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>{{ __('Sudah Diproses') }}</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-12">
                        <label class="form-label small text-muted mb-1">{{ __('Tanggal Mulai') }}</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-12">
                        <label class="form-label small text-muted mb-1">{{ __('Tanggal Selesai') }}</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3 justify-content-end">
                    <a href="{{ route('technicians.kasbon.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-rotate-left me-1"></i>{{ __('Reset') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>{{ __('Terapkan') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Rekap Teknisi Card Grid --}}
    <div class="mb-4">
        <div class="d-flex align-items-center mb-3">
            <i class="fas fa-chart-pie text-muted me-2"></i>
            <h5 class="fw-semibold mb-0">{{ __('Rekap per Teknisi') }}</h5>
        </div>
        <div class="row g-3" id="userCards">
            @foreach($recap as $item)
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 user-card" data-name="{{ strtolower($item['user']->name) }}">
                    <x-kasbon.user-summary 
                        :user="$item['user']"
                        :total-kasbon-biasa="$item['total_kasbon_biasa']"
                        :total-pinjaman-aktif="$item['total_pinjaman_aktif']"
                        :total-cicilan="$item['total_cicilan']"
                        :sisa-pinjaman="$item['sisa_pinjaman']"
                    />
                </div>
            @endforeach
        </div>
    </div>

    {{-- Kasbon Biasa Section --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-file-invoice-dollar text-muted me-2"></i>
                <h5 class="fw-semibold mb-0">{{ __('Kasbon Biasa (Sekali Potong)') }}</h5>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">{{ __('No') }}</th>
                            <th class="fw-semibold">{{ __('Teknisi') }}</th>
                            <th class="fw-semibold">{{ __('Tanggal') }}</th>
                            <th class="fw-semibold">{{ __('Jumlah') }}</th>
                            <th class="fw-semibold">{{ __('Keterangan') }}</th>
                            <th class="fw-semibold">{{ __('Status') }}</th>
                            <th class="fw-semibold text-end pe-3">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">
                        @forelse($kasbonAdjustments as $index => $adjustment)
                            <tr class="user-row">
                                <td>{{ $kasbonAdjustments->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="fw-semibold">{{ $adjustment->user->name }}</div>
                                    </div>
                                </td>
                                <td class="small text-muted">
                                    {{ $adjustment->date->translatedFormat('d M Y') }}
                                </td>
                                <td>
                                    <span class="fw-bold text-danger">
                                        Rp {{ number_format($adjustment->amount, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>{{ $adjustment->description ?? '-' }}</td>
                                <td>
                                    <x-kasbon.status-badge :status="$adjustment->status" />
                                </td>
                                <td class="text-end pe-3">
                                    @if(Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', 'hrd-manager', 'hrd manager', 'hrd', 'manager hrd', 'direktur', 'owner', 'owner pendiri']))
                                        @if($adjustment->status !== 'processed')
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editKasbonModal-{{ $adjustment->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('salary-adjustments.destroy', $adjustment) }}" method="POST" class="d-inline" data-no-loading="true" data-confirm="{{ __('Hapus kasbon ini?') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-kasbon.empty-state 
                                        icon="fa-inbox"
                                        title="{{ __('Tidak ada data kasbon biasa') }}"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($kasbonAdjustments->hasPages())
                <div class="card-footer bg-white border-top-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <small class="text-muted">
                            {{ __('Menampilkan') }} {{ $kasbonAdjustments->firstItem() }}–{{ $kasbonAdjustments->lastItem() }} {{ __('dari') }} {{ $kasbonAdjustments->total() }} {{ __('data') }}
                        </small>
                        <div>
                            {{ $kasbonAdjustments->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Bonus Section --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-gift text-muted me-2"></i>
                <h5 class="fw-semibold mb-0">{{ __('Bonus Teknisi') }}</h5>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">{{ __('No') }}</th>
                            <th class="fw-semibold">{{ __('Teknisi') }}</th>
                            <th class="fw-semibold">{{ __('Tanggal') }}</th>
                            <th class="fw-semibold">{{ __('Jumlah') }}</th>
                            <th class="fw-semibold">{{ __('Keterangan') }}</th>
                            <th class="fw-semibold">{{ __('Status') }}</th>
                            <th class="fw-semibold text-end pe-3">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">
                        @forelse($bonusAdjustments as $index => $bonus)
                            <tr class="user-row">
                                <td>{{ $bonusAdjustments->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="fw-semibold">{{ $bonus->user->name }}</div>
                                    </div>
                                </td>
                                <td class="small text-muted">
                                    {{ $bonus->date->translatedFormat('d M Y') }}
                                </td>
                                <td>
                                    <span class="fw-bold text-success">
                                        Rp {{ number_format($bonus->amount, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>{{ $bonus->description ?? '-' }}</td>
                                <td>
                                    <x-kasbon.status-badge :status="$bonus->status" />
                                </td>
                                <td class="text-end pe-3">
                                    @if(Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', 'hrd-manager', 'hrd manager', 'hrd', 'manager hrd', 'direktur', 'owner', 'owner pendiri']))
                                        @if($bonus->status !== 'processed')
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editBonusModal-{{ $bonus->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('salary-adjustments.destroy', $bonus) }}" method="POST" class="d-inline" data-no-loading="true" data-confirm="{{ __('Hapus bonus ini?') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-kasbon.empty-state 
                                        icon="fa-inbox"
                                        title="{{ __('Tidak ada data bonus') }}"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($bonusAdjustments->hasPages())
                <div class="card-footer bg-white border-top-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <small class="text-muted">
                            {{ __('Menampilkan') }} {{ $bonusAdjustments->firstItem() }}–{{ $bonusAdjustments->lastItem() }} {{ __('dari') }} {{ $bonusAdjustments->total() }} {{ __('data') }}
                        </small>
                        <div>
                            {{ $bonusAdjustments->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Kasbon Angsuran Section --}}
    <div class="mb-4">
        <div class="d-flex align-items-center mb-3">
            <i class="fas fa-credit-card text-muted me-2"></i>
            <h5 class="fw-semibold mb-0">{{ __('Kasbon Angsuran') }}</h5>
        </div>
        @forelse($loans as $loan)
            @php
                $totalPaid = $loan->installments->sum('amount');
                $remaining = $loan->remaining;
                $percentage = $loan->principal_amount > 0 ? min(100, ($totalPaid / $loan->principal_amount) * 100) : 0;
            @endphp
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 rounded-circle bg-primary-subtle text-primary">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">{{ $loan->user->name }}</h6>
                                <small class="text-muted">{{ $loan->start_date->translatedFormat('d M Y') }}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <x-kasbon.status-badge :status="$loan->status" text="{{ $loan->status === 'active' ? 'Aktif' : 'Selesai' }}" />
                            @if(Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', 'hrd-manager', 'hrd manager', 'hrd', 'manager hrd', 'direktur', 'owner', 'owner pendiri']))
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editKasbonLoanModal-{{ $loan->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addInstallmentModal-{{ $loan->id }}">
                                        <i class="fas fa-receipt me-1"></i>{{ __('Tambah Cicilan') }}
                                    </button>
                                    <form action="{{ route('kasbon-loans.destroy', $loan) }}" method="POST" class="d-inline" data-no-loading="true">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Hapus kasbon angsuran ini?') }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3">
                                <small class="text-muted d-block">{{ __('Pokok Pinjaman') }}</small>
                                <h5 class="fw-bold mb-0">
                                    Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}
                                </h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3">
                                <small class="text-muted d-block">{{ __('Outstanding') }}</small>
                                <h5 class="fw-bold mb-0 text-danger">
                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                </h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3">
                                <small class="text-muted d-block">{{ __('Tenor') }}</small>
                                <h5 class="fw-bold mb-0">
                                    {{ $loan->tenor_months ? $loan->tenor_months . ' ' . __('bulan') : '-' }}
                                </h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3">
                                <small class="text-muted d-block">{{ __('Cicilan Bulanan') }}</small>
                                <h5 class="fw-bold mb-0">
                                    {{ $loan->monthly_installment ? 'Rp ' . number_format($loan->monthly_installment, 0, ',', '.') : '-' }}
                                </h5>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-4">
                        <small class="text-muted d-block mb-1">{{ __('Progress Pinjaman') }}</small>
                        <x-kasbon.progress 
                            :value="$totalPaid"
                            :max="$loan->principal_amount"
                            color="success"
                        />
                    </div>

                    {{-- Riwayat Cicilan Timeline --}}
                    @if($loan->installments->count() > 0)
                        <hr class="my-3">
                        <h6 class="fw-semibold mb-3">
                            <i class="fas fa-history text-muted me-2"></i>
                            {{ __('Riwayat Cicilan') }}
                        </h6>
                        <div class="timeline">
                            @foreach($loan->installments as $installment)
                                <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                                    <div class="flex-shrink-0">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width: 36px; height: 36px;">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap justify-content-between gap-2">
                                            <div>
                                                <div class="fw-semibold">{{ $installment->date->translatedFormat('d M Y') }}</div>
                                                <div class="small text-muted">
                                                    {{ $installment->description ?? __('Cicilan') }}
                                                    @if($installment->salary_adjustment_id)
                                                        <span class="badge bg-success-subtle text-success ms-1">{{ __('Potong Gaji') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="fw-bold text-danger">
                                                Rp {{ number_format($installment->amount, 0, ',', '.') }}
                                            </div>
                                        </div>
                                        @if(Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', 'hrd-manager', 'hrd manager', 'hrd', 'manager hrd', 'direktur', 'owner', 'owner pendiri']))
                                            <div class="d-flex justify-content-end mt-1">
                                                <form action="{{ route('kasbon-loans.installments.destroy', [$loan, $installment]) }}" method="POST" data-no-loading="true">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Hapus cicilan ini?') }}')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <x-kasbon.empty-state 
                icon="fa-inbox"
                title="{{ __('Tidak ada data kasbon angsuran') }}"
            />
        @endforelse
    </div>
</div>

{{-- All Modals --}}
{{-- Modal Tambah Kasbon Biasa --}}
<div class="modal fade" id="addKasbonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle bg-primary-subtle text-primary me-2">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h5 class="modal-title mb-0 fw-semibold">{{ __('Tambah Kasbon Biasa') }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($errors->any() && old('type') === 'kasbon')
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('salary-adjustments.store') }}" method="POST" id="addKasbonForm" data-no-loading="true">
                    @csrf
                    <input type="hidden" name="type" value="kasbon">
                    <div class="mb-3">
                        <label for="add_user_id" class="form-label fw-semibold">{{ __('Teknisi') }}</label>
                        <select name="user_id" id="add_user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">{{ __('Pilih Teknisi') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_amount" class="form-label fw-semibold">{{ __('Jumlah') }}</label>
                        <input type="number" name="amount" id="add_amount" class="form-control @error('amount') is-invalid @enderror" required min="1" value="{{ old('amount') }}">
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_date" class="form-label fw-semibold">{{ __('Tanggal') }}</label>
                        <input type="date" name="date" id="add_date" class="form-control @error('date') is-invalid @enderror" required value="{{ old('date', date('Y-m-d')) }}">
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_description" class="form-label fw-semibold">{{ __('Keterangan') }}</label>
                        <textarea name="description" id="add_description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                <button type="submit" class="btn btn-primary" form="addKasbonForm">{{ __('Simpan') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah Kasbon Angsuran --}}
<div class="modal fade" id="addKasbonLoanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle bg-success-subtle text-success me-2">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h5 class="modal-title mb-0 fw-semibold">{{ __('Tambah Kasbon Angsuran') }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($errors->any() && !old('type'))
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('kasbon-loans.store') }}" method="POST" id="addKasbonLoanForm" data-no-loading="true">
                    @csrf
                    <div class="mb-3">
                        <label for="add_loan_user_id" class="form-label fw-semibold">{{ __('Teknisi') }}</label>
                        <select name="user_id" id="add_loan_user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">{{ __('Pilih Teknisi') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_principal_amount" class="form-label fw-semibold">{{ __('Pokok Pinjaman') }}</label>
                        <input type="number" name="principal_amount" id="add_principal_amount" class="form-control @error('principal_amount') is-invalid @enderror" required min="1" value="{{ old('principal_amount') }}">
                        @error('principal_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_start_date" class="form-label fw-semibold">{{ __('Tanggal Mulai') }}</label>
                        <input type="date" name="start_date" id="add_start_date" class="form-control @error('start_date') is-invalid @enderror" required value="{{ old('start_date', date('Y-m-d')) }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_tenor_months" class="form-label fw-semibold">{{ __('Tenor (bulan, opsional)') }}</label>
                        <input type="number" name="tenor_months" id="add_tenor_months" class="form-control @error('tenor_months') is-invalid @enderror" min="1" value="{{ old('tenor_months') }}">
                        @error('tenor_months')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_monthly_installment" class="form-label fw-semibold">{{ __('Cicilan Bulanan (opsional)') }}</label>
                        <input type="number" name="monthly_installment" id="add_monthly_installment" class="form-control @error('monthly_installment') is-invalid @enderror" min="0" value="{{ old('monthly_installment') }}">
                        @error('monthly_installment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_loan_description" class="form-label fw-semibold">{{ __('Keterangan') }}</label>
                        <textarea name="description" id="add_loan_description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                <button type="submit" class="btn btn-success" form="addKasbonLoanForm">{{ __('Simpan') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah Bonus --}}
<div class="modal fade" id="addBonusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle bg-success-subtle text-success me-2">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h5 class="modal-title mb-0 fw-semibold">{{ __('Tambah Bonus') }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($errors->any() && old('type') === 'bonus')
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('salary-adjustments.store') }}" method="POST" id="addBonusForm" data-no-loading="true">
                    @csrf
                    <input type="hidden" name="type" value="bonus">
                    <div class="mb-3">
                        <label for="add_bonus_user_id" class="form-label fw-semibold">{{ __('Teknisi') }}</label>
                        <select name="user_id" id="add_bonus_user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">{{ __('Pilih Teknisi') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_bonus_amount" class="form-label fw-semibold">{{ __('Jumlah') }}</label>
                        <input type="number" name="amount" id="add_bonus_amount" class="form-control @error('amount') is-invalid @enderror" required min="1" value="{{ old('amount') }}">
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_bonus_date" class="form-label fw-semibold">{{ __('Tanggal') }}</label>
                        <input type="date" name="date" id="add_bonus_date" class="form-control @error('date') is-invalid @enderror" required value="{{ old('date', date('Y-m-d')) }}">
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_bonus_description" class="form-label fw-semibold">{{ __('Keterangan') }}</label>
                        <textarea name="description" id="add_bonus_description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                <button type="submit" class="btn btn-warning" form="addBonusForm">{{ __('Simpan') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Kasbon Biasa --}}
@foreach($kasbonAdjustments as $adjustment)
    <div class="modal fade" id="editKasbonModal-{{ $adjustment->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-circle bg-primary-subtle text-primary me-2">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h5 class="modal-title mb-0 fw-semibold">{{ __('Edit Kasbon Biasa') }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('salary-adjustments.update', $adjustment) }}" method="POST" id="editKasbonForm-{{ $adjustment->id }}" data-no-loading="true">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('Teknisi') }}</label>
                            <input type="text" class="form-control" value="{{ $adjustment->user->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit_amount_{{ $adjustment->id }}" class="form-label fw-semibold">{{ __('Jumlah') }}</label>
                            <input type="number" name="amount" id="edit_amount_{{ $adjustment->id }}" class="form-control" required min="1" value="{{ $adjustment->amount }}">
                        </div>
                        <div class="mb-3">
                            <label for="edit_date_{{ $adjustment->id }}" class="form-label fw-semibold">{{ __('Tanggal') }}</label>
                            <input type="date" name="date" id="edit_date_{{ $adjustment->id }}" class="form-control" required value="{{ $adjustment->date->format('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label for="edit_description_{{ $adjustment->id }}" class="form-label fw-semibold">{{ __('Keterangan') }}</label>
                            <textarea name="description" id="edit_description_{{ $adjustment->id }}" class="form-control" rows="3">{{ $adjustment->description }}</textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary" form="editKasbonForm-{{ $adjustment->id }}">{{ __('Simpan') }}</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

{{-- Edit Bonus --}}
@foreach($bonusAdjustments as $bonus)
    <div class="modal fade" id="editBonusModal-{{ $bonus->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-circle bg-success-subtle text-success me-2">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h5 class="modal-title mb-0 fw-semibold">{{ __('Edit Bonus') }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('salary-adjustments.update', $bonus) }}" method="POST" id="editBonusForm-{{ $bonus->id }}" data-no-loading="true">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('Teknisi') }}</label>
                            <input type="text" class="form-control" value="{{ $bonus->user->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit_bonus_amount_{{ $bonus->id }}" class="form-label fw-semibold">{{ __('Jumlah') }}</label>
                            <input type="number" name="amount" id="edit_bonus_amount_{{ $bonus->id }}" class="form-control" required min="1" value="{{ $bonus->amount }}">
                        </div>
                        <div class="mb-3">
                            <label for="edit_bonus_date_{{ $bonus->id }}" class="form-label fw-semibold">{{ __('Tanggal') }}</label>
                            <input type="date" name="date" id="edit_bonus_date_{{ $bonus->id }}" class="form-control" required value="{{ $bonus->date->format('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label for="edit_bonus_description_{{ $bonus->id }}" class="form-label fw-semibold">{{ __('Keterangan') }}</label>
                            <textarea name="description" id="edit_bonus_description_{{ $bonus->id }}" class="form-control" rows="3">{{ $bonus->description }}</textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-warning" form="editBonusForm-{{ $bonus->id }}">{{ __('Simpan') }}</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

{{-- Edit Kasbon Angsuran dan Tambah Cicilan --}}
@foreach($loans as $loan)
    @php
        $totalPaid = $loan->installments->sum('amount');
        $remaining = $loan->remaining;
    @endphp
    <div class="modal fade" id="editKasbonLoanModal-{{ $loan->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-circle bg-success-subtle text-success me-2">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h5 class="modal-title mb-0 fw-semibold">{{ __('Edit Kasbon Angsuran') }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('kasbon-loans.update', $loan) }}" method="POST" id="editKasbonLoanForm-{{ $loan->id }}" data-no-loading="true">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('Teknisi') }}</label>
                            <input type="text" class="form-control" value="{{ $loan->user->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit_principal_amount_{{ $loan->id }}" class="form-label fw-semibold">{{ __('Pokok Pinjaman') }}</label>
                            <input type="number" name="principal_amount" id="edit_principal_amount_{{ $loan->id }}" class="form-control" required min="1" value="{{ $loan->principal_amount }}">
                        </div>
                        <div class="mb-3">
                            <label for="edit_start_date_{{ $loan->id }}" class="form-label fw-semibold">{{ __('Tanggal Mulai') }}</label>
                            <input type="date" name="start_date" id="edit_start_date_{{ $loan->id }}" class="form-control" required value="{{ $loan->start_date->format('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label for="edit_tenor_months_{{ $loan->id }}" class="form-label fw-semibold">{{ __('Tenor (bulan)') }}</label>
                            <input type="number" name="tenor_months" id="edit_tenor_months_{{ $loan->id }}" class="form-control" min="1" value="{{ $loan->tenor_months }}">
                        </div>
                        <div class="mb-3">
                            <label for="edit_monthly_installment_{{ $loan->id }}" class="form-label fw-semibold">{{ __('Cicilan Bulanan (opsional)') }}</label>
                            <input type="number" name="monthly_installment" id="edit_monthly_installment_{{ $loan->id }}" class="form-control" min="0" value="{{ $loan->monthly_installment }}">
                        </div>
                        <div class="mb-3">
                            <label for="edit_loan_description_{{ $loan->id }}" class="form-label fw-semibold">{{ __('Keterangan') }}</label>
                            <textarea name="description" id="edit_loan_description_{{ $loan->id }}" class="form-control" rows="3">{{ $loan->description }}</textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-success" form="editKasbonLoanForm-{{ $loan->id }}">{{ __('Simpan') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tambah Cicilan --}}
    <div class="modal fade" id="addInstallmentModal-{{ $loan->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-circle bg-info-subtle text-info me-2">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <h5 class="modal-title mb-0 fw-semibold">{{ __('Tambah Cicilan') }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Ringkasan --}}
                    <div class="mb-4 border rounded p-3 bg-light">
                        <h6 class="fw-semibold mb-2">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            {{ __('Ringkasan') }}
                        </h6>
                        <div class="row g-2">
                            <div class="col-sm-4">
                                <small class="text-muted d-block">{{ __('Teknisi') }}</small>
                                <div class="fw-semibold">{{ $loan->user->name }}</div>
                            </div>
                            <div class="col-sm-4">
                                <small class="text-muted d-block">{{ __('Pokok') }}</small>
                                <div class="fw-bold">
                                    Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <small class="text-muted d-block">{{ __('Outstanding') }}</small>
                                <div class="fw-bold text-danger">
                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <small class="text-muted d-block">{{ __('Sudah Dibayar') }}</small>
                                <div class="fw-bold text-success">
                                    Rp {{ number_format($totalPaid, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <small class="text-muted d-block">{{ __('Progress') }}</small>
                                <div class="fw-bold">
                                    {{ number_format(($loan->principal_amount > 0 ? ($totalPaid / $loan->principal_amount) * 100 : 0), 0) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('kasbon-loans.installments.store', $loan) }}" method="POST" id="addInstallmentForm-{{ $loan->id }}" data-no-loading="true">
                        @csrf
                        <div class="mb-3">
                            <label for="installment_amount_{{ $loan->id }}" class="form-label fw-semibold">{{ __('Jumlah') }}</label>
                            <input type="number" name="amount" id="installment_amount_{{ $loan->id }}" class="form-control" required min="1" max="{{ $remaining }}">
                        </div>
                        <div class="mb-3">
                            <label for="installment_date_{{ $loan->id }}" class="form-label fw-semibold">{{ __('Tanggal') }}</label>
                            <input type="date" name="date" id="installment_date_{{ $loan->id }}" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label for="installment_description_{{ $loan->id }}" class="form-label fw-semibold">{{ __('Keterangan') }}</label>
                            <textarea name="description" id="installment_description_{{ $loan->id }}" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="potong_gaji" id="potong_gaji_{{ $loan->id }}" class="form-check-input" checked>
                                <label for="potong_gaji_{{ $loan->id }}" class="form-check-label fw-semibold">{{ __('Potong via Gaji') }}</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-success" form="addInstallmentForm-{{ $loan->id }}">{{ __('Simpan') }}</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('styles')
<style>
    .timeline .border-bottom:last-child {
        border-bottom: none !important;
        padding-bottom: 0 !important;
        margin-bottom: 0 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto open modal if there are validation errors
        @if ($errors->any() && old('type') === 'kasbon')
            const addKasbonModal = new bootstrap.Modal(document.getElementById('addKasbonModal'));
            addKasbonModal.show();
        @elseif ($errors->any() && !old('type'))
            const addKasbonLoanModal = new bootstrap.Modal(document.getElementById('addKasbonLoanModal'));
            addKasbonLoanModal.show();
        @endif
        
        // Debounced search
        const searchInput = document.getElementById('searchTechnician');
        const userCards = document.querySelectorAll('.user-card');
        
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', function(e) {
                clearTimeout(debounceTimer);
                const searchTerm = e.target.value.toLowerCase().trim();
                debounceTimer = setTimeout(() => {
                    userCards.forEach(card => {
                        const cardName = card.getAttribute('data-name');
                        if (searchTerm.length === 0 || cardName.includes(searchTerm)) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                }, 300);
            });
        }
    });
</script>
@endpush