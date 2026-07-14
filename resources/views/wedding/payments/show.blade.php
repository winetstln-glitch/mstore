@extends('layouts.app')

@section('title', __('Detail Pembayaran Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Pembayaran Wedding</h4>
        <a href="{{ route('wedding.bookings.show', $payment->booking) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div><span class="fw-semibold">Booking:</span> {{ $payment->booking?->booking_number }}</div>
                    <div><span class="fw-semibold">Tipe:</span> {{ $payment->type }}</div>
                    <div><span class="fw-semibold">Jumlah:</span> Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</div>
                    <div><span class="fw-semibold">Status:</span> {{ $payment->status }}</div>
                    <div><span class="fw-semibold">Paid At:</span> {{ $payment->paid_at?->toDateTimeString() ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">QRIS</div>
                <div class="card-body">
                    @if($payment->paymentTransaction?->qr_url)
                        <img src="{{ $payment->paymentTransaction->qr_url }}" alt="QRIS" class="img-fluid">
                        <div class="mt-2 text-muted">Ref: {{ $payment->paymentTransaction->reference_id }}</div>
                    @else
                        <div class="text-muted">QRIS belum tersedia.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

