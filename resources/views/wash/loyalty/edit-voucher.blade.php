@extends('layouts.app')

@section('content')
<div class="container-xxl">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Voucher Reward</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('wash.loyalty.vouchers.update', $voucher) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Kode Voucher</label>
                    <input type="text" class="form-control" value="{{ $voucher->code }}" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Plat Nomor</label>
                    <input type="text" class="form-control" value="{{ $voucher->vehicle_plate }}" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Customer</label>
                    <input type="text" class="form-control" value="{{ $voucher->customer?->name ?? '-' }}" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" required>
                        <option value="available" @selected($voucher->status === 'available')>Available</option>
                        <option value="used" @selected($voucher->status === 'used')>Used</option>
                        <option value="expired" @selected($voucher->status === 'expired')>Expired</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal Kadaluarsa</label>
                    <input type="date" class="form-control" name="expires_at" value="{{ $voucher->expires_at?->format('Y-m-d') }}">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('wash.loyalty.vouchers') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection