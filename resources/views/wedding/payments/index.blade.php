@extends('layouts.app')

@section('title', __('Pembayaran Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Pembayaran Wedding</h4>
        <a href="{{ route('wedding.bookings.index') }}" class="btn btn-outline-secondary">Ke Booking</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Tipe</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $p)
                            <tr>
                                <td><a href="{{ route('wedding.bookings.show', $p->booking) }}">{{ $p->booking?->booking_number }}</a></td>
                                <td>{{ $p->type }}</td>
                                <td>Rp {{ number_format((int) $p->amount, 0, ',', '.') }}</td>
                                <td>{{ $p->status }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('wedding.payments.show', $p) }}">Lihat</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection

