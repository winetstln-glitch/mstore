@extends('layouts.app')

@section('content')
<div class="container-xxl">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Loyalty Counter</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('wash.loyalty.update', $counter) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Plat Nomor</label>
                    <input type="text" class="form-control" value="{{ $counter->vehicle_plate }}" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Customer</label>
                    <input type="text" class="form-control" value="{{ $counter->customer?->name ?? '-' }}" readonly>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Transaksi Siklus Saat Ini</label>
                        <input type="number" class="form-control" name="cycle_paid_count" value="{{ $counter->cycle_paid_count }}" required min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Transaksi Seumur Hidup</label>
                        <input type="number" class="form-control" name="lifetime_paid_count" value="{{ $counter->lifetime_paid_count }}" required min="0">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('wash.loyalty.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection