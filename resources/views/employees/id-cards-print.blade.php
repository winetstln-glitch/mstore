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
        </div>
    </div>

    @php
        $storeAddress = (string) \App\Models\Setting::getValue('store_address', '');
        $storePhone = (string) \App\Models\Setting::getValue('store_phone', '');
        $waNumber = (string) \App\Models\Setting::getValue('whatsapp_number', '');
        $recoveryContact = $waNumber !== '' ? $waNumber : ($storePhone !== '' ? $storePhone : '-');
        $brandWebsite = preg_replace('#^https?://#', '', url('/'));
    @endphp

    <div class="id-card-sheet">
        @forelse($cards as $row)
            @php
                $employee = $row['employee'];
                $code = (string) ($row['code'] ?? '');
                $brandName = (string) ($row['brand_name'] ?? 'MSTORE');
                $brandSlogan = (string) ($row['brand_slogan'] ?? 'Solusi Digital Cepat dan Terpercaya');
                $logoUrl = (string) ($row['logo_url'] ?? asset('img/logo.png'));
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

            <div class="id-card-pair" id="card-pair-{{ $employee->id }}">
                <div class="id-card-item brand-{{ $row['brand_key'] ?? 'mstore' }} id-card-front">
                    <div class="wave-accent-top"></div>
                    <div class="header-bg"></div>
                    <div class="wave-accent-bottom"></div>
                    <div class="side-curve"></div>

                    <div class="logo-container">
                        <div class="logo-diamond">
                            <img src="{{ $logoUrl }}" alt="Logo {{ $brandName }}" crossorigin="anonymous" referrerpolicy="no-referrer" style="display:block">
                        </div>
                        <div class="company-copy">
                            <div class="company-name">{{ $brandName }}</div>
                            <div class="company-tagline">{{ $brandSlogan }}</div>
                        </div>
                    </div>

                    <div class="profile-container">
                        <div class="profile-image">
                            @if($photoUrl)
                                <img src="{{ $photoUrl }}" alt="Photo {{ $employee->full_name }}" class="photo-image" crossorigin="anonymous" referrerpolicy="no-referrer" style="display:block">
                            @else
                                <div class="photo-placeholder"><i class="fa-solid fa-user"></i></div>
                            @endif
                        </div>
                    </div>

                    <div class="content">
                        <h1 class="name-block">{{ $employee->full_name }}</h1>
                        <p class="job-title">{{ strtoupper($employee->position ?: 'STAFF') }}</p>
                    </div>

                    <div class="footer-info">
                        <div class="id-label">ID NO : {{ $code }}</div>
                        <div class="barcode-container">
                            <svg class="barcode-svg" data-code="{{ $code }}"></svg>
                        </div>
                        <div class="barcode-text">{{ $code }}</div>
                    </div>
                </div>

                <div class="id-card-item id-card-back brand-{{ $row['brand_key'] ?? 'mstore' }} id-card-back">
                    <div class="back-header-bg"></div>
                    <div class="back-accent-circle"></div>
                    <div class="back-accent-line"></div>

                    <div class="back-brand-lockup">
                        <div class="back-logo-frame">
                            <img src="{{ $logoUrl }}" alt="Logo {{ $brandName }}" crossorigin="anonymous" referrerpolicy="no-referrer" style="display:block">
                        </div>
                        <div class="back-brand-meta">
                            <div class="back-brand-name">{{ $brandName }}</div>
                            <div class="back-brand-slogan">{{ $brandSlogan }}</div>
                        </div>
                    </div>

                    <div class="back-content">
                        <div class="back-chip">OFFICIAL ID CARD</div>
                        <div class="back-title">KARTU IDENTITAS RESMI</div>
                        <p class="back-description">
                            Kartu ini adalah identitas resmi perusahaan. Jika ditemukan, harap hubungi kontak resmi di bawah ini.
                        </p>

                        <div class="back-contact-card">
                            <div class="back-contact-item">
                                <div class="back-contact-label">Alamat</div>
                                <div class="back-contact-value">{{ $storeAddress !== '' ? $storeAddress : '-' }}</div>
                            </div>
                            <div class="back-contact-item">
                                <div class="back-contact-label">Telepon</div>
                                <div class="back-contact-value">{{ $storePhone !== '' ? $storePhone : '-' }}</div>
                            </div>
                            <div class="back-contact-item">
                                <div class="back-contact-label">WhatsApp</div>
                                <div class="back-contact-value">{{ $recoveryContact }}</div>
                            </div>
                            <div class="back-contact-item">
                                <div class="back-contact-label">Website</div>
                                <div class="back-contact-value">{{ $brandWebsite }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="back-footer">
                        <div class="back-footer-note">ID NO : {{ $code }}</div>
                        <div class="back-footer-warning">Kartu ini wajib dibawa saat bertugas.</div>
                    </div>
                </div>

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
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.barcode-svg').forEach((el) => {
        const code = el.dataset.code || '';
        if (!code || typeof JsBarcode === 'undefined') {
            return;
        }

        JsBarcode(el, code, {
            format: 'CODE128',
            lineColor: '#111827',
            width: 2,
            height: 26,
            displayValue: false,
            margin: 0,
        });
    });
});

