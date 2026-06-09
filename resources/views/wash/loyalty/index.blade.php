@extends('layouts.app')

@section('content')
<div class="container-xxl">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h4 class="mb-1">Loyalty Program</h4>
            <div class="text-muted">Skema: 10x cuci berbayar → voucher gratis 1x cuci</div>
        </div>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control" placeholder="Cari plat / nama / no HP" value="{{ $q }}">
            <button class="btn btn-primary" type="submit">Cari</button>
            <a class="btn btn-outline-secondary" href="{{ route('wash.loyalty.index') }}">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Plat</th>
                            <th>Progress</th>
                            <th class="text-end">Total Berbayar</th>
                            <th class="text-end">Voucher Aktif</th>
                            <th>Terakhir Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($counters as $counter)
                            @php
                                $cycle = (int) ($counter->cycle_paid_count ?? 0);
                                $progress = $cycle % $target;
                                if ($progress === 0) {
                                    $progress = 0;
                                }
                                $pct = $target > 0 ? (int) round(($progress / $target) * 100) : 0;
                                $voucherActive = (int) ($voucherCounts[$counter->vehicle_plate] ?? 0);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $counter->customer?->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $counter->customer?->phone ?? '' }}</div>
                                </td>
                                <td class="fw-semibold">{{ $counter->vehicle_plate }}</td>
                                <td style="min-width: 200px;">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ $progress }} / {{ $target }}</span>
                                        <span>{{ $target - $progress }} lagi</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%"></div>
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format((int) ($counter->lifetime_paid_count ?? 0), 0, ',', '.') }}</td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $voucherActive > 0 ? 'success' : 'secondary' }}">{{ $voucherActive }}</span>
                                </td>
                                <td>{{ $counter->last_paid_at?->format('d-m-Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada data loyalty.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $counters->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

