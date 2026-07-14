@extends('layouts.app')

@section('content')
<div class="container-xxl">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h4 class="mb-1">Laporan Loyalty</h4>
            <div class="text-muted">Ringkasan voucher diterbitkan/digunakan dan top loyal customer</div>
        </div>
        <form method="GET" class="d-flex gap-2">
            <select name="range" class="form-select" style="width: 180px;">
                <option value="7" @selected($days === 7)>7 hari</option>
                <option value="30" @selected($days === 30)>30 hari</option>
                <option value="90" @selected($days === 90)>90 hari</option>
                <option value="180" @selected($days === 180)>180 hari</option>
            </select>
            <button class="btn btn-primary" type="submit">Terapkan</button>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Total Pelanggan Loyalty</div>
                    <div class="fs-4 fw-bold">{{ number_format($totalCounters, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Voucher Aktif</div>
                    <div class="fs-4 fw-bold">{{ number_format($activeVouchers, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Voucher Digunakan</div>
                    <div class="fs-4 fw-bold">{{ number_format($usedVouchers, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Voucher Kadaluarsa</div>
                    <div class="fs-4 fw-bold">{{ number_format($expiredVouchers, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Periode {{ $from->format('d M Y') }} - {{ now()->format('d M Y') }}</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Voucher diterbitkan</span>
                        <span class="fw-semibold">{{ number_format($issuedInRange, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Voucher digunakan</span>
                        <span class="fw-semibold">{{ number_format($redeemedInRange, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Top Loyal Customer</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Plat</th>
                                    <th class="text-end">Total Berbayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topLoyal as $c)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $c->customer?->name ?? '-' }}</div>
                                            <div class="text-muted small">{{ $c->customer?->phone ?? '' }}</div>
                                        </td>
                                        <td class="fw-semibold">{{ $c->vehicle_plate }}</td>
                                        <td class="text-end">{{ number_format((int) ($c->lifetime_paid_count ?? 0), 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Belum ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

