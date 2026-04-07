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
                $brandName = (string) ($row['brand_name'] ?? 'MSTORE');
                $logoUrl = (string) ($row['logo_url'] ?? asset('img/logo.png'));
                $expDate = optional($employee->id_card_expires_at)->format('m/d/Y') ?: now()->addYear()->format('m/d/Y');
                $avatar = (string) ($employee->user?->avatar ?? '');
                $cardPhoto = (string) ($employee->id_card_photo_path ?? '');
                $photoUrl = null;
                if ($avatar !== '') {
                    if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
                        $photoUrl = $avatar;
                    } elseif (str_starts_with($avatar, 'storage/') || str_starts_with($avatar, 'img/')) {
                        $photoUrl = asset($avatar);
                    } else {
                        $photoUrl = asset('storage/'.$avatar);
                    }
                } elseif ($cardPhoto !== '') {
                    $photoUrl = asset('storage/'.$cardPhoto);
                }
            @endphp
            <div class="id-card-item">
                <div class="lanyard-slot"></div>
                <div class="id-card-item-top">
                    <div class="brand-section">
                        <div class="brand-logo">
                            <img src="{{ $logoUrl }}" alt="Logo">
                        </div>
                        <div>
                            <div class="brand-name">{{ $brandName }}</div>
                            <div class="brand-subtitle">{{ strtoupper($employee->department ?: 'GENERAL') }}</div>
                        </div>
                    </div>
                    <div class="exp-badge">EXP: {{ $expDate }}</div>
                </div>

                <div class="id-card-item-main">
                    <div class="photo-frame">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Photo {{ $employee->full_name }}" class="photo-image">
                        @else
                            <div class="photo-placeholder">[PHOTO PLACEHOLDER]</div>
                        @endif
                    </div>
                    <div class="identity-lines">
                        <div class="identity-row">
                            <div class="identity-label">NAME</div>
                            <div class="identity-sep">:</div>
                            <div class="identity-value">{{ $employee->full_name }}</div>
                        </div>
                        <div class="identity-row">
                            <div class="identity-label">TITLE</div>
                            <div class="identity-sep">:</div>
                            <div class="identity-value">{{ $employee->position ?: '-' }}</div>
                        </div>
                        <div class="identity-row">
                            <div class="identity-label">EMP ID</div>
                            <div class="identity-sep">:</div>
                            <div class="identity-value">{{ $code }}</div>
                        </div>
                    </div>
                </div>

                <div class="id-card-item-footer">
                    <div class="barcode-container">
                        <svg class="barcode-svg" data-code="{{ $code }}"></svg>
                    </div>
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
                    lineColor: '#111827',
                    width: 1.5,
                    height: 20,
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
    .id-card-sheet {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(54mm, 1fr));
        gap: 22px;
        justify-content: center;
        padding: 20px 0;
    }

    .id-card-item {
        width: 54mm;
        height: 85.6mm;
        border-radius: 4mm;
        overflow: hidden;
        background:
            radial-gradient(circle at 18% 16%, rgba(59, 130, 246, 0.18), transparent 42%),
            radial-gradient(circle at 85% 22%, rgba(14, 165, 233, 0.16), transparent 38%),
            radial-gradient(circle at 72% 88%, rgba(99, 102, 241, 0.14), transparent 40%),
            linear-gradient(145deg, #ffffff 0%, #f8fbff 55%, #eef4ff 100%);
        display: flex;
        flex-direction: column;
        position: relative;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        border: 0.8pt solid #dbeafe;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .lanyard-slot {
        position: absolute;
        top: 2.1mm;
        left: 50%;
        transform: translateX(-50%);
        width: 14mm;
        height: 3.2mm;
        border-radius: 999px;
        background: #d1d5db;
        z-index: 4;
        box-shadow: inset 0 0 1px rgba(0,0,0,0.2);
    }

    .id-card-item-top {
        background: #ffffff;
        border-bottom: 0.8pt solid #bfdbfe;
        color: #0f172a;
        padding: 7.2mm 3.2mm 2mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 2;
    }

    .brand-section { display: flex; align-items: center; gap: 2.1mm; }
    .brand-logo {
        width: 9mm;
        height: 9mm;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 1.6mm;
        background: #ffffff;
        overflow: hidden;
        border: 0.8pt solid #d6e0f5ff;
    }
    .brand-logo img { width: 100%; height: 100%; object-fit: cover; }
    .brand-name { font-size: 3.4mm; font-weight: 900; letter-spacing: 0.2px; color: #1d4ed8; line-height: 1; }
    .brand-subtitle { font-size: 1.8mm; font-weight: 800; color: #1d4ed8; line-height: 1.1; margin-top: 0.5mm; }
    .exp-badge {
        font-size: 1.8mm;
        font-weight: 800;
        color: #1d4ed8;
        white-space: nowrap;
    }

    .id-card-item-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 1.9mm 3.2mm 0.9mm;
        gap: 1.2mm;
    }

    .photo-frame {
        width: 28mm;
        height: 37mm;
        border: 0.8pt dashed #9ca3af;
        border-radius: 1.3mm;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        overflow: hidden;
        margin: 0 auto;
    }
    .photo-image { width: 100%; height: 100%; object-fit: cover; }
    .photo-placeholder {
        font-size: 2.4mm;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.3px;
    }

    .identity-lines { display: flex; flex-direction: column; gap: 0.7mm; }
    .identity-row { display: flex; align-items: baseline; gap: 0.8mm; line-height: 1.08; }
    .identity-label {
        width: 11.8mm;
        font-size: 2.3mm;
        font-weight: 900;
        color: #1d4ed8;
        text-transform: uppercase;
        letter-spacing: 0.1px;
    }
    .identity-sep {
        width: 2mm;
        text-align: center;
        font-size: 2.4mm;
        font-weight: 900;
        color: #1d4ed8;
        line-height: 1;
    }
    .identity-value {
        flex: 1;
        font-size: 2.6mm;
        font-weight: 800;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .id-card-item-footer {
        border-top: 0.8pt solid #bfdbfe;
        padding: 0.8mm 3.2mm 1.2mm;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .barcode-container {
        width: 100%;
        background: #fff;
        border-radius: 1.2mm;
        padding: 0.6mm 0.6mm 0;
        display: flex;
        justify-content: center;
    }
    .barcode-svg { max-width: 100%; height: auto; }
    .barcode-text {
        margin-top: 0.6mm;
        font-size: 1.5mm;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: 0.12em;
        line-height: 1;
    }

    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body * { visibility: hidden; }
        .id-card-sheet, .id-card-sheet * { visibility: visible; }

        .id-card-sheet {
            position: absolute;
            left: 0;
            top: 0;
            display: grid;
            grid-template-columns: repeat(3, 54mm);
            gap: 5mm;
            width: 100%;
            justify-content: center;
            padding: 0;
        }

        .id-card-item {
            box-shadow: none;
            border: 0.2pt solid #000;
            page-break-inside: avoid;
            break-inside: avoid;
        }

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
