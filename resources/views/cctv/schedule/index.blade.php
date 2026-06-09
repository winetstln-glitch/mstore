@extends('layouts.app')

@section('title', __('Jadwal Teknisi CCTV'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Jadwal Teknisi</h4>
        <a href="{{ route('cctv.bookings.index') }}" class="btn btn-outline-secondary">Ke Booking</a>
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

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Kalender Instalasi (List)</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Scheduled</th>
                                    <th>Booking</th>
                                    <th>Teknisi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($installations as $i)
                                    <tr>
                                        <td>{{ $i->scheduled_at?->toDateTimeString() ?? '-' }}</td>
                                        <td><a href="{{ route('cctv.bookings.show', $i->booking) }}">{{ $i->booking?->booking_number }}</a></td>
                                        <td>{{ $i->technician?->name ?? '-' }}</td>
                                        <td>{{ $i->status }} ({{ $i->progress_percent }}%)</td>
                                    </tr>
                                @endforeach
                                @if($installations->isEmpty())
                                    <tr><td colspan="4" class="text-muted">Tidak ada jadwal pada rentang tanggal ini.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Booking Belum Dijadwalkan</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach($unscheduledBookings as $b)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="{{ route('cctv.bookings.show', $b) }}">{{ $b->booking_number }}</a>
                                <span class="badge bg-secondary">{{ $b->status }}</span>
                            </li>
                        @endforeach
                        @if($unscheduledBookings->isEmpty())
                            <li class="list-group-item text-muted">Tidak ada.</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

