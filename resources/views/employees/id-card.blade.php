@extends('layouts.app')

@section('title', 'ID Card Karyawan')

@section('content')
<div class="card shadow-sm border-0 mb-4 overflow-hidden">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-id-card text-primary fa-lg"></i>
                <div>
                    <h5 class="mb-0 fw-bold">ID Card Karyawan</h5>
                    <div class="text-muted x-small">Preview dan cetak kartu identitas resmi.</div>
                </div>
            </div>
            
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
                <div class="btn-group btn-group-sm shadow-sm">
                    <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="btn btn-outline-primary">
                        <i class="fa-regular fa-eye me-1"></i> Preview
                    </a>
                    <button type="button" class="btn btn-primary px-3" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i> Cetak
                    </button>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-dark dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-download me-1"></i> Unduh
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><button class="dropdown-item py-2" type="button" onclick="downloadCard('front')"><i class="fa-regular fa-image me-2 text-primary"></i> Sisi Depan (JPG)</button></li>
                        <li><button class="dropdown-item py-2" type="button" onclick="downloadCard('back')"><i class="fa-regular fa-image me-2 text-success"></i> Sisi Belakang (JPG)</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item py-2 fw-bold" type="button" onclick="downloadBothCards()"><i class="fa-solid fa-images me-2 text-warning"></i> Unduh Keduanya</button></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="employee-print-sheet is-preview d-flex justify-content-center py-4 bg-light rounded-4 border shadow-inner">
    <div class="id-card-wrapper shadow-lg rounded-3 overflow-hidden">
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
