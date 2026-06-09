@extends('layouts.app')

@section('title', __('Jadwal Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Jadwal Acara</h4>
        <a href="{{ route('wedding.bookings.index') }}" class="btn btn-outline-secondary">Ke Booking</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form class="row g-2">
                <div class="col-md-4">
                    <label class="form-label mb-1">From</label>
                    <input type="date" class="form-control" name="from" value="{{ request('from', $from) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">To</label>
                    <input type="date" class="form-control" name="to" value="{{ request('to', $to) }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>No Booking</th>
                            <th>Paket</th>
                            <th>Pelanggan</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $e)
                            <tr>
                                <td>{{ $e->event_date?->toDateString() }}</td>
                                <td><a href="{{ route('wedding.bookings.show', $e) }}">{{ $e->booking_number }}</a></td>
                                <td>{{ $e->package?->name }}</td>
                                <td>{{ $e->customer_name }}</td>
                                <td>{{ $e->location }}</td>
                                <td>{{ $e->status }}</td>
                            </tr>
                        @endforeach
                        @if($events->isEmpty())
                            <tr><td colspan="6" class="text-muted">Tidak ada event pada rentang tanggal ini.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

