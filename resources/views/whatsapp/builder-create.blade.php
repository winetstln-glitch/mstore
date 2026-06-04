@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('whatsapp.builder.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
            <h1 class="h3 mb-0">
                <i class="fab fa-whatsapp text-success"></i> Tambah Menu WhatsApp
            </h1>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">📋 Pilih Template</h5>
            <div class="row">
                <div class="col-md-6">
                    <select id="templateSelect" class="form-select">
                        <option value="">-- Pilih Template --</option>
                        <option value="greeting">Salam & Halo</option>
                        <option value="help">Menu Bantuan</option>
                        <option value="attendance_in">Absensi Masuk</option>
                        <option value="attendance_out">Absensi Pulang</option>
                        <option value="schedule">Jadwal Kerja</option>
                        <option value="voucher">Voucher Internet</option>
                        <option value="ticket">Tiket Support</option>
                        <option value="contact">Kontak Kami</option>
                        <option value="thanks">Terima Kasih</option>
                        <option value="greeting_time">Salam Waktu (Pagi/Siang/Malam)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <button type="button" id="applyTemplateBtn" class="btn btn-primary">
                        <i class="fa-solid fa-magic"></i> Terapkan Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.builder.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Keyword</label>
                        <input type="text" name="keyword" id="keywordInput" class="form-control @error('keyword') is-invalid @enderror" required placeholder="contoh: halo, jadwal hari ini">
                        @error('keyword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipe Balasan</label>
                        <select name="type" id="typeSelect" class="form-select @error('type') is-invalid @enderror" required id="menuType">
                            <option value="text">Teks</option>
                            <option value="image">Gambar</option>
                            <option value="document">Dokumen</option>
                            <option value="button">Tombol</option>
                            <option value="list">List</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Teks Balasan</label>
                    <textarea name="response_text" id="responseTextInput" class="form-control @error('response_text') is-invalid @enderror" rows="5" placeholder="Masukkan teks balasan...">{{ old('response_text') }}</textarea>
                    <small class="text-muted">
                        Variabel yang bisa digunakan: {nama_user}, {jam_sekarang}, {tanggal_sekarang}
                    </small>
                    @error('response_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" id="fileUploadSection" style="display: none;">
                    <label class="form-label fw-bold">File Media</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" id="menuFile">
                    <small class="text-muted">Maksimal 10 MB</small>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Priority</label>
                        <input type="number" name="priority" id="priorityInput" class="form-control @error('priority') is-invalid @enderror" value="0" min="0">
                        <small class="text-muted">Semakin tinggi angka, semakin diprioritaskan</small>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="enableFuzzyMatch" name="enable_fuzzy_match" value="1" checked>
                            <label class="form-check-label" for="enableFuzzyMatch">Enable Fuzzy Matching</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-save"></i> Simpan Menu
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const templates = {
    greeting: {
        keyword: 'halo',
        type: 'text',
        response_text: "Halo {nama_user}! 👋\n\nSelamat datang di WhatsApp Bot kami.\nKetik *bantuan* untuk melihat menu yang tersedia.",
        priority: 10,
        enable_fuzzy_match: true
    },
    help: {
        keyword: 'bantuan',
        type: 'text',
        response_text: "📋 *Menu Bantuan WhatsApp Bot*\n\nBerikut perintah yang bisa Anda gunakan:\n\n1️⃣ *halo/hi* - Sapa bot\n2️⃣ *bantuan* - Menampilkan menu ini\n3️⃣ *absen* - Info absensi\n4️⃣ *pulang* - Info absen pulang\n5️⃣ *jadwal* - Lihat jadwal kerja\n6️⃣ *voucher* - Info voucher internet\n7️⃣ *tiket* - Buat tiket support\n8️⃣ *kontak* - Kontak kami\n\nTerima kasih! 🙏",
        priority: 10,
        enable_fuzzy_match: true
    },
    attendance_in: {
        keyword: 'absen',
        type: 'text',
        response_text: "⏰ *Informasi Absensi Masuk*\n\nUntuk melakukan absensi masuk:\n1. Buka aplikasi MStore\n2. Klik menu Absensi\n3. Tekan tombol \"Clock In\"\n\nAtau kunjungi: " + window.location.origin + "/attendance/create\n\nWaktu absensi masuk mulai pukul 07:00!",
        priority: 8,
        enable_fuzzy_match: true
    },
    attendance_out: {
        keyword: 'pulang',
        type: 'text',
        response_text: "🏠 *Informasi Absensi Pulang*\n\nUntuk melakukan absensi pulang:\n1. Buka aplikasi MStore\n2. Klik menu Absensi\n3. Tekan tombol \"Clock Out\"\n\nAtau kunjungi: " + window.location.origin + "/attendance\n\nPastikan Anda sudah selesai bekerja!",
        priority: 8,
        enable_fuzzy_match: true
    },
    schedule: {
        keyword: 'jadwal',
        type: 'text',
        response_text: "📅 *Jadwal Kerja*\n\n🕐 Shift 1: 08:00 - 17:00\n🕑 Shift 2: 15:00 - 00:00\n\nUntuk melihat jadwal pribadi Anda:\n" + window.location.origin + "/schedules\n\nHari libur sesuai kalender nasional!",
        priority: 8,
        enable_fuzzy_match: true
    },
    voucher: {
        keyword: 'voucher',
        type: 'text',
        response_text: "💳 *Informasi Voucher Internet*\n\nUntuk membeli voucher internet:\n1. Buka halaman voucher di website\n2. Pilih paket yang diinginkan\n3. Lakukan pembayaran\n\nKunjungi: " + window.location.origin + "/voucher/list\n\nUntuk pertanyaan lebih lanjut, silakan hubungi support!",
        priority: 7,
        enable_fuzzy_match: true
    },
    ticket: {
        keyword: 'tiket',
        type: 'text',
        response_text: "🎫 *Buat Tiket Support*\n\nUntuk membuat tiket support:\n1. Buka aplikasi MStore\n2. Klik menu Tiket\n3. Isi formulir dan kirim\n\nKunjungi: " + window.location.origin + "/tickets/create\n\nTim kami akan segera membantu Anda!",
        priority: 7,
        enable_fuzzy_match: true
    },
    contact: {
        keyword: 'kontak',
        type: 'text',
        response_text: "📞 *Kontak Kami*\n\n📧 Email: support@mstore.com\n📱 Telepon: +62 812-3456-7890\n🏠 Alamat: Jl. Teknologi No. 123, Jakarta\n\nJam operasional: 08:00 - 17:00 WIB\n\nTerima kasih telah menghubungi kami!",
        priority: 7,
        enable_fuzzy_match: true
    },
    thanks: {
        keyword: 'terima kasih',
        type: 'text',
        response_text: "Sama-sama {nama_user}! 🙏\n\nSenang bisa membantu Anda.\nJika ada pertanyaan lain, silakan hubungi kami kembali!",
        priority: 6,
        enable_fuzzy_match: true
    },
    greeting_time: {
        keyword: 'selamat pagi',
        type: 'text',
        response_text: "Selamat pagi {nama_user}! 🌅\n\nSemoga hari ini penuh semangat dan produktivitas!\n\nKetik *bantuan* jika Anda membutuhkan bantuan.",
        priority: 6,
        enable_fuzzy_match: true
    }
};

document.getElementById('applyTemplateBtn').addEventListener('click', function() {
    const templateKey = document.getElementById('templateSelect').value;
    if (templateKey && templates[templateKey]) {
        const template = templates[templateKey];
        document.getElementById('keywordInput').value = template.keyword;
        document.getElementById('typeSelect').value = template.type;
        document.getElementById('responseTextInput').value = template.response_text;
        document.getElementById('priorityInput').value = template.priority;
        document.getElementById('enableFuzzyMatch').checked = template.enable_fuzzy_match;
    }
});

document.getElementById('typeSelect').addEventListener('change', function() {
    const fileSection = document.getElementById('fileUploadSection');
    if (this.value === 'image' || this.value === 'document') {
        fileSection.style.display = 'block';
    } else {
        fileSection.style.display = 'none';
    }
});
</script>
@endsection
