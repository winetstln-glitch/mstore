@extends('layouts.app')

@section('title', __('CCTV Report'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">CCTV Report</h4>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-danger" href="{{ route('reports.cctv.pdf', request()->query()) }}">PDF</a>
            <a class="btn btn-outline-success" href="{{ route('reports.cctv.excel', request()->query()) }}">Excel</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form class="row g-2">
                <div class="col-md-4">
                    <label class="form-label mb-1">From</label>
                    <input class="form-control" name="from" value="{{ request('from', $from) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">To</label>
                    <input class="form-control" name="to" value="{{ request('to', $to) }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Total Booking</div>
                    <div class="fs-3">{{ $summary['total_booking'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Revenue</div>
                    <div class="fs-3">Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Pending Payment</div>
                    <div class="fs-3">{{ $summary['pending_payment'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Top Paket</div>
                <div class="card-body">
                    <pre class="mb-0">{{ json_encode($topPackages, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Booking</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Pelanggan</th>
                                    <th>WA</th>
                                    <th>Alamat</th>
                                    <th>Paket</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bookings as $b)
                                    <tr>
                                        <td>{{ $b->booking_number }}</td>
                                        <td>{{ $b->customer_name }}</td>
                                        <td>{{ $b->customer_whatsapp }}</td>
                                        <td>{{ $b->address }}</td>
                                        <td>{{ $b->package?->name }}</td>
                                        <td>{{ $b->status }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

