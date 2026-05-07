@extends('layouts.app')

@section('content')
<div class="payslip-container py-3">
    <div class="container-fluid max-w-5xl mx-auto">
        
        <!-- Kontrol Aksi (Sembunyi saat Cetak) -->
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border mb-4 print-none">
            <div>
                <h5 class="fw-bold text-dark mb-0">Manajemen Slip Gaji</h5>
                <p class="text-muted x-small mb-0">Mode Optimasi: 4-6 Slip per Halaman (A4)</p>
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
            @foreach($summary as $data)
            @php
                $period = request('month') ? \Carbon\Carbon::parse(request('month'))->translatedFormat('F Y') : now()->translatedFormat('F Y');
                $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $data['user']->phone);
            @endphp
            
            <div class="col-md-6 print-col">
                <div id="payslip-{{ $data['user']->id }}" class="payslip-card bg-white position-relative">
                    
                    <!-- Watermark -->
                    <div class="watermark">{{ config('app.name') }}</div>

                    <!-- Header -->
                    <div class="p-3 border-bottom border-3 border-primary bg-light-subtle position-relative overflow-hidden">
                        <div class="d-flex justify-content-between align-items-center position-relative z-1">
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ asset('img/logo.png') }}" alt="Logo" class="img-fluid" style="height: 40px; width: auto;">
                                <div>
                                    <h5 class="fw-bold text-dark mb-0 tracking-tight" style="font-size: 1.1rem; line-height: 1;">{{ config('app.name', 'MSTORE') }}</h5>
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
                                        <p class="xx-small fw-bold text-muted text-uppercase mb-0">Izin</p>
                                        <p class="small fw-bold text-info mb-0">{{ $data['leave_count'] + $data['permit_count'] }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="xx-small fw-bold text-muted text-uppercase mb-0">Sakit</p>
                                        <p class="small fw-bold text-warning mb-0">{{ $data['sick_count'] }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="xx-small fw-bold text-muted text-uppercase mb-0">Alpa</p>
                                        <p class="small fw-bold text-danger mb-0">{{ $data['alpha_count'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Pendapatan -->
                            <div class="col-6">
                                <p class="x-small fw-bold text-primary text-uppercase mb-2 border-bottom border-2 border-primary-subtle pb-1">Pendapatan</p>
                                <div class="vstack gap-1">
                                    <div class="d-flex justify-content-between x-small fw-bold">
                                        <span class="text-muted">Gaji Pokok</span>
                                        <span class="text-dark">{{ number_format($data['daily_salary'] * $data['paid_days'], 0, ',', '.') }}</span>
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
                                    @endif
                                </div>
                            </div>
                            <!-- Potongan -->
                            <div class="col-6 border-start border-2 ps-3">
                                <p class="x-small fw-bold text-danger text-uppercase mb-2 border-bottom border-2 border-danger-subtle pb-1">Potongan</p>
                                <div class="vstack gap-1">
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
                                    <div class="d-flex justify-content-between x-small mt-1 text-muted border-top border-2 pt-1 fw-bold">
                                        <span>Total Hari</span>
                                        <span class="text-dark">{{ $data['paid_days'] }} Hari</span>
                                    </div>
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
            @endforeach
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
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        border: 1px solid #dee2e6;
        overflow: hidden;
        background-color: white !important;
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