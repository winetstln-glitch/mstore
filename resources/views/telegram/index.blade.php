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

                @if(session('preview_ip_down'))
                    <div class="alert alert-danger">
                        <h6 class="fw-bold mb-2"><i class="fa-solid fa-eye me-2"></i>{{ __('Preview Template IP DOWN') }}</h6>
                        <pre class="mb-0 small">{{ session('preview_ip_down') }}</pre>
                    </div>
                @endif

                @if(session('preview_ip_up'))
                    <div class="alert alert-primary">
                        <h6 class="fw-bold mb-2"><i class="fa-solid fa-eye me-2"></i>{{ __('Preview Template IP UP') }}</h6>
                        <pre class="mb-0 small">{{ session('preview_ip_up') }}</pre>
                    </div>
                @endif

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
                        <label class="form-label fw-bold">{{ __('Group Notification Settings') }}</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light border-0 mb-3">
                                    <div class="card-body">
                                        <label for="telegram_ticket_group_id" class="form-label fw-bold">{{ __('Tiket Notification') }}</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="telegram_ticket_notification_enabled" name="telegram_ticket_notification_enabled" value="1" {{ \App\Models\Setting::getValue('telegram_ticket_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="telegram_ticket_notification_enabled">{{ __('Aktifkan Notifikasi Tiket') }}</label>
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text"><i class="fa-solid fa-users"></i></span>
                                            <input type="text" name="telegram_ticket_group_id" id="telegram_ticket_group_id" value="{{ \App\Models\Setting::getValue('telegram_ticket_group_id') }}" class="form-control" placeholder="-100xxxxxxxxx">
                                        </div>
                                        <div class="form-text small">{{ __('ID Grup Telegram untuk notifikasi tiket baru & update status.') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0 mb-3">
                                    <div class="card-body">
                                        <label for="telegram_attendance_group_id" class="form-label fw-bold">{{ __('Absensi Notification') }}</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="telegram_attendance_notification_enabled" name="telegram_attendance_notification_enabled" value="1" {{ \App\Models\Setting::getValue('telegram_attendance_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="telegram_attendance_notification_enabled">{{ __('Aktifkan Notifikasi Absensi') }}</label>
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text"><i class="fa-solid fa-users"></i></span>
                                            <input type="text" name="telegram_attendance_group_id" id="telegram_attendance_group_id" value="{{ \App\Models\Setting::getValue('telegram_attendance_group_id') }}" class="form-control" placeholder="-100xxxxxxxxx">
                                        </div>
                                        <div class="form-text small">{{ __('ID Grup Telegram untuk notifikasi absensi teknisi.') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light border-0 mb-3">
                                    <div class="card-body">
                                        <label for="telegram_modem_up_group_id" class="form-label fw-bold">{{ __('Modem UP Notification') }}</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="telegram_modem_up_notification_enabled" name="telegram_modem_up_notification_enabled" value="1" {{ \App\Models\Setting::getValue('telegram_modem_up_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="telegram_modem_up_notification_enabled">{{ __('Aktifkan Notifikasi Modem UP') }}</label>
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text"><i class="fa-solid fa-users"></i></span>
                                            <input type="text" name="telegram_modem_up_group_id" id="telegram_modem_up_group_id" value="{{ \App\Models\Setting::getValue('telegram_modem_up_group_id') }}" class="form-control" placeholder="-100xxxxxxxxx">
                                        </div>
                                        <div class="form-text small">{{ __('ID Grup Telegram untuk notifikasi modem UP (Recovery).') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light border-0 mb-3">
                                    <div class="card-body">
                                        <label for="telegram_modem_down_group_id" class="form-label fw-bold">{{ __('Modem DOWN Notification') }}</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="telegram_modem_down_notification_enabled" name="telegram_modem_down_notification_enabled" value="1" {{ \App\Models\Setting::getValue('telegram_modem_down_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="telegram_modem_down_notification_enabled">{{ __('Aktifkan Notifikasi Modem DOWN') }}</label>
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text"><i class="fa-solid fa-users"></i></span>
                                            <input type="text" name="telegram_modem_down_group_id" id="telegram_modem_down_group_id" value="{{ \App\Models\Setting::getValue('telegram_modem_down_group_id') }}" class="form-control" placeholder="-100xxxxxxxxx">
                                        </div>
                                        <div class="form-text small">{{ __('ID Grup Telegram untuk notifikasi modem DOWN.') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light border-0 mb-3">
                                    <div class="card-body">
                                        <label for="telegram_modem_recap_group_id" class="form-label fw-bold">{{ __('Modem RECAP Notification') }}</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="telegram_modem_recap_notification_enabled" name="telegram_modem_recap_notification_enabled" value="1" {{ \App\Models\Setting::getValue('telegram_modem_recap_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="telegram_modem_recap_notification_enabled">{{ __('Aktifkan Notifikasi Modem RECAP') }}</label>
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text"><i class="fa-solid fa-users"></i></span>
                                            <input type="text" name="telegram_modem_recap_group_id" id="telegram_modem_recap_group_id" value="{{ \App\Models\Setting::getValue('telegram_modem_recap_group_id') }}" class="form-control" placeholder="-100xxxxxxxxx">
                                        </div>
                                        <div class="form-text small">{{ __('ID Grup Telegram untuk notifikasi rekap berkala GenieACS.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="telegram_technician_group_chat_id" value="{{ $groupChatId->value }}">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ __('Notifikasi Monitoring IP') }}</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="telegram_notify_ip_down" name="telegram_notify_ip_down" value="1" {{ (string)($notifyIpDown->value ?? '1') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="telegram_notify_ip_down">{{ __('Kirim notifikasi saat IP/ONU DOWN') }}</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="telegram_notify_ip_up" name="telegram_notify_ip_up" value="1" {{ (string)($notifyIpUp->value ?? '1') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="telegram_notify_ip_up">{{ __('Kirim notifikasi saat IP/ONU UP (recovery)') }}</label>
                        </div>
                        <div class="form-text">{{ __('Notifikasi UP/DOWN dipakai oleh monitor GenieACS yang berjalan berkala, termasuk detail per mode (ONLINE/OFFLINE), pelanggan, dan IP address.') }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ __('Akurasi Monitoring & Retry Telegram') }}</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="genieacs_online_threshold_minutes" class="form-label">{{ __('Batas Online (menit)') }}</label>
                                <input type="number" min="1" max="180" name="genieacs_online_threshold_minutes" id="genieacs_online_threshold_minutes" value="{{ (int)($onlineThresholdMinutes->value ?? 15) }}" class="form-control">
                                <div class="form-text">{{ __('ONU dianggap online jika Last Inform masih dalam batas ini.') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="network_monitor_down_confirm_checks" class="form-label">{{ __('Konfirmasi DOWN (jumlah cek)') }}</label>
                                <input type="number" min="1" max="10" name="network_monitor_down_confirm_checks" id="network_monitor_down_confirm_checks" value="{{ (int)($downConfirmChecks->value ?? 2) }}" class="form-control">
                                <div class="form-text">{{ __('Status OFFLINE baru dianggap valid setelah lolos jumlah cek ini.') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="network_monitor_up_confirm_checks" class="form-label">{{ __('Konfirmasi UP (jumlah cek)') }}</label>
                                <input type="number" min="1" max="10" name="network_monitor_up_confirm_checks" id="network_monitor_up_confirm_checks" value="{{ (int)($upConfirmChecks->value ?? 2) }}" class="form-control">
                                <div class="form-text">{{ __('Status ONLINE (recovery) baru dianggap valid setelah lolos jumlah cek ini.') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="network_monitor_telegram_max_retry_attempts" class="form-label">{{ __('Maks Retry Telegram') }}</label>
                                <input type="number" min="1" max="20" name="network_monitor_telegram_max_retry_attempts" id="network_monitor_telegram_max_retry_attempts" value="{{ (int)($telegramRetryAttempts->value ?? 5) }}" class="form-control">
                                <div class="form-text">{{ __('Jumlah percobaan ulang jika kirim Telegram gagal.') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="network_monitor_telegram_retry_backoff_minutes" class="form-label">{{ __('Jeda Retry Telegram (menit)') }}</label>
                                <input type="number" min="1" max="120" name="network_monitor_telegram_retry_backoff_minutes" id="network_monitor_telegram_retry_backoff_minutes" value="{{ (int)($telegramRetryBackoffMinutes->value ?? 5) }}" class="form-control">
                                <div class="form-text">{{ __('Interval jeda antar percobaan retry Telegram.') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="telegram_monitor_detail_list_limit" class="form-label">{{ __('Maks Detail List ONLINE/OFFLINE') }}</label>
                                <input type="number" min="5" max="100" name="telegram_monitor_detail_list_limit" id="telegram_monitor_detail_list_limit" value="{{ (int)($telegramMonitorDetailLimit->value ?? 20) }}" class="form-control">
                                <div class="form-text">{{ __('Jumlah maksimal data pelanggan yang ditampilkan per mode pada notifikasi rekap GenieACS.') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="telegram_ip_down_template" class="form-label fw-bold">{{ __('Template Notifikasi IP DOWN') }}</label>
                        <textarea name="telegram_ip_down_template" id="telegram_ip_down_template" rows="8" class="form-control font-monospace">{{ $ipDownTemplate->value }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="telegram_ip_up_template" class="form-label fw-bold">{{ __('Template Notifikasi IP UP (Recovery)') }}</label>
                        <textarea name="telegram_ip_up_template" id="telegram_ip_up_template" rows="8" class="form-control font-monospace">{{ $ipUpTemplate->value }}</textarea>
                        <div class="form-text mt-2">
                            <strong>{{ __('Variables Available:') }}</strong><br>
                            <code>{customer_name}</code> - {{ __('Nama Pelanggan') }}<br>
                            <code>{customer_id}</code> - {{ __('ID Pelanggan') }}<br>
                            <code>{customer_pppoe_user}</code> - {{ __('Username PPPoE Pelanggan') }}<br>
                            <code>{customer_phone}</code> - {{ __('No HP Pelanggan') }}<br>
                            <code>{customer_address}</code> - {{ __('Alamat Pelanggan') }}<br>
                            <code>{customer_package}</code> - {{ __('Paket Pelanggan') }}<br>
                            <code>{onu_serial}</code> - {{ __('Serial Number ONU') }}<br>
                            <code>{status}</code> - {{ __('Status ONT/ONU') }}<br>
                            <code>{tr069_ip}</code> - {{ __('IP TR069') }}<br>
                            <code>{connection_request_url}</code> - {{ __('Connection Request URL') }}<br>
                            <code>{last_inform}</code> - {{ __('Waktu Inform Terakhir') }}<br>
                            <code>{reason}</code> - {{ __('Alasan Status') }}
                        </div>
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

                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-success" onclick="document.getElementById('test-form').submit()">
                                <i class="fa-brands fa-telegram me-1"></i> {{ __('Test Send Message') }}
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="document.getElementById('test-ip-down-form').submit()">
                                <i class="fa-solid fa-circle-down me-1"></i> {{ __('Test IP DOWN') }}
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="document.getElementById('preview-ip-down-form').submit()">
                                <i class="fa-solid fa-eye me-1"></i> {{ __('Preview IP DOWN') }}
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('test-ip-up-form').submit()">
                                <i class="fa-solid fa-circle-up me-1"></i> {{ __('Test IP UP') }}
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('preview-ip-up-form').submit()">
                                <i class="fa-solid fa-eye me-1"></i> {{ __('Preview IP UP') }}
                            </button>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Save Settings') }}
                        </button>
                    </div>
                </form>

                <form id="test-form" action="{{ route('telegram.test') }}" method="POST" class="d-none">
                    @csrf
                </form>
                <form id="test-ip-down-form" action="{{ route('telegram.test_ip_down') }}" method="POST" class="d-none">
                    @csrf
                </form>
                <form id="test-ip-up-form" action="{{ route('telegram.test_ip_up') }}" method="POST" class="d-none">
                    @csrf
                </form>
                <form id="preview-ip-down-form" action="{{ route('telegram.preview_ip_down') }}" method="POST" class="d-none">
                    @csrf
                </form>
                <form id="preview-ip-up-form" action="{{ route('telegram.preview_ip_up') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