async function waitForAssets(element) {
    const images = Array.from(element.querySelectorAll('img'));
    const promises = images.map((img) => {
        try {
            if (img.decode) {
                return img.decode().catch(() => {});
            }
        } catch (e) {}
        return new Promise((resolve) => {
            if (img.complete) return resolve();
            img.onload = img.onerror = () => resolve();
        });
    });
    await Promise.all(promises);
    await new Promise((r) => setTimeout(r, 150));
}

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

function downloadElement(element, fileName) {
    return waitForAssets(element).then(() => {
        return html2canvas(element, {
            scale: 4,
            useCORS: true,
            logging: false,
            backgroundColor: '#ffffff'
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = fileName;
            link.href = canvas.toDataURL('image/jpeg', 0.95);
            link.click();
        }).catch((e) => {
            alert('Gagal membuat gambar. Pastikan logo/foto dapat diakses (CORS) dan coba muat ulang halaman.');
            console.error(e);
        });
    });
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
        const name = pair.querySelector('.name-block').textContent.trim().toLowerCase().replace(/\s+/g, '-');
        await downloadPair(id, name);
        // Delay between pairs
        await new Promise(r => setTimeout(r, 600));
    }

    btn.disabled = false;
    btn.innerHTML = originalText;
    alert('Selesai mendownload semua gambar.');
}
</script>
@endpush

@push('styles')
<style>
.id-card-pair {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
    page-break-inside: avoid;
}

