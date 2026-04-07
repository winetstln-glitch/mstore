@extends('layouts.app')

@section('title', 'Print ID Cards Karyawan')

@section('content')
<div class="container py-3 employee-cards-print-page">
    {{-- Toolbar ini akan disembunyikan saat print melalui CSS --}}
    <div class="d-flex justify-content-between align-items-center mb-4 employee-cards-toolbar">
        <div>
            <h5 class="mb-0 fw-bold">Print ID Cards Karyawan</h5>
            <small class="text-muted">Gunakan kertas PVC atau Art Paper 260gr untuk hasil terbaik.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Print Sekarang
            </button>
        </div>
    </div>

    <div class="id-card-sheet">
        @forelse($cards as $row)
            @php
                $employee = $row['employee'];
                $code = $row['code'];
            @endphp
            <div class="id-card-item">
                <div class="id-card-item-top">
                    <div class="brand">MSTORE</div>
                    <div class="role">{{ strtoupper($employee->position ?? 'STAFF') }}</div>
                </div>
                <div class="id-card-item-main">
                    <div class="left">
                        <div class="name">{{ $employee->full_name }}</div>
                        <div class="line"><span>ID Card</span><strong>{{ $code }}</strong></div>
                        <div class="line"><span>Divisi</span><strong>{{ $employee->department }}</strong></div>
                        <div class="line"><span>Jabatan</span><strong>{{ $employee->position }}</strong></div>
                        <div class="line"><span>No HP</span><strong>{{ $employee->phone ?: '-' }}</strong></div>
                    </div>
                    <div class="right">
                        {{-- Placeholder QR Code --}}
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($code) }}" alt="QR" class="qr">
                    </div>
                </div>
                <div class="id-card-item-footer">
                    <svg class="barcode-svg" data-code="{{ $code }}"></svg>
                    <div class="barcode-text">{{ $code }}</div>
                </div>
            </div>
        @empty
            <div class="alert alert-warning employee-cards-toolbar">Tidak ada data karyawan sesuai filter.</div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const generateBarcodes = () => {
            document.querySelectorAll('.barcode-svg').forEach((el) => {
                const code = el.dataset.code || '';
                if (!code) return;
                JsBarcode(el, code, {
                    format: 'CODE128',
                    lineColor: '#000',
                    width: 1.2,
                    height: 30,
                    displayValue: false,
                    margin: 0,
                });
            });
        };
        generateBarcodes();
    });
</script>
@endpush

@push('styles')
<style>
    /* UI Style (Screen Only) */
    .id-card-sheet {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(85.6mm, 1fr));
        gap: 20px;
        justify-content: center;
    }

    /* ID Card Base Style */
    .id-card-item {
        width: 85.6mm;
        height: 54mm;
        border: 1px solid #d1d5db;
        border-radius: 3mm;
        overflow: hidden;
        background: #fff;
        display: flex;
        flex-direction: column;
        position: relative;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .id-card-item-top {
        background: linear-gradient(135deg, #0f172a, #2563eb);
        color: #fff;
        padding: 2.5mm 4mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .id-card-item-top .brand { font-size: 3.5mm; font-weight: 800; letter-spacing: 0.5px; }
    .id-card-item-top .role { 
        font-size: 2mm; 
        background: rgba(255,255,255,0.2); 
        border: 0.5px solid rgba(255,255,255,0.5); 
        border-radius: 50px; 
        padding: 0.5mm 2.5mm; 
        text-transform: uppercase;
    }

    .id-card-item-main {
        flex: 1;
        display: flex;
        padding: 3mm 4mm;
        gap: 3mm;
    }

    .id-card-item-main .left { flex: 1; }
    .id-card-item-main .name {
        font-size: 3.2mm;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 2mm;
        border-bottom: 1.5px solid #2563eb;
        padding-bottom: 0.5mm;
        display: inline-block;
        width: 100%;
    }

    .id-card-item-main .line {
        display: flex;
        justify-content: space-between;
        font-size: 2.1mm;
        margin-bottom: 0.8mm;
        color: #475569;
    }
    .id-card-item-main .line strong { color: #000; font-weight: 600; }

    .id-card-item-main .right { width: 18mm; text-align: right; }
    .id-card-item-main .qr {
        width: 18mm;
        height: 18mm;
        border: 1px solid #e2e8f0;
        padding: 1mm;
        background: #fff;
    }

    .id-card-item-footer {
        padding: 1mm 4mm 2mm;
        display: flex;
        flex-direction: column;
        align-items: center;
        background: #f8fafc;
    }

    .id-card-item-footer .barcode-svg { width: 100%; max-height: 8mm; }
    .id-card-item-footer .barcode-text { font-size: 1.8mm; font-weight: 600; color: #1e293b; margin-top: 0.5mm; }

    /* PRINT OPTIMIZATION */
    @media print {
        @page { 
            size: A4 portrait; 
            margin: 10mm; 
        }

        /* Sembunyikan semua elemen UI kecuali konten utama */
        body * { visibility: hidden; }
        .id-card-sheet, .id-card-sheet * { visibility: visible; }
        
        /* Layout Grid Khusus Cetak */
        .id-card-sheet {
            visibility: visible;
            position: absolute;
            left: 0;
            top: 0;
            display: grid;
            grid-template-columns: repeat(2, 85.6mm); /* 2 kolom */
            gap: 7mm 7mm; /* Spasi potong antar kartu diperbesar */
            width: 100%;
            justify-content: center;
        }

        /* Hilangkan shadow dan border dekoratif saat print agar bersih */
        .id-card-item {
            box-shadow: none;
            border: 0.2pt solid #000; /* Border tipis sebagai guide potong */
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Sembunyikan elemen dashboard/nav (desktop + mobile) */
        .employee-cards-toolbar, 
        #sidebar-wrapper,
        #sidebar-overlay,
        .main-header, 
        .main-sidebar, 
        .main-footer, 
        .mobile-bottom-nav,
        #mobile-bottom-nav,
        [class*="mobile-bottom-nav"],
        nav, 
        footer { 
            display: none !important; 
        }
    }
</style>
@endpush
