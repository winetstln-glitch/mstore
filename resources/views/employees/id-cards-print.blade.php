@extends('layouts.app')

@section('title', 'Print ID Cards Karyawan')

@section('content')
<div class="card shadow-sm border-0 mb-4 overflow-hidden no-print">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-print text-primary fa-lg"></i>
                <div>
                    <h5 class="mb-0 fw-bold">Print Massal ID Card</h5>
                    <div class="text-muted x-small">Cetak kartu identitas untuk banyak karyawan sekaligus.</div>
                </div>
            </div>
            
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
                <button type="button" class="btn btn-sm btn-primary shadow-sm px-3" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Cetak Sekarang
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-dark dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-download me-1"></i> Unduh Massal
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><button class="dropdown-item py-2" type="button" onclick="downloadAllImages()"><i class="fa-regular fa-images me-2 text-primary"></i> Semua Gambar (JPG)</button></li>
                        <li><button class="dropdown-item py-2 fw-bold" type="button" onclick="downloadAllZip()"><i class="fa-solid fa-file-zipper me-2 text-success"></i> Download ZIP</button></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 no-print bg-light-subtle">
    <div class="card-body py-2">
        <form action="{{ route('employees.print.cards') }}" method="GET" class="row g-2 align-items-center">
            @foreach((array) request()->query('selected_ids', []) as $selectedId)
                <input type="hidden" name="selected_ids[]" value="{{ (int) $selectedId }}">
            @endforeach

            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted">Filter Jabatan</span>
                    <select name="position" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Jabatan</option>
                        @foreach(($positions ?? collect()) as $pos)
                            <option value="{{ $pos }}" {{ ($selectedPosition ?? '') === $pos ? 'selected' : '' }}>{{ $pos }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-dark px-3 text-nowrap">Terapkan</button>
                <a href="{{ route('employees.print.cards', array_filter(['selected_ids' => request()->query('selected_ids')])) }}" class="btn btn-sm btn-link text-decoration-none text-muted">Reset</a>
            </div>

            <div class="col-auto ms-auto">
                <span class="badge bg-white border text-dark fw-medium shadow-xs">{{ count($cards ?? []) }} Kartu Siap Cetak</span>
            </div>
        </form>
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
<script>
const positionFilter = @json($selectedPosition ?? '');
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
    if (typeof JSZip === 'undefined' || typeof saveAs === 'undefined' || typeof html2canvas === 'undefined') {
        alert('Fitur ZIP membutuhkan assets front-end yang lengkap. Tunggu sebentar atau jalankan: npm install && npm run build');
        return;
    }
    const btn = document.getElementById('downloadZipBtn');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyiapkan ZIP...';

    const zip = new JSZip();
    const folder = zip.folder('id-cards') || zip;
    const pairs = document.querySelectorAll('.id-card-pair');
    
    // Config for html2canvas to prevent distortion
    const captureOptions = {
        scale: 3,
        useCORS: true,
        allowTaint: false,
        backgroundColor: '#ffffff',
        imageTimeout: 15000,
        scrollX: 0,
        scrollY: 0,
        logging: false,
        onclone: (clonedDoc) => {
            const items = clonedDoc.querySelectorAll('.id-card-item');
            items.forEach(item => {
                item.classList.add('is-capturing');
                item.style.position = 'relative';
                item.style.margin = '0';
                item.style.display = 'block';
            });
        }
    };

    try {
        for (const pair of pairs) {
            const nameText = pair.querySelector('.name-block')?.textContent || 'karyawan';
            const name = nameText.trim().toLowerCase().replace(/\s+/g, '-');
            const front = pair.querySelector('.id-card-front');
            const back = pair.querySelector('.id-card-back');

            if (front) {
                await waitForAssets(front);
                const canvasF = await html2canvas(front, captureOptions);
                const blobF = await new Promise(res => canvasF.toBlob(res, 'image/jpeg', 1.0));
                if (blobF) folder.file(`ID_Card_Depan_${name}.jpg`, blobF);
            }
            if (back) {
                await waitForAssets(back);
                const canvasB = await html2canvas(back, captureOptions);
                const blobB = await new Promise(res => canvasB.toBlob(res, 'image/jpeg', 1.0));
                if (blobB) folder.file(`ID_Card_Belakang_${name}.jpg`, blobB);
            }

            // Small delay to prevent UI freezing
            await new Promise(r => setTimeout(r, 300));
        }

        const zipBlob = await zip.generateAsync({ type: 'blob' });
        const suffix = positionFilter ? `_${positionFilter.trim().toLowerCase().replace(/\s+/g, '-')}` : '';
        saveAs(zipBlob, `ID-Cards${suffix}.zip`);
    } catch (e) {
        alert('Terjadi kesalahan saat membuat ZIP. Silakan coba lagi atau download satu per satu.');
        console.error('ZIP Error:', e);
    } finally {
        btn.disabled = false;
        btn.innerHTML = original;
    }
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