.card-pair-toolbar {
    margin-top: 5px;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
@endpush

@push('styles')
<style>
.employee-cards-print-page {
    max-width: 1320px;
}

.id-card-sheet {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    justify-content: center;
}

.id-card-pair {
    display: grid;
    grid-template-columns: repeat(2, 54mm);
    gap: 18px;
}

@media (max-width: 767.98px) {
    .id-card-pair {
        grid-template-columns: repeat(1, 54mm);
    }
}

/* Brand Themes */
.brand-gtwash .header-bg, .brand-gtwash .back-header-bg { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
.brand-gtwash .wave-accent-top { background: #86efac; }
.brand-gtwash .side-curve { border-color: #86efac; }
.brand-gtwash .job-title, .brand-gtwash .back-contact-label { color: #16a34a; }

.brand-mstorenet .header-bg, .brand-mstorenet .back-header-bg { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.brand-mstorenet .wave-accent-top { background: #93c5fd; }
.brand-mstorenet .side-curve { border-color: #93c5fd; }
.brand-mstorenet .job-title, .brand-mstorenet .back-contact-label { color: #2563eb; }

.id-card-item {
    width: 54mm;
    height: 85.6mm;
    border-radius: 4.5mm;
    overflow: hidden;
    background: linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
    position: relative;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    border: 1px solid #dbe3f0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.header-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 29mm;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    clip-path: polygon(0 0, 100% 0, 100% 70%, 0 100%);
    z-index: 1;
}

.wave-accent-top {
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 31mm;
    background: #fdba74;
    clip-path: polygon(30% 0, 100% 0, 100% 85%, 0 100%);
    z-index: 0;
    opacity: 0.55;
}

.wave-accent-bottom {
    position: absolute;
    bottom: -6mm;
    right: -6mm;
    width: 26mm;
    height: 26mm;
    border-radius: 50%;
    background: #fef08a;
    z-index: 0;
    opacity: 0.6;
}

.side-curve {
    position: absolute;
    left: -8mm;
    bottom: 12mm;
    width: 18mm;
    height: 46mm;
    border: 2.4mm solid #fdba74;
    border-radius: 999px;
    opacity: 0.35;
    z-index: 0;
    transform: rotate(-15deg);
}

.logo-container {
    position: absolute;
    top: 4.5mm;
    left: 4mm;
    right: 4mm;
    z-index: 3;
    display: flex;
    align-items: center;
    gap: 2.6mm;
    color: #fff;
}

.logo-diamond {
    width: 11mm;
    height: 11mm;
    position: relative;
    border-radius: 0.6mm;
    background: #ffffff;
    border: 0.7pt solid rgba(255, 255, 255, 0.96);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transform: rotate(45deg);
    box-shadow: 0 5px 12px rgba(15, 23, 42, 0.16);
}

.logo-diamond::before {
    content: "";
    position: absolute;
    inset: 0.65mm;
    border: 0.55pt solid rgba(212, 175, 55, 0.92);
    border-radius: 0.4mm;
    pointer-events: none;
}

.logo-diamond img {
    width: 82%;
    height: 82%;
    object-fit: contain;
    transform: rotate(-45deg);
    position: relative;
    z-index: 1;
}

.company-copy {
    min-width: 0;
    max-width: 31mm;
}

.company-name {
    font-size: 3mm;
    font-weight: 900;
    letter-spacing: 0.06em;
    line-height: 1.1;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.company-tagline {
    margin-top: 0.7mm;
    font-size: 1.55mm;
    font-weight: 700;
    letter-spacing: 0.07em;
    line-height: 1.2;
    opacity: 0.95;
    text-transform: none;
}

.profile-container {
    position: relative;
    z-index: 2;
    margin-top: 17.8mm;
    display: flex;
    justify-content: center;
}

.profile-image {
    width: 31mm;
    height: 31mm;
    border-radius: 50%;
    background:
        linear-gradient(45deg, rgba(203, 213, 225, 0.45) 25%, transparent 25%, transparent 75%, rgba(203, 213, 225, 0.45) 75%),
        linear-gradient(45deg, rgba(203, 213, 225, 0.45) 25%, transparent 25%, transparent 75%, rgba(203, 213, 225, 0.45) 75%),
        linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    background-size: 4mm 4mm, 4mm 4mm, auto;
    background-position: 0 0, 2mm 2mm, 0 0;
    border: 1.5mm solid #ffffff;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.16);
}

.profile-image img,
.profile-image .photo-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.photo-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-placeholder {
    color: #64748b;
    background: linear-gradient(145deg, #eef2ff, #f8fafc);
}

.photo-placeholder i {
    font-size: 10mm;
    color: #94a3b8;
}

.content {
    position: relative;
    z-index: 2;
    margin-top: 2.8mm;
    margin-left: 3.3mm;
    margin-right: 3.3mm;
    padding: 2.4mm 2.8mm 2mm;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.name-block {
    margin: 0;
    font-size: 4.05mm;
    font-weight: 900;
    line-height: 1.05;
    color: #111827;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    max-width: 100%;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-wrap: balance;
}

.job-title {
    margin: 1mm 0 0;
    font-size: 2.2mm;
    font-weight: 800;
    color: #f97316;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.footer-info {
    position: absolute;
    left: 4mm;
    right: 4mm;
    bottom: 3mm;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.9mm;
    padding-top: 2.2mm;
    border-top: 0.8pt solid rgba(148, 163, 184, 0.3);
}

.id-label {
    width: 100%;
    font-size: 1.9mm;
    font-weight: 800;
    color: #111827;
    letter-spacing: 0.12em;
    text-align: center;
    margin-bottom: 0.55mm;
}

.barcode-container {
    width: 100%;
    min-height: 10.6mm;
    background: rgba(255, 255, 255, 0.92);
    border-radius: 2.4mm;
    padding: 1.1mm 0.8mm 0.4mm;
    border: 0.8pt solid rgba(15, 23, 42, 0.12);
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
}

.barcode-svg {
    max-width: 100%;
    max-height: 8.2mm;
    height: auto;
}

.barcode-text {
    font-size: 1.55mm;
    font-weight: 800;
    color: #334155;
    letter-spacing: 0.18em;
    line-height: 1;
    text-transform: uppercase;
}

.id-card-back {
    background:
        radial-gradient(circle at top right, rgba(253, 186, 116, 0.22), transparent 30%),
        linear-gradient(180deg, #fffaf5 0%, #ffffff 100%);
}

.back-header-bg {
    position: absolute;
    inset: 0 0 auto 0;
    height: 22mm;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    border-bottom-left-radius: 7mm;
    z-index: 0;
}

.back-accent-circle {
    position: absolute;
    top: 7mm;
    right: -6mm;
    width: 24mm;
    height: 24mm;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.18);
    z-index: 1;
}

.back-accent-line {
    position: absolute;
    left: -7mm;
    bottom: 14mm;
    width: 16mm;
    height: 38mm;
    border: 2mm solid rgba(249, 115, 22, 0.25);
    border-radius: 999px;
    transform: rotate(-18deg);
    z-index: 0;
}

.back-brand-lockup {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 2.6mm;
    padding: 4.6mm 4mm 0;
    color: #fff;
}

.back-logo-frame {
    width: 11.5mm;
    height: 11.5mm;
    border-radius: 3mm;
    background: rgba(255, 255, 255, 0.96);
    border: 0.7pt solid rgba(255, 255, 255, 0.98);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.15);
}

.back-logo-frame img {
    width: 74%;
    height: 74%;
    object-fit: contain;
}

.back-brand-meta {
    min-width: 0;
    max-width: 32mm;
}

.back-brand-name {
    font-size: 3mm;
    font-weight: 900;
    letter-spacing: 0.05em;
    line-height: 1.1;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.back-brand-slogan {
    margin-top: 0.7mm;
    font-size: 1.45mm;
    font-weight: 700;
    line-height: 1.2;
    opacity: 0.95;
}

.back-content {
    position: relative;
    z-index: 2;
    padding: 7mm 4mm 0;
}

.back-chip {
    display: inline-flex;
    align-items: center;
    min-height: 4.8mm;
    padding: 0.7mm 2mm;
    border-radius: 999px;
    background: #fff7ed;
    border: 0.8pt solid #fdba74;
    color: #c2410c;
    font-size: 1.6mm;
    font-weight: 800;
    letter-spacing: 0.08em;
}

.back-title {
    margin-top: 2.2mm;
    font-size: 3.2mm;
    font-weight: 900;
    line-height: 1.1;
    color: #111827;
    letter-spacing: 0.03em;
}

.back-description {
    margin: 1.8mm 0 0;
    font-size: 1.75mm;
    line-height: 1.45;
    color: #475569;
}

.back-contact-card {
    margin-top: 3.2mm;
    padding: 2.4mm 2.4mm 2.2mm;
    border-radius: 3mm;
    background: rgba(255, 255, 255, 0.9);
    border: 0.8pt solid rgba(148, 163, 184, 0.22);
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    display: flex;
    flex-direction: column;
    gap: 1.8mm;
}

.back-contact-item {
    display: flex;
    flex-direction: column;
    gap: 0.45mm;
}

.back-contact-label {
    font-size: 1.45mm;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f97316;
}

.back-contact-value {
    font-size: 1.75mm;
    font-weight: 700;
    line-height: 1.35;
    color: #0f172a;
    word-break: break-word;
}

.back-footer {
    position: absolute;
    left: 4mm;
    right: 4mm;
    bottom: 4mm;
    z-index: 2;
    padding-top: 2mm;
    border-top: 0.8pt solid rgba(148, 163, 184, 0.28);
    text-align: center;
}

.back-footer-note {
    font-size: 1.55mm;
    font-weight: 800;
    letter-spacing: 0.14em;
    color: #334155;
}

.back-footer-warning {
    margin-top: 0.8mm;
    font-size: 1.55mm;
    line-height: 1.35;
    color: #64748b;
}

@media print {
    @page { size: A4 portrait; margin: 8mm; }

    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

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

    body { margin: 0 !important; background: #fff !important; }

    #wrapper { display: block !important; }
    #page-content-wrapper { margin-left: 0 !important; width: 100% !important; max-width: 100% !important; padding: 0 !important; }

    .employee-cards-print-page {
        max-width: 100% !important;
        padding: 0 !important;
    }

    .id-card-sheet {
        justify-content: center !important;
        gap: 8mm 6mm !important;
    }

    .id-card-pair {
        gap: 6mm !important;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .id-card-item {
        box-shadow: none !important;
        border: 0.2pt solid #000 !important;
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>
@endpush
