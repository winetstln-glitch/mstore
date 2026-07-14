@extends('layouts.app')

@section('title', __('Booking Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Booking Wedding</h4>
        <a href="{{ route('wedding.bookings.create') }}" class="btn btn-primary">Tambah Booking</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form class="row g-2">
                <div class="col-md-4">
                    <input class="form-control" name="q" placeholder="Cari booking / pelanggan / WA" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <input class="form-control" name="status" placeholder="Status" value="{{ request('status') }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Pelanggan</th>
                            <th>WA</th>
                            <th>Tanggal</th>
                            <th>Paket</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $b)
                            <tr>
                                <td><a href="{{ route('wedding.bookings.show', $b) }}">{{ $b->booking_number }}</a></td>
                                <td>{{ $b->customer_name }}</td>
                                <td>{{ $b->customer_whatsapp }}</td>
                                <td>{{ $b->event_date?->toDateString() }}</td>
                                <td>{{ $b->package?->name }}</td>
                                <td>{{ $b->status }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('wedding.bookings.edit', $b) }}">Edit</a>
                                    <form action="{{ route('wedding.bookings.destroy', $b) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $bookings->links() }}
        </div>
    </div>
</div>
@endsection

