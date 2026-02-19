@extends('layouts.app')

@section('title', 'Tagihan Saya')

@push('styles')
<style>
    .status-badge { text-transform: uppercase; }
    .table td, .table th { vertical-align: middle; }
    .snap-hidden { display: none; }
</style>
@endpush

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Tagihan Saya</h3>
        <a href="{{ route('client.dashboard') }}" class="btn btn-light"><i class="fa-solid fa-chevron-left me-1"></i> Dashboard</a>
    </div>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th class="text-end">Jumlah</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                        <tr>
                            <td>{{ $inv->code }}</td>
                            <td class="text-end">Rp {{ number_format($inv->amount, 0, ',', '.') }}</td>
                            <td>{{ optional($inv->due_date)->format('d M Y') ?? '-' }}</td>
                            <td>
                                <span class="badge status-badge {{ $inv->status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $inv->status }}</span>
                            </td>
                            <td class="text-end">
                                @if($inv->status === 'pending')
                                <form action="{{ route('client.invoices.pay', $inv) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-money-bill me-1"></i> Bayar Sekarang
                                    </button>
                                </form>
                                @else
                                <span class="text-muted">Sudah Dibayar</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center p-4 text-muted">Belum ada tagihan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

