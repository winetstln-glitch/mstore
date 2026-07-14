@extends('layouts.app')

@section('title', __('Wedding & Event Dashboard'))

@section('content')
<div class="container-fluid">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Total Booking</div>
                    <div class="fs-3">{{ $stats['total_booking'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Booking Bulan Ini</div>
                    <div class="fs-3">{{ $stats['booking_this_month'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Pendapatan</div>
                    <div class="fs-3">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Acara Hari Ini</div>
                    <div class="fs-3">{{ $stats['events_today'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Acara Minggu Ini</div>
                    <div class="fs-3">{{ $stats['events_this_week'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Pembayaran Pending</div>
                    <div class="fs-3">{{ $stats['pending_payments'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="fw-semibold">Chart Data</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="fw-semibold mb-2">Booking Bulanan</div>
                            <pre class="mb-0">{{ json_encode($charts['booking_by_month'], JSON_PRETTY_PRINT) }}</pre>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-2">Pendapatan Bulanan</div>
                            <pre class="mb-0">{{ json_encode($charts['revenue_by_month'], JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

