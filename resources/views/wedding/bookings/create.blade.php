@extends('layouts.app')

@section('title', __('Tambah Booking Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Tambah Booking Wedding</h4>
        <a href="{{ route('wedding.bookings.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('wedding.bookings.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Pelanggan</label>
                        <input name="customer_name" class="form-control" value="{{ old('customer_name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">WhatsApp</label>
                        <input name="customer_whatsapp" class="form-control" value="{{ old('customer_whatsapp') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Acara</label>
                        <input name="event_date" type="date" class="form-control" value="{{ old('event_date') }}" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Lokasi</label>
                        <input name="location" class="form-control" value="{{ old('location') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Paket</label>
                        <select name="wedding_package_id" class="form-select" required>
                            @foreach($packages as $p)
                                <option value="{{ $p->id }}" {{ old('wedding_package_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} - Rp {{ number_format((int) $p->price, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <input name="status" class="form-control" value="{{ old('status', 'pending') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quotation</label>
                        <input name="quotation_amount" type="number" class="form-control" value="{{ old('quotation_amount') }}" min="0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
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

