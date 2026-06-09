@extends('layouts.app')

@section('content')
<div class="container-xxl">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h4 class="mb-1">Riwayat Reward</h4>
            <div class="text-muted">Daftar redeem voucher reward pada transaksi wash</div>
        </div>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control" placeholder="Cari kode / plat / no transaksi" value="{{ $q }}">
            <button class="btn btn-primary" type="submit">Cari</button>
            <a class="btn btn-outline-secondary" href="{{ route('wash.loyalty.redemptions') }}">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Kode Voucher</th>
                            <th>Plat</th>
                            <th>No Transaksi</th>
                            <th class="text-end">Nilai</th>
                            <th>Redeemed At</th>
                            <th>Kasir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($redemptions as $r)
                            <tr>
                                <td class="fw-semibold">{{ $r->voucher?->code ?? '-' }}</td>
                                <td class="fw-semibold">{{ $r->voucher?->vehicle_plate ?? '-' }}</td>
                                <td>
                                    @if($r->transaction)
                                        <a href="{{ route('wash.transactions.show', $r->transaction) }}">{{ $r->transaction->transaction_number ?? ('#'.$r->transaction->id) }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">Rp {{ number_format((int) ($r->amount ?? 0), 0, ',', '.') }}</td>
                                <td>{{ $r->redeemed_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                <td>{{ $r->redeemedBy?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada redeem.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $redemptions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

