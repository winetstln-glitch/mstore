@extends('layouts.app')

@section('title', __('Buat Survey CCTV'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Buat Survey: {{ $booking->booking_number }}</h4>
        <a href="{{ route('cctv.bookings.show', $booking) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('cctv.surveys.store', $booking) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Survey</label>
                        <input name="survey_date" type="datetime-local" class="form-control" value="{{ old('survey_date') }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Lokasi</label>
                        <input name="location" class="form-control" value="{{ old('location', $booking->address) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="completed">Selesai</option>
                        </select>
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

