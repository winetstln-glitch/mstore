@extends('layouts.app')

@section('content')
<div class="payslip-container py-3">
    <div class="container-fluid max-w-5xl mx-auto">
        
        <!-- Panduan Penggunaan -->
        <div class="alert alert-info py-2 mb-4 print-none">
            <i class="fa-solid fa-circle-info me-2"></i>
            <strong>Panduan Penggunaan:</strong>
            <ul class="mb-0 mt-2">
                <li>Halaman ini menampilkan rekap slip gaji karyawan berdasarkan absensi pada periode yang dipilih.</li>
                <li>Gunakan filter di halaman Rekap Absensi untuk memilih bulan atau karyawan tertentu.</li>
                <li>Klik <strong>"Cetak PDF (A4)"</strong> untuk mencetak slip gaji dalam format PDF.</li>
                <li>Klik tombol <strong>"Kirim Image"</strong> pada setiap slip untuk mengirim slip gaji ke WhatsApp karyawan.</li>
            </ul>
        </div>
        
        <!-- Kontrol Aksi (Sembunyi saat Cetak) -->
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border mb-4 print-none">
            <div>
                <h5 class="fw-bold text-dark mb-0">Manajemen Slip Gaji</h5>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-light border">
                    <i class="fa-solid fa-arrow-left me-1"></i>Kembali
                </a>
                <button onclick="window.print()" class="btn btn-sm btn-primary px-3 shadow-sm">
                    <i class="fa-solid fa-print me-1"></i>Cetak PDF (A4)
                </button>
            </div>
        </div>

        <!-- Grid Slip Gaji -->
        <div class="row g-3 print-row">
            @forelse($summary as $data)
            @php
                if (request('start_date') && request('end_date')) {
                    $period = \Carbon\Carbon::parse(request('start_date'))->translatedFormat('d F Y') . ' - ' . \Carbon\Carbon::parse(request('end_date'))->translatedFormat('d F Y');
                } elseif (request('month')) {
                    $period = \Carbon\Carbon::parse(request('month'))->translatedFormat('F Y');
                } elseif (request('date')) {
                    $period = \Carbon\Carbon::parse(request('date'))->translatedFormat('d F Y');
                } else {
                    $period = now()->translatedFormat('F Y');
                }
                $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $data['user']->phone);
                
                // Brand Resolution (similar to ID Card logic)
                $roleLabel = strtolower(trim((string) ($data['user']->role?->label ?: $data['user']->role?->name ?: '')));
                $defaultLogo = (string) (\App\Models\Setting::getValue('store_logo') ?: '');
                
                if (str_contains($roleLabel, 'wash')) {
                    $brandName = (string) (\App\Models\Setting::getValue('brand_gtwash_name') ?: 'GTWASH');
                    $logo = (string) (\App\Models\Setting::getValue('brand_gtwash_logo') ?: $defaultLogo);
                    $brandKey = 'gtwash';
                    $accentColor = '#16a34a'; // Green
                } elseif (str_contains($roleLabel, 'net') || str_contains($roleLabel, 'network') || str_contains($roleLabel, 'internet')) {
                    $brandName = (string) (\App\Models\Setting::getValue('brand_mstorenet_name') ?: 'MSTORE.NET');
                    $logo = (string) (\App\Models\Setting::getValue('brand_mstorenet_logo') ?: $defaultLogo);
                    $brandKey = 'mstorenet';
                    $accentColor = '#2563eb'; // Blue
                } else {
                    $brandName = (string) (\App\Models\Setting::getValue('brand_mstore_name') ?: \App\Models\Setting::getValue('store_name') ?: 'MSTORE');
                    $logo = (string) (\App\Models\Setting::getValue('brand_mstore_logo') ?: $defaultLogo);
                    $brandKey = 'mstore';
                    $accentColor = '#ea580c'; // Orange
                }
                
                $logoUrl = $logo ? (str_starts_with($logo, 'http') ? $logo : asset($logo)) : asset('img/logo.png');
            @endphp
            
            <div class="col-md-6 print-col">
                <div id="payslip-{{ $data['user']->id }}" class="payslip-card bg-white position-relative brand-{{ $brandKey }}" data-watermark="{{ $brandName }}">
                    
                    <!-- Watermark -->
                    <div class="watermark-centered">{{ $brandName }}</div>

                    <!-- Header -->
                    <div class="p-3 border-bottom border-3 bg-light-subtle position-relative overflow-hidden" style="border-color: {{ $accentColor }} !important;">
                        <div class="d-flex justify-content-between align-items-center position-relative z-1">
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $logoUrl }}" alt="Logo" class="img-fluid" style="height: 40px; width: auto;">
                                <div>
                                    <h5 class="fw-bold text-dark mb-0 tracking-tight" style="font-size: 1.1rem; line-height: 1;">{{ $brandName }}</h5>
                                    <p class="text-muted x-small fw-bold mb-0 mt-1" style="text-transform: uppercase; letter-spacing: 0.5px;">Slip Gaji Digital</p>
                                </div>
                            </div>
                            <div class="text-end print-none">
                                <button onclick="shareToWhatsApp('payslip-{{ $data['user']->id }}', 'Slip-{{ $data['user']->name }}', '{{ $waLink }}')" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm fw-bold">
                                    <i class="fa-brands fa-whatsapp me-1"></i> <span style="font-size: 0.75rem;">Kirim Image</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Info Utama -->
                    <div class="px-3 py-2 bg-white border-bottom border-light">
                        <div class="row g-0">
                            <div class="col-7">
                                <p class="x-small text-uppercase fw-bold text-muted mb-0">Teknisi</p>
                                <p class="fs-6 fw-bold text-dark mb-0">{{ $data['user']->name }}</p>
                            </div>
                            <div class="col-5 text-end">
                                <p class="x-small text-uppercase fw-bold text-muted mb-0">Periode</p>
                                <p class="fs-6 fw-bold text-dark mb-0">{{ $period }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Rincian -->
                    <div class="p-3">
                        <div class="row g-3">
                            <!-- Ringkasan Kehadiran (Horizontal) -->
                            <div class="col-12 mb-2">
                                <div class="d-flex justify-content-between p-2 px-3 bg-light rounded-3 border border-secondary-subtle">
                                    <div class="text-center">
                                        <p class="xx-small fw-bold text-muted text-uppercase mb-0">Hadir</p>
                                        <p class="small fw-bold text-success mb-0">{{ $data['present_count'] }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="xx-small fw-bold text-muted text-uppercase mb-0">Terlambat</p>
                                        <p class="small fw-bold text-warning mb-0">{{ $data['late_count'] }}</p>
                                        @if($data['total_late_minutes'] > 0)
                                        <p class="xx-small text-muted mb-0">({{ $data['total_late_minutes'] }} menit)</p>
                                        @endif
                                    </div>
                                    <div class="text-center">
                                        <p class="xx-small fw-bold text-muted text-uppercase mb-0">Izin</p>
                                        <p class="small fw-bold text-info mb-0">{{ $data['leave_count'] + $data['permit_count'] }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="xx-small fw-bold text-muted text-uppercase mb-0">Sakit</p>
                                        <p class="small fw-bold text-info mb-0">{{ $data['sick_count'] }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="xx-small fw-bold text-muted text-uppercase mb-0">Alpa</p>
                                        <p class="small fw-bold text-danger mb-0">{{ $data['alpha_count'] }}</p>
                                    </div>
                                    @if($data['off_count'] > 0)
                                    <div class="text-center">
                                        <p class="xx-small fw-bold text-muted text-uppercase mb-0">Off</p>
                                        <p class="small fw-bold text-muted mb-0">{{ $data['off_count'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Pendapatan -->
                            <div class="col-6">
                                <p class="x-small fw-bold text-primary text-uppercase mb-2 border-bottom border-2 border-primary-subtle pb-1">Pendapatan</p>
                                <div class="vstack gap-1">
                                    <div class="d-flex justify-content-between x-small fw-bold">
                                        <span class="text-muted">Gaji Pokok Bulanan</span>
                                        <span class="text-dark">{{ number_format($data['monthly_salary'] ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between x-small fw-bold">
                                        <span class="text-muted">Gaji Harian (per hari)</span>
                                        <span class="text-dark">{{ number_format($data['daily_salary'], 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between x-small fw-bold">
                                        <span class="text-muted">Total Gaji Harian ({{ $data['paid_days'] }} hari)</span>
                                        <span class="text-dark">{{ number_format($data['total_daily_salary'], 0, ',', '.') }}</span>
                                    </div>
                                    @if($data['total_bonus'] > 0)
                                        @if($data['bonus_disiplin'] > 0)
                                            <div class="d-flex justify-content-between x-small fw-bold text-success">
                                                <span>Disiplin</span>
                                                <span>+{{ number_format($data['bonus_disiplin'], 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        @if($data['bonus_tanggung_jawab'] > 0)
                                            <div class="d-flex justify-content-between x-small fw-bold text-success">
                                                <span>Tanggung Jwb</span>
                                                <span>+{{ number_format($data['bonus_tanggung_jawab'], 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        @if($data['bonus_absensi'] > 0)
                                            <div class="d-flex justify-content-between x-small fw-bold text-success">
                                                <span>Absensi</span>
                                                <span>+{{ number_format($data['bonus_absensi'], 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        @if($data['bonus_lainnya'] > 0)
                                            <div class="d-flex justify-content-between x-small fw-bold text-success">
                                                <span>Lainnya</span>
                                                <span>+{{ number_format($data['bonus_lainnya'], 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                    @endif
                                    <div class="d-flex justify-content-between x-small mt-1 text-primary border-top border-1 pt-1 fw-bold">
                                        <span>Total Pendapatan</span>
                                        <span>{{ number_format($data['total_daily_salary'] + $data['total_bonus'], 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Potongan -->
                            <div class="col-6 border-start border-2 ps-3">
                                <p class="x-small fw-bold text-danger text-uppercase mb-2 border-bottom border-2 border-danger-subtle pb-1">Potongan</p>
                                <div class="vstack gap-1">
                                    @if($data['late_deduction'] > 0)
                                        <div class="d-flex justify-content-between x-small fw-bold text-danger">
                                            <span>Potongan Terlambat</span>
                                            <span>-{{ number_format($data['late_deduction'], 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    @if($data['kasbon_kantor'] > 0)
                                        <div class="d-flex justify-content-between x-small fw-bold text-danger">
                                            <span>Bon Kantor</span>
                                            <span>-{{ number_format($data['kasbon_kantor'], 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    @if($data['kasbon_warung'] > 0)
                                        <div class="d-flex justify-content-between x-small fw-bold text-danger">
                                            <span>Bon Warung</span>
                                            <span>-{{ number_format($data['kasbon_warung'], 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    @if($data['kasbon_lainnya'] > 0)
                                        <div class="d-flex justify-content-between x-small fw-bold text-danger">
                                            <span>Lainnya</span>
                                            <span>-{{ number_format($data['kasbon_lainnya'], 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-between x-small mt-1 text-danger border-top border-1 pt-1 fw-bold">
                                        <span>Total Potongan</span>
                                        <span>-{{ number_format($data['total_deductions'], 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="d-flex flex-column gap-1 mt-2">
                                    <div class="d-flex justify-content-between x-small text-muted fw-bold">
                                        <span>Hari Kerja / Bulan</span>
                                        <span class="text-dark">{{ $data['working_days'] }} Hari</span>
                                    </div>
                                    <div class="d-flex justify-content-between x-small text-muted fw-bold">
                                        <span>Total Hari Dibayar</span>
                                        <span class="text-dark">{{ $data['paid_days'] }} Hari</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Keterangan Perhitungan -->
                            <div class="col-12">
                                <div class="p-2 bg-info-subtle border border-info rounded-3">
                                    <p class="x-small fw-bold text-info mb-0">Keterangan Perhitungan:</p>
                                    <p class="xx-small text-info-emphasis mb-0">{{ $data['salary_calculation_note'] }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Bersih & Metode -->
                        <div class="mt-4">
                            <div class="p-2 px-3 bg-dark rounded-3 text-white shadow-sm border-start border-4 border-primary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="x-small fw-bold text-uppercase tracking-widest opacity-100">TAKE HOME PAY</span>
                                    <span class="fw-bold text-primary fs-4">
                                        Rp {{ number_format($data['total_salary'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between x-small mt-2 px-1">
                                <div class="text-start">
                                    <span class="text-muted fw-bold d-block">METODE PEMBAYARAN:</span>
                                    <span class="fw-bold text-dark text-uppercase small">{{ $data['user']->bank_name ?: 'CASH' }}</span>
                                </div>
                                <div class="text-end">
                                    @if($data['user']->bank_account_number)
                                        <span class="text-muted fw-bold d-block">NO. REKENING:</span>
                                        <span class="fw-bold text-dark small">{{ $data['user']->bank_account_number }}</span>
                                        <span class="text-muted x-small d-block">a/n {{ $data['user']->bank_account_name ?: $data['user']->name }}</span>
                                    @else
                                        <span class="text-muted fw-bold d-block">STATUS:</span>
                                        <span class="fw-bold text-success small text-uppercase">PAID</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-3 py-2 border-top border-2 bg-light-subtle d-flex justify-content-between align-items-center">
                        <span class="xx-small text-muted fw-bold" style="font-size: 0.6rem;">ID: #{{ str_pad($data['user']->id, 4, '0', STR_PAD_LEFT) }} | {{ now()->format('d/m/y H:i') }}</span>
                        <div class="d-flex gap-4">
                            <div class="text-center">
                                <div class="border-top border-2 mt-1" style="width: 60px; border-color: #666 !important;"></div>
                                <p class="xx-small text-dark fw-bold mb-0 mt-1" style="font-size: 0.6rem;">Penerima</p>
                            </div>
                            <div class="text-center">
                                <div class="border-top border-2 mt-1" style="width: 60px; border-color: #666 !important;"></div>
                                <p class="xx-small text-dark fw-bold mb-0 mt-1" style="font-size: 0.6rem;">Finance</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div class="bg-white p-5 rounded-4 shadow-sm border">
                    <i class="fa-solid fa-receipt fa-3x text-muted mb-3 opacity-25"></i>
                    <h5 class="fw-bold text-dark">Tidak Ada Data Absensi</h5>
                    <p class="text-muted small">Belum ada aktivitas absensi pada periode ini ({{ request('month') ?: now()->translatedFormat('F Y') }}).</p>
                    <a href="{{ route('attendance.create') }}" class="btn btn-primary btn-sm px-4 rounded-pill">
                        Mulai Absen Sekarang
                    </a>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    function shareToWhatsApp(id, filename, waLink) {
        const element = document.getElementById(id);
        const originalStyle = element.getAttribute('style');
        
        // Optimasi untuk Screenshot
        element.style.borderRadius = "0px";
        element.style.boxShadow = "none";
        element.style.border = "1px solid #eee";

        const options = {
            backgroundColor: '#ffffff',
            scale: 3,
            logging: false,
            useCORS: true,
            allowTaint: true
        };
        
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        html2canvas(element, options).then(canvas => {
            element.setAttribute('style', originalStyle || '');

            canvas.toBlob(function(blob) {
                const file = new File([blob], filename + ".png", { type: "image/png" });
                
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    navigator.share({
                        files: [file],
                        title: 'Slip Gaji Digital',
                        text: 'Halo, berikut adalah rincian slip gaji Anda periode ini.'
                    })
                    .then(() => resetBtn(btn, originalHtml))
                    .catch(() => fallbackDownload(canvas, filename, waLink, btn, originalHtml));
                } else {
                    fallbackDownload(canvas, filename, waLink, btn, originalHtml);
                }
            });
        });
    }

    function resetBtn(btn, html) {
        btn.innerHTML = html;
        btn.disabled = false;
    }

    function fallbackDownload(canvas, filename, waLink, btn, html) {
        const link = document.createElement('a');
        link.download = filename + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        resetBtn(btn, html);
        // Custom message box sebagai pengganti alert
        console.log('Gambar diunduh. Alihkan ke WhatsApp...');
        window.open(waLink, '_blank');
    }
</script>

<style>
    .x-small { font-size: 0.8rem; }
    .xx-small { font-size: 0.7rem; }
    .btn-xs { padding: 0.15rem 0.4rem; font-size: 0.7rem; }
    .fw-black { font-weight: 900; }
    .tracking-tight { letter-spacing: -0.02em; }
    .tracking-widest { letter-spacing: 0.1em; }
    .bg-light-subtle { background-color: #f8fafc !important; }
    .payslip-container { background-color: #f8fafc; min-height: 100vh; }
    
    .payslip-card {
        border-radius: 1.25rem;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .watermark-centered {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
        font-size: 3.5rem;
        font-weight: 900;
        color: rgba(0, 0, 0, 0.04);
        white-space: nowrap;
        z-index: 0;
        pointer-events: none;
        text-transform: uppercase;
        letter-spacing: 0.3em;
        width: 150%;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .watermark {
        display: none; /* Hide old watermark */
    }

    @media print {
        @page {
            size: A4;
            margin: 10mm;
        }
        body { background-color: white !important; margin: 0; padding: 0; }
        .payslip-container { background-color: white !important; padding: 0 !important; }
        .print-none { display: none !important; }
        
        .print-row {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 10mm !important;
            padding: 5mm !important;
        }
        
        .print-col {
            padding: 0 !important;
            margin: 0 !important;
            page-break-inside: avoid;
        }

        .payslip-card {
            border: 2px solid #333 !important; /* Lebih tebal di print */
            border-radius: 0 !important;
            box-shadow: none !important;
            height: 120mm !important; /* Pas 4 slip per A4 (2 baris x 2 kolom) */
            margin-bottom: 0 !important;
        }

        .text-dark, .text-muted { color: black !important; }
        .fw-bold { font-weight: 800 !important; }
        
        .bg-dark { background-color: #000 !important; -webkit-print-color-adjust: exact; }
        .bg-primary { background-color: #000 !important; -webkit-print-color-adjust: exact; }
        .text-white { color: white !important; -webkit-print-color-adjust: exact; }
        .text-primary { color: black !important; font-weight: 900 !important; -webkit-print-color-adjust: exact; }
        .border-primary, .border-danger { border-color: black !important; }
    }
</style>
@endsection