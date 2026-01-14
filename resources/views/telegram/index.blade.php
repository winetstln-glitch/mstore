@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-info">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-brands fa-telegram me-2"></i>{{ __('Telegram Settings') }}</h5>
            </div>

            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-circle-info me-2"></i>{{ __('Panduan Pembuatan Telegram Bot') }}</h6>
                    <p class="mb-0">{{ __('Ikuti langkah-langkah berikut untuk mendapatkan Token Bot Telegram:') }}</p>
                    <ol class="mb-2 mt-2">
                        <li>{{ __('Buka aplikasi Telegram dan cari akun') }} <strong>@BotFather</strong>.</li>
                        <li>{{ __('Kirim pesan') }} <code>/newbot</code> {{ __('untuk membuat bot baru.') }}</li>
                        <li>{{ __('Ikuti petunjuk untuk memberi nama dan username bot Anda.') }}</li>
                        <li>{{ __('Setelah berhasil, Anda akan menerima') }} <strong>Token API</strong>.</li>
                        <li>{{ __('Salin token tersebut dan tempelkan pada kolom input di bawah ini.') }}</li>
                    </ol>
                    <hr>
                    <p class="mb-0 small">
                        <strong>{{ __('Cara Mendapatkan Chat ID:') }}</strong> 
                        {{ __('Minta teknisi untuk mengirim pesan ke bot yang baru dibuat, lalu gunakan bot lain seperti') }} 
                        <strong>@userinfobot</strong> {{ __('untuk melihat ID mereka.') }}
                    </p>
                    <p class="mb-0 small mt-2">
                        <strong>{{ __('Cara Mendapatkan Group ID:') }}</strong>
                        <ol class="small mb-0">
                            <li>{{ __('Buat grup baru di Telegram dan tambahkan bot Anda ke dalamnya.') }}</li>
                            <li>{{ __('Kirim pesan apapun di grup tersebut.') }}</li>
                            <li>{{ __('Buka browser dan kunjungi:') }} <code>https://api.telegram.org/bot&lt;TOKEN&gt;/getUpdates</code></li>
                            <li>{{ __('Cari "chat":{"id": -123xxxx} di hasil JSON. Angka yang diawali tanda minus (-) adalah Group ID.') }}</li>
                        </ol>
                    </p>
                    
                    <hr>
                    <h6 class="fw-bold mt-3"><i class="fa-solid fa-robot me-2"></i>{{ __('Perintah Bot & Listener') }}</h6>
                    <p class="mb-2">{{ __('Gunakan perintah berikut di grup atau chat pribadi dengan bot:') }}</p>
                     <ul class="mb-3">
                         <li><code>/cek_tiket [No. Tiket]</code> - {{ __('Cek status tiket (Contoh: /cek_tiket TKT-2024...)') }}</li>
                         <li><code>/cek_modem [ID/SN]</code> - {{ __('Cek status modem pelanggan (Online/Offline)') }}</li>
                         <li><code>/cek_tiket_all</code> - {{ __('Rekap semua tiket dan daftar 20 tiket aktif terbaru') }}</li>
                         <li><code>/cek_modem_all</code> - {{ __('Rekap semua modem pelanggan (ONLINE/OFFLINE) dan tampilkan 20 OFFLINE') }}</li>
                         <li><code>/bantuan</code> - {{ __('Menampilkan daftar bantuan') }}</li>
                     </ul>
                    <div class="alert alert-warning py-2">
                        <small><i class="fa-solid fa-triangle-exclamation me-1"></i> {{ __('Untuk mengaktifkan fitur balas otomatis, jalankan perintah ini di terminal server:') }}</small><br>
                        <code class="user-select-all">php artisan telegram:listen</code>
                    </div>
                </div>

                <form action="{{ route('telegram.update') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="telegram_bot_token" class="form-label fw-bold">{{ __('Telegram Bot Token') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                            <input type="text" name="telegram_bot_token" id="telegram_bot_token" value="{{ $setting->value }}" class="form-control" placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                        </div>
                        <div class="form-text">{{ __('Token ini digunakan untuk mengirim notifikasi tiket ke teknisi.') }}</div>
                    </div>

                    <div class="mb-4">
                        <label for="telegram_technician_group_chat_id" class="form-label fw-bold">{{ __('Technician Group Chat ID') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-users"></i></span>
                            <input type="text" name="telegram_technician_group_chat_id" id="telegram_technician_group_chat_id" value="{{ $groupChatId->value }}" class="form-control" placeholder="-100xxxxxxxxx">
                        </div>
                        <div class="form-text">{{ __('ID Grup Telegram teknisi (diawali dengan tanda minus). Bot akan mengirim notifikasi tiket baru ke grup ini.') }}</div>
                    </div>

                    <div class="mb-4">
                        <label for="telegram_ticket_template" class="form-label fw-bold">{{ __('Template Pesan Notifikasi Tiket') }}</label>
                        <textarea name="telegram_ticket_template" id="telegram_ticket_template" rows="8" class="form-control font-monospace">{{ $template->value }}</textarea>
                        <div class="form-text mt-2">
                            <strong>{{ __('Variables Available:') }}</strong><br>
                            <code>{ticket_number}</code> - {{ __('Nomor Tiket') }}<br>
                            <code>{subject}</code> - {{ __('Judul Masalah') }}<br>
                            <code>{customer_name}</code> - {{ __('Nama Pelanggan') }}<br>
                            <code>{technicians}</code> - {{ __('Nama Teknisi') }}<br>
                            <code>{coordinator}</code> - {{ __('Nama Koordinator') }}<br>
                            <code>{location}</code> - {{ __('Alamat / Koordinat') }}<br>
                            <code>{priority}</code> - {{ __('Prioritas (Low, Medium, High)') }}<br>
                            <code>{description}</code> - {{ __('Deskripsi Masalah') }}<br>
                            <code>{location_link}</code> - {{ __('Link Google Maps') }}
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-success" onclick="document.getElementById('test-form').submit()">
                            <i class="fa-brands fa-telegram me-1"></i> {{ __('Test Send Message') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Save Settings') }}
                        </button>
                    </div>
                </form>

                <form id="test-form" action="{{ route('telegram.test') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
