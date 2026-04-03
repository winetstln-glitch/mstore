@extends('layouts.app')

@php
    $printMode = request()->boolean('print');
@endphp

@section('content')
<div class="container py-3 id-card-pro-page">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 id-card-toolbar">
        <h5 class="mb-0 fw-bold">ID Card Absensi Pro</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
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

    <div class="card border-0 shadow-lg id-card-shell id-card-pro-card overflow-hidden">
        <div class="id-card-pro-ribbon"></div>
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-lg-7">
                    <div class="p-4 p-lg-5">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="id-card-avatar">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="small text-uppercase fw-semibold text-secondary">MSTORE Digital Profile</div>
                                <h4 class="fw-bold mb-1">{{ $user->name }}</h4>
                                <div class="text-muted">{{ $user->role->label ?? 'Staff' }}</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="id-card-info-box">
                                    <div class="id-card-label">Username</div>
                                    <div class="id-card-value">{{ $user->username ?: '-' }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="id-card-info-box">
                                    <div class="id-card-label">No. HP</div>
                                    <div class="id-card-value">{{ $user->phone ?: '-' }}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="id-card-info-box">
                                    <div class="id-card-label">ID Card Code</div>
                                    <div class="id-card-code">{{ $user->attendance_card_code }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="id-card-profile-chip">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <i class="fa-solid fa-fingerprint me-1"></i>Absensi Aktif
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="fa-solid fa-id-card me-1"></i>ID Digital Terverifikasi
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 id-card-pro-side">
                    <div class="p-4 p-lg-5 text-center">
                        <div class="id-card-qr-wrap mb-3">
                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($user->attendance_card_code) }}"
                                alt="QR {{ $user->attendance_card_code }}"
                                class="img-fluid rounded"
                                style="max-width: 210px;">
                        </div>

                        <div class="id-card-barcode-wrap">
                            <div class="small fw-semibold text-uppercase mb-2">Barcode ID Profile</div>
                            <svg id="barcodeSvg"></svg>
                            <div class="small text-muted mt-2">{{ $user->attendance_card_code }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="id-card-print-sheet {{ $printMode ? 'is-preview' : '' }}">
        <div class="id-card-print-preview">
            <div class="id-card-print-top">
                <div class="id-card-print-brand">
                    <div class="id-card-print-logo">M</div>
                    <div>
                        <div class="id-card-print-title">MSTORE</div>
                        <div class="id-card-print-subtitle">ID CARD ABSENSI</div>
                    </div>
                </div>
                <div class="id-card-print-role">{{ strtoupper($user->role->label ?? 'STAFF') }}</div>
            </div>

            <div class="id-card-print-main">
                <div class="id-card-print-left">
                    <div class="id-card-print-name">{{ $user->name }}</div>
                    <div class="id-card-print-row"><span>Divisi/Jabatan</span><strong>{{ $user->role->label ?? 'Staff' }}</strong></div>
                    <div class="id-card-print-row"><span>Phone</span><strong>{{ $user->phone ?: '-' }}</strong></div>
                    <div class="id-card-print-row"><span>ID Code</span><strong>{{ $user->attendance_card_code }}</strong></div>
                </div>
                <div class="id-card-print-right">
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={{ urlencode($user->attendance_card_code) }}"
                        alt="QR {{ $user->attendance_card_code }}"
                        class="id-card-print-qr">
                </div>
            </div>

            <div class="id-card-print-footer">
                <svg id="barcodePrintSvg"></svg>
                <div class="id-card-print-code">{{ $user->attendance_card_code }}</div>
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
        width: 1.8,
        height: 52,
        displayValue: false,
        margin: 0,
    });
    if (document.getElementById('barcodePrintSvg')) {
        JsBarcode('#barcodePrintSvg', @json($user->attendance_card_code), {
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
.id-card-pro-page .id-card-pro-card {
    background: linear-gradient(120deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid rgba(148, 163, 184, 0.25);
    border-radius: 1.2rem;
    position: relative;
}
.id-card-pro-ribbon {
    position: absolute;
    right: -54px;
    top: -54px;
    width: 180px;
    height: 180px;
    border-radius: 999px;
    background: radial-gradient(circle at center, rgba(37, 99, 235, 0.2), rgba(16, 185, 129, 0.08), transparent 70%);
    pointer-events: none;
}
.id-card-pro-side {
    background: linear-gradient(165deg, rgba(15, 23, 42, 0.03), rgba(37, 99, 235, 0.06));
    border-left: 1px solid rgba(148, 163, 184, 0.2);
}
.id-card-avatar {
    width: 62px;
    height: 62px;
    border-radius: 16px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: #fff;
    font-size: 1.3rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.28);
}
.id-card-info-box {
    border: 1px solid rgba(148, 163, 184, 0.25);
    border-radius: 0.85rem;
    padding: 0.65rem 0.75rem;
    background: rgba(255, 255, 255, 0.82);
}
.id-card-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 0.1rem;
}
.id-card-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: #0f172a;
}
.id-card-code {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: 0.04em;
}
.id-card-profile-chip {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}
.id-card-qr-wrap {
    display: inline-block;
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 0.9rem;
    padding: 0.45rem;
    background: #fff;
}
.id-card-barcode-wrap {
    border: 1px dashed rgba(148, 163, 184, 0.55);
    border-radius: 0.75rem;
    padding: 0.6rem;
    background: rgba(255, 255, 255, 0.82);
}
[data-bs-theme="dark"] .id-card-pro-page .id-card-pro-card {
    background: linear-gradient(120deg, #0f172a 0%, #111827 100%);
    border-color: rgba(96, 165, 250, 0.32);
}
[data-bs-theme="dark"] .id-card-pro-side {
    background: linear-gradient(165deg, rgba(2, 6, 23, 0.66), rgba(30, 64, 175, 0.25));
    border-left-color: rgba(96, 165, 250, 0.25);
}
[data-bs-theme="dark"] .id-card-info-box,
[data-bs-theme="dark"] .id-card-barcode-wrap {
    background: rgba(15, 23, 42, 0.88);
    border-color: rgba(148, 163, 184, 0.35);
}
[data-bs-theme="dark"] .id-card-value,
[data-bs-theme="dark"] .id-card-code {
    color: #e2e8f0;
}
[data-bs-theme="dark"] .id-card-label {
    color: #94a3b8;
}

.id-card-print-sheet {
    display: none;
}
.id-card-print-sheet.is-preview {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
    padding: 1rem;
    background: #f1f5f9;
    border-radius: 1rem;
}
.id-card-print-preview {
    width: 85.6mm;
    height: 54mm;
    background: #fff;
    border: 1px solid #0f172a;
    border-radius: 3mm;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.id-card-print-top {
    background: linear-gradient(135deg, #0f172a, #1d4ed8);
    color: #fff;
    padding: 2.2mm 3mm;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.id-card-print-brand {
    display: flex;
    align-items: center;
    gap: 2mm;
}
.id-card-print-logo {
    width: 6mm;
    height: 6mm;
    border-radius: 1.5mm;
    background: #fff;
    color: #1d4ed8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 3.1mm;
    font-weight: 700;
}
.id-card-print-title {
    font-size: 2.6mm;
    font-weight: 700;
    line-height: 1;
}
.id-card-print-subtitle {
    font-size: 1.8mm;
    opacity: 0.92;
    line-height: 1;
    margin-top: 0.3mm;
}
.id-card-print-role {
    font-size: 1.9mm;
    padding: 0.8mm 1.6mm;
    border: 1px solid rgba(255, 255, 255, 0.45);
    border-radius: 99px;
}
.id-card-print-main {
    flex: 1;
    display: flex;
    padding: 2mm 3mm 1.2mm;
    gap: 2mm;
}
.id-card-print-left {
    flex: 1;
    min-width: 0;
}
.id-card-print-name {
    font-size: 2.9mm;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 1.1mm;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.id-card-print-row {
    display: flex;
    justify-content: space-between;
    gap: 1.2mm;
    border-bottom: 1px dashed #cbd5e1;
    padding: 0.4mm 0;
    font-size: 1.9mm;
}
.id-card-print-row span {
    color: #64748b;
}
.id-card-print-row strong {
    color: #111827;
    font-weight: 700;
    max-width: 30mm;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.id-card-print-right {
    width: 17mm;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}
.id-card-print-qr {
    width: 16mm;
    height: 16mm;
    border: 1px solid #cbd5e1;
    border-radius: 1.2mm;
    padding: 0.5mm;
    background: #fff;
}
.id-card-print-footer {
    border-top: 1px solid #e2e8f0;
    padding: 1.2mm 3mm 1.6mm;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
#barcodePrintSvg {
    width: 42mm;
    height: 7.2mm;
}
.id-card-print-code {
    font-size: 1.8mm;
    color: #334155;
    margin-top: 0.6mm;
    letter-spacing: 0.05em;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    body {
        background: #fff !important;
        margin: 0 !important;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    #sidebar-wrapper,
    #sidebar-overlay,
    .main-header,
    .navbar,
    .sidebar,
    footer,
    .id-card-toolbar,
    .id-card-pro-card {
        display: none !important;
    }

    #wrapper {
        display: block !important;
    }

    #page-content-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
    }

    .id-card-print-sheet {
        display: flex !important;
        justify-content: center;
        align-items: flex-start;
        width: 100% !important;
        margin-top: 0 !important;
        padding: 0 !important;
        background: transparent !important;
        border-radius: 0 !important;
    }

    .id-card-print-preview {
        border: 1px solid #0f172a !important;
    }

    .id-card-print-top {
        background: #1d4ed8 !important;
        color: #ffffff !important;
    }

    .id-card-print-role {
        background: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.7) !important;
    }

    .id-card-print-main {
        background: #ffffff !important;
    }

    .id-card-print-footer {
        background: #f8fafc !important;
    }
}
</style>
@endpush
