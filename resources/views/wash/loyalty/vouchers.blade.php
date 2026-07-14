@extends('layouts.app')

@section('content')
<div class="container-xxl">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h4 class="mb-1">Voucher Reward</h4>
            <div class="text-muted">Voucher hasil loyalty: Available / Used / Expired</div>
        </div>
        <form method="GET" class="d-flex flex-wrap gap-2">
            <select name="status" class="form-select" style="width: 180px;">
                <option value="">Semua Status</option>
                <option value="available" @selected($status === 'available')>Available</option>
                <option value="used" @selected($status === 'used')>Used</option>
                <option value="expired" @selected($status === 'expired')>Expired</option>
            </select>
            <input type="text" name="q" class="form-control" placeholder="Cari kode / plat / nama / no HP" value="{{ $q }}">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('wash.loyalty.vouchers') }}">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Customer</th>
                            <th>Plat</th>
                            <th>Reward</th>
                            <th>Status</th>
                            <th>Expired</th>
                            <th>Used</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $v)
                            @php
                                $isExpired = $v->status === 'available' && $v->expires_at && $v->expires_at->isPast();
                                $statusText = $isExpired ? 'expired' : $v->status;
                                $badge = $statusText === 'available' ? 'success' : ($statusText === 'used' ? 'primary' : 'secondary');
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $v->code }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $v->customer?->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $v->customer?->phone ?? '' }}</div>
                                </td>
                                <td class="fw-semibold">{{ $v->vehicle_plate }}</td>
                                <td>{{ $v->reward_type }}</td>
                                <td><span class="badge bg-{{ $badge }}">{{ strtoupper($statusText) }}</span></td>
                                <td>{{ $v->expires_at?->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ $v->used_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    @can('wash.reward.manage')
                                        <a href="{{ route('wash.loyalty.vouchers.edit', $v) }}" class="btn btn-sm btn-primary">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Belum ada voucher.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $vouchers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

