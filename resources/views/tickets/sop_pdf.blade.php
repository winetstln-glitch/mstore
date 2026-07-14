<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panduan SOP dan Tiket Teknisi</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; line-height: 1.45; }
        h1, h2, h3 { margin: 0; }
        .muted { color: #6b7280; }
        .mb-8 { margin-bottom: 8px; }
        .mb-12 { margin-bottom: 12px; }
        .mb-16 { margin-bottom: 16px; }
        .mb-20 { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: 700; border-bottom: 1px solid #d1d5db; padding-bottom: 6px; margin-bottom: 8px; }
        .box { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        .table th { background: #f3f4f6; text-align: left; }
        ul { margin: 6px 0 10px 16px; padding: 0; }
    </style>
</head>
<body>
    <h1 class="mb-8">Panduan SOP dan Tiket Teknisi</h1>
    <div class="muted mb-16">Dokumen panduan umum teknisi. Dicetak pada {{ $generatedAt->format('d M Y H:i') }}.</div>

    <div class="section-title">1) Persiapan Sebelum Berangkat</div>
    <ul>
        <li>Pelajari detail tiket: jenis gangguan, alamat, kontak pelanggan, riwayat tiket, ODP, dan catatan teknisi sebelumnya.</li>
        <li>Konfirmasi jadwal kunjungan dengan pelanggan dan pastikan akses lokasi tersedia.</li>
        <li>Siapkan alat kerja dan APD sesuai standar keselamatan.</li>
    </ul>

    <div class="section-title">2) SOP Instalasi / Aktivasi</div>
    <ul>
        <li>Survey jalur kabel paling aman, rapi, dan minim risiko.</li>
        <li>Pasang kabel sesuai standar dan beri pengikat/label.</li>
        <li>Konfigurasi ONU/router, SSID, dan lakukan uji koneksi.</li>
        <li>Edukasi pelanggan dan dokumentasikan hasil pekerjaan.</li>
    </ul>

    <div class="section-title">3) SOP Pemeliharaan Berkala</div>
    <ul>
        <li>Periksa kualitas sinyal, performa koneksi, dan kondisi fisik perangkat.</li>
        <li>Bersihkan perangkat, rapikan instalasi, dan update konfigurasi jika perlu.</li>
        <li>Catat hasil pemeriksaan secara detail di tiket.</li>
    </ul>

    <div class="section-title">4) SOP Penanganan Gangguan</div>
    <ul>
        <li>Identifikasi gejala: LOS merah, internet lambat, putus-putus, gagal autentikasi, atau perangkat mati.</li>
        <li>Lakukan pengecekan berurutan dari sisi pelanggan ke jaringan inti.</li>
        <li>Lakukan tindakan korektif, lalu uji stabilitas koneksi.</li>
        <li>Jika perlu eskalasi, sertakan bukti teknis dan langkah yang sudah dicoba.</li>
    </ul>

    <div class="section-title">5) Panduan Tiket</div>
    <ul>
        <li>Baca detail tiket dengan lengkap sebelum berangkat: jenis pekerjaan, lokasi, kontak, dan catatan sebelumnya.</li>
        <li>Untuk instalasi baru/pergantian ONU, SN ONU dan WAN MAC wajib dicatat sebelum tiket diselesaikan.</li>
        <li>Isi catatan penyelesaian secara ringkas: akar masalah, tindakan, material yang digunakan, dan hasil uji.</li>
        <li>Pastikan bukti foto pekerjaan tersedia sebelum klik selesai tiket.</li>
    </ul>

    <div class="section-title">6) Estimasi Waktu Pekerjaan</div>
    <table class="table">
        <thead>
            <tr>
                <th>Tahap</th>
                <th>Target Waktu</th>
                <th>Catatan Kontrol</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Diagnosa awal di lokasi</td>
                <td>10 - 20 menit</td>
                <td>Pastikan akar masalah jelas sebelum tindakan.</td>
            </tr>
            <tr>
                <td>Tindakan teknis utama</td>
                <td>20 - 60 menit</td>
                <td>Fokus pada perbaikan inti, hindari pekerjaan di luar scope tiket.</td>
            </tr>
            <tr>
                <td>Verifikasi hasil & uji koneksi</td>
                <td>10 - 20 menit</td>
                <td>Minimal cek ping/browsing/stabilitas sesuai kebutuhan.</td>
            </tr>
            <tr>
                <td>Dokumentasi & closing tiket</td>
                <td>5 - 15 menit</td>
                <td>Foto sebelum/sesudah, catatan penyebab, tindakan, dan hasil.</td>
            </tr>
        </tbody>
    </table>

    <div class="box" style="margin-top: 10px;">
        <strong>Aturan Monitoring Durasi:</strong>
        <ul>
            <li>Jika melebihi estimasi ticket, wajib isi alasan keterlambatan di catatan penyelesaian.</li>
            <li>Jika progres tidak signifikan dalam 60 menit pertama, lakukan eskalasi ke koordinator/NOC.</li>
            <li>Prioritas tinggi harus didahulukan dan dipantau ketat.</li>
        </ul>
    </div>
</body>
</html>
