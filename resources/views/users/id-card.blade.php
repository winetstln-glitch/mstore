@extends('layouts.app')

@section('title', 'ID Card User')

@section('content')
<div class="container py-3 employee-id-card-page">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 employee-id-toolbar">
        <h5 class="mb-0 fw-bold">ID Card User</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i>Kembali
            </a>
            <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fa-regular fa-id-card me-1"></i>Print Preview
            </a>
            <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="employee-print-sheet is-preview">
        <x-id-card :user="$user" :employee="$employee" :brandName="$brandName" :logoUrl="$logoUrl" :brandSlogan="$brandSlogan" :brandKey="$brandKey" :idCardCode="$idCardCode" />
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.barcode-svg').forEach((el) => {
        const code = el.dataset.code || '';
        if (!code || typeof JsBarcode === 'undefined') {
            return;
        }

        JsBarcode(el, code, {
            format: 'CODE128',
            lineColor: '#111827',
            width: 2,
            height: 26,
            displayValue: false,
            margin: 0,
        });
    });
});
</script>
@endpush

@push('styles')
<x-id-card-styles />
@endpush
