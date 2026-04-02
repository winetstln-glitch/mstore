@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-bold">ID Card Absensi</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
            <button type="button" class="btn btn-success btn-sm" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm id-card-shell">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                <div class="col-md-7">
                    <div class="small text-uppercase text-muted fw-semibold">MSTORE Attendance Card</div>
                    <h4 class="fw-bold mb-1">{{ $user->name }}</h4>
                    <div class="text-muted mb-3">{{ $user->role->label ?? 'Staff' }}</div>
                    <div class="mb-2"><span class="text-muted">Username:</span> <strong>{{ $user->username ?: '-' }}</strong></div>
                    <div class="mb-2"><span class="text-muted">ID Card Code:</span> <strong>{{ $user->attendance_card_code }}</strong></div>
                    <div><span class="text-muted">Phone:</span> <strong>{{ $user->phone ?: '-' }}</strong></div>
                </div>
                <div class="col-md-5 text-center">
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($user->attendance_card_code) }}"
                        alt="QR {{ $user->attendance_card_code }}"
                        class="img-fluid border rounded p-2 bg-white mb-3"
                        style="max-width: 190px;">
                    <svg id="barcodeSvg"></svg>
                    <div class="small text-muted mt-2">{{ $user->attendance_card_code }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    JsBarcode('#barcodeSvg', @json($user->attendance_card_code), {
        format: 'CODE128',
        lineColor: '#111827',
        width: 2,
        height: 54,
        displayValue: false,
        margin: 0,
    });
});
</script>
@endpush
