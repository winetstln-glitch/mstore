@extends('layouts.app')

@section('title', __('Edit Booking CCTV'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Booking CCTV</h4>
        <a href="{{ route('cctv.bookings.show', $booking) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('cctv.bookings.update', $booking) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Pelanggan</label>
                        <input name="customer_name" class="form-control" value="{{ old('customer_name', $booking->customer_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">WhatsApp</label>
                        <input name="customer_whatsapp" class="form-control" value="{{ old('customer_whatsapp', $booking->customer_whatsapp) }}" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="3" required>{{ old('address', $booking->address) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Paket</label>
                        <select name="cctv_package_id" class="form-select" required>
                            @foreach($packages as $p)
                                <option value="{{ $p->id }}" {{ old('cctv_package_id', $booking->cctv_package_id) == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} - Rp {{ number_format((int) $p->price, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <input name="status" class="form-control" value="{{ old('status', $booking->status) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quotation</label>
                        <input name="quotation_amount" type="number" class="form-control" value="{{ old('quotation_amount', $booking->quotation_amount) }}" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">DP</label>
                        <input name="dp_amount" type="number" class="form-control" value="{{ old('dp_amount', $booking->dp_amount) }}" min="0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $booking->notes) }}</textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

