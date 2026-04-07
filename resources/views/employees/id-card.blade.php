@extends('layouts.app')

@section('title', 'ID Card Karyawan')

@section('content')
<div class="container py-3 employee-id-card-page">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 employee-id-toolbar">
        <h5 class="mb-0 fw-bold">ID Card Karyawan</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i>Kembali
            </a>
            <a href="{{ request()->url() }}?print=1" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fa-regular fa-id-card me-1"></i>Print Preview
            </a>
            <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i>Print
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-lg employee-id-card-screen">
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-lg-7 p-4">
                    <div class="small text-uppercase fw-semibold text-secondary">MSTORE Employee Card</div>
                    <h4 class="fw-bold mb-1">{{ $employee->full_name }}</h4>
                    <div class="text-muted mb-3">{{ $employee->department }} - {{ $employee->position }}</div>
                    <div class="mb-2"><span class="text-muted">ID Card:</span> <strong>{{ $idCardCode }}</strong></div>
                    <div class="mb-2"><span class="text-muted">NIK:</span> <strong>{{ $employee->nik }}</strong></div>
                    <div><span class="text-muted">No HP:</span> <strong>{{ $employee->phone }}</strong></div>
                </div>
                <div class="col-lg-5 p-4 text-center employee-id-card-side">
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($idCardCode) }}"
                        alt="QR {{ $idCardCode }}"
                        class="img-fluid border rounded p-1 bg-white mb-2"
                        style="max-width: 170px;">
                    <svg id="barcodeSvg"></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="employee-print-sheet {{ $printMode ? 'is-preview' : '' }}">
        <div class="employee-print-card">
            <div class="employee-print-top">
                <div class="employee-print-brand">MSTORE</div>
                <div class="employee-print-role">{{ strtoupper($employee->position) }}</div>
            </div>
            <div class="employee-print-main">
                <div class="employee-print-left">
                    <div class="employee-print-name">{{ $employee->full_name }}</div>
                    <div class="employee-print-row"><span>ID Card</span><strong>{{ $idCardCode }}</strong></div>
                    <div class="employee-print-row"><span>Divisi/Jabatan</span><strong>{{ $employee->department }} / {{ $employee->position }}</strong></div>
                    <div class="employee-print-row"><span>No HP</span><strong>{{ $employee->phone ?: '-' }}</strong></div>
                </div>
                <div class="employee-print-right">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={{ urlencode($idCardCode) }}" class="employee-print-qr" alt="QR">
                </div>
            </div>
            <div class="employee-print-footer">
                <svg id="barcodePrintSvg"></svg>
                <div class="employee-print-code">{{ $idCardCode }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const code = @json($idCardCode);
    JsBarcode('#barcodeSvg', code, {
        format: 'CODE128',
        lineColor: '#111827',
        width: 1.8,
        height: 50,
        displayValue: false,
        margin: 0,
    });
    if (document.getElementById('barcodePrintSvg')) {
        JsBarcode('#barcodePrintSvg', code, {
            format: 'CODE128',
            lineColor: '#111827',
            width: 1.4,
            height: 30,
            displayValue: false,
            margin: 0,
        });
    }
});
</script>
@endpush

@push('styles')
<style>
.employee-id-card-screen { border-radius: 1rem; overflow: hidden; }
.employee-id-card-side { background: linear-gradient(165deg, rgba(15,23,42,0.03), rgba(37,99,235,0.08)); }
.employee-print-sheet { display: none; }
.employee-print-sheet.is-preview {
    display: flex; justify-content: center; margin-top: 1rem; padding: 1rem;
    background: #f1f5f9; border-radius: 1rem;
}
.employee-print-card {
    width: 85.6mm; height: 54mm; background: #fff; border: 1px solid #0f172a; border-radius: 3mm;
    display: flex; flex-direction: column; overflow: hidden;
}
.employee-print-top {
    background: linear-gradient(135deg, #0f172a, #1d4ed8); color: #fff;
    padding: 2.2mm 3mm; display: flex; justify-content: space-between; align-items: center;
}
.employee-print-brand { font-size: 2.8mm; font-weight: 700; }
.employee-print-role { font-size: 1.9mm; border: 1px solid rgba(255,255,255,.45); border-radius: 99px; padding: .8mm 1.6mm; }
.employee-print-main { flex: 1; display: flex; padding: 2mm 3mm 1.2mm; gap: 2mm; }
.employee-print-left { flex: 1; min-width: 0; }
.employee-print-name { font-size: 2.9mm; font-weight: 700; margin-bottom: 1.1mm; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.employee-print-row { display: flex; justify-content: space-between; gap: 1.2mm; border-bottom: 1px dashed #cbd5e1; padding: .4mm 0; font-size: 1.9mm; }
.employee-print-row span { color: #64748b; }
.employee-print-row strong { color: #111827; max-width: 30mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.employee-print-right { width: 17mm; display: flex; justify-content: center; }
.employee-print-qr { width: 16mm; height: 16mm; border: 1px solid #cbd5e1; border-radius: 1.2mm; padding: .5mm; background: #fff; }
.employee-print-footer { border-top: 1px solid #e2e8f0; padding: 1.2mm 3mm 1.6mm; display: flex; flex-direction: column; align-items: center; }
#barcodePrintSvg { width: 42mm; height: 7.2mm; }
.employee-print-code { font-size: 1.8mm; color: #334155; margin-top: .6mm; letter-spacing: .05em; }

@media print {
    @page { size: A4 portrait; margin: 10mm; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    #sidebar-wrapper, #sidebar-overlay, .main-header, .navbar, .sidebar, footer, .employee-id-toolbar, .employee-id-card-screen,
    .mobile-bottom-nav, #mobile-bottom-nav, [class*="mobile-bottom-nav"] { display: none !important; }
    #wrapper { display: block !important; }
    #page-content-wrapper { margin-left: 0 !important; width: 100% !important; max-width: 100% !important; padding: 0 !important; }
    .employee-id-card-page,
    .employee-id-card-page * {
        visibility: hidden !important;
    }
    .employee-print-sheet { display: flex !important; justify-content: center; width: 100% !important; margin: 0 !important; padding: 0 !important; background: transparent !important; }
    .employee-print-sheet,
    .employee-print-sheet * {
        visibility: visible !important;
    }
}
</style>
@endpush
