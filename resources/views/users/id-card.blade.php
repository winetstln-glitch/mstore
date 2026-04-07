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
            <a href="{{ request()->url() }}?print=1" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fa-regular fa-id-card me-1"></i>Print Preview
            </a>
            <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="employee-print-sheet is-preview">
        <div class="id-card-sheet">
            @php
                $fullName = $employee ? $employee->full_name : $user->name;
                $position = $employee ? $employee->position : ($user->role ? $user->role->label : '-');
                $department = $employee ? $employee->department : 'GENERAL';
                $expDate = ($employee && $employee->id_card_expires_at) ? $employee->id_card_expires_at->format('m/d/Y') : now()->addYear()->format('m/d/Y');
                
                $avatar = (string) ($user->avatar ?? '');
                $cardPhoto = $employee ? (string) ($employee->id_card_photo_path ?? '') : '';
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
                        <div class="brand-logo"><img src="{{ $logoUrl }}" alt="Logo"></div>
                        <div>
                            <div class="brand-name">{{ $brandName }}</div>
                            <div class="brand-subtitle">{{ strtoupper($department ?: 'GENERAL') }}</div>
                        </div>
                    </div>
                    
                </div>
                <div class="id-card-item-main">
                    <div class="hex-container">
                        <div class="hex-border"></div>
                        <div class="hex-img">
                            @if($photoUrl)
                                <img src="{{ $photoUrl }}" alt="Photo {{ $fullName }}" class="photo-image">
                            @else
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($fullName) }}&background=f1f5f9&color=1e40af&bold=true" alt="Photo {{ $fullName }}" class="photo-image">
                            @endif
                        </div>
                    </div>
                    <div class="identity-lines">
                        <div class="identity-row">
                            <div class="identity-label">NAME</div>
                            <div class="identity-sep">:</div>
                            <div class="identity-value">{{ $fullName }}</div>
                        </div>
                        <div class="identity-row">
                            <div class="identity-label">TITLE</div>
                            <div class="identity-sep">:</div>
                            <div class="identity-value">{{ $position ?: '-' }}</div>
                        </div>
                        <div class="identity-row">
                            <div class="identity-label">EMP ID</div>
                            <div class="identity-sep">:</div>
                            <div class="identity-value">{{ $idCardCode }}</div>
                        </div>
                    </div>
                </div>
                <div class="id-card-item-footer">
                    <div class="exp-badge">EXP: {{ $expDate }}</div>
                    <div class="barcode-container">
                        <svg class="barcode-svg" data-code="{{ $idCardCode }}"></svg>
                    </div>
                    <div class="barcode-text">{{ $idCardCode }}</div>
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
    const code = @json($idCardCode);
    document.querySelectorAll('.barcode-svg').forEach((el) => {
        JsBarcode(el, code, {
            format: 'CODE128',
            lineColor: '#111827',
            width: 2,
            height: 30,
            displayValue: false,
            margin: 0
        });
    });
});
</script>
@endpush

@push('styles')
<style>
    .employee-id-card-page {
        max-width: 800px;
    }
    
    .id-card-sheet {
        display: flex;
        justify-content: center;
        padding: 20px;
        background: #f3f4f6;
        border-radius: 12px;
    }

    /* ID Card Styling - Credit Card Size (approx 85.6 x 54 mm) */
    .id-card-item {
        width: 320px; /* Vertical orientation */
        height: 500px;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        border: 1px solid #e5e7eb;
    }

    .lanyard-slot {
        width: 40px;
        height: 6px;
        background: #e5e7eb;
        border-radius: 3px;
        margin: 15px auto 0;
    }

    .id-card-item-top {
        padding: 20px;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
        text-align: center;
    }

    .brand-section {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .brand-logo img {
        height: 35px;
        filter: brightness(0) invert(1);
    }

    .brand-name {
        font-weight: 800;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
        line-height: 1;
    }

    .brand-subtitle {
        font-size: 0.65rem;
        opacity: 0.9;
        font-weight: 600;
        letter-spacing: 1px;
        margin-top: 2px;
    }

    .id-card-item-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 25px 20px;
    }

    .hex-container {
        position: relative;
        width: 140px;
        height: 160px;
        margin-bottom: 20px;
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.15));
    }

    .hex-border {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
    }

    .hex-img {
        position: absolute;
        top: 4px; left: 4px;
        width: calc(100% - 8px);
        height: calc(100% - 8px);
        background: #f9fafb;
        clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .photo-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .photo-placeholder {
        font-size: 3rem;
        color: #d1d5db;
    }

    .identity-lines {
        width: 100%;
    }

    .identity-row {
        display: flex;
        margin-bottom: 8px;
        font-size: 0.85rem;
    }

    .identity-label {
        width: 60px;
        color: #6b7280;
        font-weight: 600;
    }

    .identity-sep {
        width: 15px;
        color: #9ca3af;
    }

    .identity-value {
        flex: 1;
        color: #111827;
        font-weight: 700;
        text-transform: uppercase;
    }

    .id-card-item-footer {
        padding: 15px 20px 25px;
        background: #f9fafb;
        border-top: 1px dashed #e5e7eb;
        text-align: center;
        position: relative;
    }

    .exp-badge {
        position: absolute;
        top: -10px;
        right: 20px;
        background: #ef4444;
        color: white;
        font-size: 0.6rem;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
    }

    .barcode-container {
        margin-bottom: 5px;
    }

    .barcode-svg {
        max-width: 100%;
        height: auto;
    }

    .barcode-text {
        font-family: monospace;
        font-size: 0.75rem;
        font-weight: 700;
        color: #374151;
        letter-spacing: 2px;
    }

    @media print {
        .employee-id-toolbar, .main-header, #sidebar-wrapper, .breadcrumb {
            display: none !important;
        }
        #wrapper { padding: 0 !important; margin: 0 !important; }
        #page-content-wrapper { padding: 0 !important; }
        .container { width: 100% !important; max-width: none !important; padding: 0 !important; }
        .id-card-sheet { background: none !important; padding: 0 !important; }
        .id-card-item { box-shadow: none !important; border: 1px solid #eee !important; }
        body { background: white !important; }
    }
</style>
@endpush
