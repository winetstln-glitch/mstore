@extends('layouts.app')

@section('title', 'Print ID Cards Karyawan')

@section('content')
<div class="container py-3 employee-cards-print-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 employee-cards-toolbar">
        <div>
            <h5 class="mb-0 fw-bold">Print ID Cards Karyawan</h5>
            <small class="text-muted">Tiap pasangan menampilkan sisi depan dan belakang dengan ukuran kartu PVC 54mm x 85.6mm.</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i>Kembali
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i>Print Sekarang
            </button>
            <button type="button" class="btn btn-dark" id="downloadAllBtn" onclick="downloadAllImages()">
                <i class="fa-solid fa-download me-1"></i>Download Semua (Image)
            </button>
                <button type="button" class="btn btn-success" id="downloadZipBtn" onclick="downloadAllZip()">
                    <i class="fa-solid fa-file-zipper me-1"></i>Download ZIP
                </button>
        </div>
    </div>

    <div class="id-card-sheet">
        @forelse($cards as $row)
            @php
                $employee = $row['employee'];
                $user = $employee->user;
            @endphp

            <div class="id-card-pair" id="card-pair-{{ $employee->id }}">
                <x-id-card 
                    :user="$user" 
                    :employee="$employee" 
                    :brand-name="$row['brand_name'] ?? 'MSTORE'"
                    :logo-url="$row['logo_url'] ?? ''"
                    :brand-slogan="$row['brand_slogan'] ?? ''"
                    :brand-key="$row['brand_key'] ?? 'mstore'"
                    :id-card-code="$row['code'] ?? ''"
                />

                <div class="card-pair-toolbar no-print">
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="downloadPair('{{ $employee->id }}', '{{ Str::slug($employee->full_name) }}')">
                        <i class="fa-solid fa-download me-1"></i> Download Images
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <p class="text-muted">Tidak ada data karyawan terpilih untuk dicetak.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<x-id-card-scripts />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script>
async function downloadPair(id, name) {
    const pair = document.getElementById(`card-pair-${id}`);
    if (!pair) return;

    const front = pair.querySelector('.id-card-front');
    const back = pair.querySelector('.id-card-back');

    if (front) await downloadElement(front, `ID_Card_Depan_${name}.jpg`);
    if (back) {
        // Delay a bit to avoid browser issues
        await new Promise(r => setTimeout(r, 300));
        await downloadElement(back, `ID_Card_Belakang_${name}.jpg`);
    }
}

async function downloadAllImages() {
    const pairs = document.querySelectorAll('.id-card-pair');
    const btn = document.getElementById('downloadAllBtn');
    const originalText = btn.innerHTML;
    
    if (pairs.length > 5 && !confirm(`Anda akan mendownload ${pairs.length * 2} file gambar. Lanjutkan?`)) {
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Sedang memproses...';

    for (const pair of pairs) {
        const id = pair.id.replace('card-pair-', '');
        const nameText = pair.querySelector('.name-block')?.textContent || 'karyawan';
        const name = nameText.trim().toLowerCase().replace(/\s+/g, '-');
        await downloadPair(id, name);
        // Delay between pairs
        await new Promise(r => setTimeout(r, 600));
    }

    btn.disabled = false;
    btn.innerHTML = originalText;
    alert('Selesai mendownload semua gambar.');
}

async function downloadAllZip() {
    if (typeof JSZip === 'undefined' || typeof saveAs === 'undefined') {
        alert('ZIP dependencies failed to load.');
        return;
    }
    const btn = document.getElementById('downloadZipBtn');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyiapkan ZIP...';

    const zip = new JSZip();
    const folder = zip.folder('id-cards') || zip;
    const pairs = document.querySelectorAll('.id-card-pair');
    let processed = 0;

    for (const pair of pairs) {
        const nameText = pair.querySelector('.name-block')?.textContent || 'karyawan';
        const name = nameText.trim().toLowerCase().replace(/\s+/g, '-');
        const front = pair.querySelector('.id-card-front');
        const back = pair.querySelector('.id-card-back');

        if (front) {
            await waitForAssets(front);
            const canvasF = await html2canvas(front, { scale: 4, useCORS: true, backgroundColor: '#ffffff' });
            const blobF = await new Promise(res => canvasF.toBlob(res, 'image/jpeg', 0.95));
            if (blobF) folder.file(`ID_Card_Depan_${name}.jpg`, blobF);
        }
        if (back) {
            await waitForAssets(back);
            const canvasB = await html2canvas(back, { scale: 4, useCORS: true, backgroundColor: '#ffffff' });
            const blobB = await new Promise(res => canvasB.toBlob(res, 'image/jpeg', 0.95));
            if (blobB) folder.file(`ID_Card_Belakang_${name}.jpg`, blobB);
        }

        processed++;
        await new Promise(r => setTimeout(r, 200));
    }

    const zipBlob = await zip.generateAsync({ type: 'blob' });
    saveAs(zipBlob, 'ID-Cards.zip');
    btn.disabled = false;
    btn.innerHTML = original;
}
</script>
@endpush

@push('styles')
<x-id-card-styles />
<style>
@media print {
    @page { size: A4 portrait; margin: 8mm; }
    
    .employee-cards-toolbar {
        display: none !important;
    }
}
</style>
@endpush
