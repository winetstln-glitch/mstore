@extends('layouts.app')

@section('title', __('Detail Booking Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Detail Booking: {{ $booking->booking_number }}</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('wedding.bookings.edit', $booking) }}" class="btn btn-outline-primary">Edit</a>
            <a href="{{ route('wedding.payments.create', $booking) }}" class="btn btn-primary">Buat Pembayaran</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div><span class="fw-semibold">Pelanggan:</span> {{ $booking->customer_name }}</div>
                    <div><span class="fw-semibold">WhatsApp:</span> {{ $booking->customer_whatsapp }}</div>
                    <div><span class="fw-semibold">Tanggal:</span> {{ $booking->event_date?->toDateString() }}</div>
                    <div><span class="fw-semibold">Lokasi:</span> {{ $booking->location }}</div>
                    <div><span class="fw-semibold">Paket:</span> {{ $booking->package?->name }}</div>
                    <div><span class="fw-semibold">Status:</span> {{ $booking->status }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Pembayaran</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tipe</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th class="text-end">QRIS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($booking->payments as $p)
                                    <tr>
                                        <td>{{ $p->type }}</td>
                                        <td>Rp {{ number_format((int) $p->amount, 0, ',', '.') }}</td>
                                        <td>{{ $p->status }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('wedding.payments.show', $p) }}">Lihat</a>
                                        </td>
                                    </tr>
                                @endforeach
                                @if($booking->payments->isEmpty())
                                    <tr><td colspan="4" class="text-muted">Belum ada pembayaran.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Catatan</div>
                <div class="card-body">
                    {{ $booking->notes ?: '-' }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

