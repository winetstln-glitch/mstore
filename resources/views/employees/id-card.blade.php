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
            <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fa-regular fa-id-card me-1"></i>Print Preview
            </a>
            <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i>Print
            </button>
            <div class="dropdown">
                <button class="btn btn-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-download me-1"></i>Download
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><button class="dropdown-item" type="button" onclick="downloadCard('front')"><i class="fa-regular fa-image me-2"></i>Download Front (JPG)</button></li>
                    <li><button class="dropdown-item" type="button" onclick="downloadCard('back')"><i class="fa-regular fa-image me-2"></i>Download Back (JPG)</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button class="dropdown-item" type="button" onclick="downloadBothCards()"><i class="fa-solid fa-images me-2"></i>Download Keduanya (Zip/Batch)</button></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="employee-print-sheet is-preview">
        <x-id-card 
            :user="$user" 
            :employee="$employee" 
            :brand-name="$brandName" 
            :logo-url="$logoUrl" 
            :brand-slogan="$brandSlogan" 
            :brand-key="$brandKey" 
            :id-card-code="$idCardCode" 
        />
    </div>
</div>
@endsection

@push('scripts')
<x-id-card-scripts />
<script>
async function downloadCard(side) {
    const elementId = `id-card-${side}`;
    const element = document.getElementById(elementId);
    if (!element) return;

    const fileName = `ID_Card_${side === 'front' ? 'Depan' : 'Belakang'}_{{ Str::slug($employee->full_name) }}.jpg`;
    await downloadElement(element, fileName);
}

function downloadBothCards() {
    downloadCard('front').then(() => {
        setTimeout(() => downloadCard('back'), 600);
    });
}
</script>
@endpush

@push('styles')
<x-id-card-styles />
@endpush
