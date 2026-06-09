@extends('layouts.app')

@section('title', __('Assign Teknisi CCTV'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Assign Teknisi: {{ $booking->booking_number }}</h4>
        <a href="{{ route('cctv.bookings.show', $booking) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('cctv.installations.store', $booking) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Teknisi</label>
                        <select name="technician_id" class="form-select">
                            <option value="">-</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Scheduled At</label>
                        <input name="scheduled_at" type="datetime-local" class="form-control" value="{{ old('scheduled_at') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <input name="status" class="form-control" value="{{ old('status', 'scheduled') }}">
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

