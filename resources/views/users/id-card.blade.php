@extends('layouts.app')

@section('title', 'ID Card User')

@section('content')
<div class="container py-3 user-id-card-page">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 user-id-toolbar">
        <h5 class="mb-0 fw-bold">ID Card User</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i>Kembali
            </a>
            <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i>Print
            </button>
        </div>
    </div>

    @php
        $expDate = now()->addYear()->format('m/d/Y');
        $avatar = (string) ($user->avatar ?? '');
        $photoUrl = null;
        if ($avatar !== '') {
            if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
                $photoUrl = $avatar;
            } elseif (str_starts_with($avatar, 'storage/') || str_starts_with($avatar, 'img/')) {
                $photoUrl = asset($avatar);
            } else {
                $photoUrl = asset('storage/'.$avatar);
            }
        }
    @endphp

    <div class="user-print-sheet is-preview">
        <div class="id-card-sheet">
            <div class="id-card-item">
                <div class="lanyard-slot"></div>
                <div class="id-card-item-top">
                    <div class="brand-section">
                        <div class="brand-logo"><img src="{{ $logoUrl }}" alt="Logo"></div>
                        <div>
                            <div class="brand-name">{{ $brandName }}</div>
                            <div class="brand-subtitle">{{ strtoupper($user->role->label ?? 'GENERAL') }}</div>
                        </div>
                    </div>
                    <div class="exp-badge">EXP: {{ $expDate }}</div>
                </div>
                <div class="id-card-item-main">
                    <div class="photo-frame">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Photo {{ $user->name }}" class="photo-image">
                        @else
                            <div class="photo-placeholder">[PHOTO PLACEHOLDER]</div>
                        @endif
                    </div>
                    <div class="identity-lines">
                        <div class="identity-row">
                            <div class="identity-label">NAME:</div>
                            <div class="identity-value">{{ $user->name }}</div>
                        </div>
                        <div class="identity-row">
                            <div class="identity-label">TITLE:</div>
                            <div class="identity-value">{{ $user->role->label ?? '-' }}</div>
                        </div>
                        <div class="identity-row">
                            <div class="identity-label">EMP ID:</div>
                            <div class="identity-value">{{ $user->attendance_card_code }}</div>
                        </div>
                    </div>
                </div>
                <div class="id-card-item-footer">
                    <div class="barcode-container">
                        <svg class="barcode-svg" data-code="{{ $user->attendance_card_code }}"></svg>
                    </div>
                    <div class="barcode-text">{{ $user->attendance_card_code }}</div>
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
    const code = @json($user->attendance_card_code);
    document.querySelectorAll('.barcode-svg').forEach((el) => {
        JsBarcode(el, code, {
            format: 'CODE128',
            lineColor: '#111827',
            width: 1.4,
            height: 22,
            displayValue: false,
            margin: 0,
        });
    });
});
</script>
@endpush

@push('styles')
<style>
.user-print-sheet.is-preview {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
    padding: 1rem;
    background: #f1f5f9;
    border-radius: 1rem;
}
.id-card-sheet {
    display: grid;
    grid-template-columns: repeat(1, 54mm);
    gap: 20px;
    justify-content: center;
}
.id-card-item {
    width: 54mm;
    height: 85.6mm;
    border-radius: 4mm;
    overflow: hidden;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    position: relative;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
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
    border: 0.8pt solid #2563eb;
}
.brand-logo img { width: 100%; height: 100%; object-fit: cover; }
.brand-name { font-size: 3.4mm; font-weight: 900; letter-spacing: 0.2px; color: #1d4ed8; line-height: 1; }
.brand-subtitle { font-size: 1.8mm; font-weight: 800; color: #1d4ed8; line-height: 1.1; margin-top: 0.5mm; }
.exp-badge { font-size: 1.8mm; font-weight: 800; color: #1d4ed8; white-space: nowrap; }
.id-card-item-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 2.3mm 3.2mm 1.4mm;
    gap: 1.6mm;
}
.photo-frame {
    width: 100%;
    height: 31mm;
    border: 0.8pt dashed #9ca3af;
    border-radius: 1.3mm;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    overflow: hidden;
}
.photo-image { width: 100%; height: 100%; object-fit: cover; }
.photo-placeholder { font-size: 2.4mm; font-weight: 700; color: #64748b; letter-spacing: 0.3px; }
.identity-lines { display: flex; flex-direction: column; gap: 0.7mm; }
.identity-row { display: flex; align-items: baseline; gap: 1.1mm; line-height: 1.08; }
.identity-label {
    width: 12.5mm;
    font-size: 2.5mm;
    font-weight: 900;
    color: #1d4ed8;
    text-transform: uppercase;
    letter-spacing: 0.1px;
}
.identity-value {
    flex: 1;
    font-size: 2.9mm;
    font-weight: 800;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.id-card-item-footer {
    border-top: 0.8pt solid #bfdbfe;
    padding: 1.1mm 3.2mm 1.6mm;
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
    margin-top: 0.8mm;
    font-size: 1.6mm;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: 0.12em;
    line-height: 1;
}

@media print {
    @page { size: A4 portrait; margin: 10mm; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    #sidebar-wrapper, #sidebar-overlay, .main-header, .navbar, .sidebar, footer, .user-id-toolbar,
    .mobile-bottom-nav, #mobile-bottom-nav, [class*="mobile-bottom-nav"], nav { display: none !important; }
    #wrapper { display: block !important; }
    #page-content-wrapper { margin-left: 0 !important; width: 100% !important; max-width: 100% !important; padding: 0 !important; }
    .user-id-card-page,
    .user-id-card-page * { visibility: hidden !important; }
    .id-card-sheet { display: grid !important; grid-template-columns: repeat(1, 54mm); justify-content: center; gap: 0 !important; }
    .user-print-sheet { display: flex !important; justify-content: center; width: 100% !important; margin: 0 !important; padding: 0 !important; background: transparent !important; }
    .user-print-sheet, .user-print-sheet * { visibility: visible !important; }
    .id-card-item { box-shadow: none !important; border: 0.2pt solid #000 !important; }
}
</style>
@endpush
