@extends('layouts.app')

@section('title', __('Buat Pembayaran Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Buat Pembayaran: {{ $booking->booking_number }}</h4>
        <a href="{{ route('wedding.bookings.show', $booking) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('wedding.payments.store', $booking) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select" required>
                            <option value="dp">DP</option>
                            <option value="final">Pelunasan</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jumlah (opsional)</label>
                        <input name="amount" type="number" class="form-control" value="{{ old('amount') }}" min="1000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email (opsional)</label>
                        <input name="email" type="email" class="form-control" value="{{ old('email') }}">
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary" type="submit">Generate QRIS</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

