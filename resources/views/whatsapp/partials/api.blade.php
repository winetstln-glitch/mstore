<div class="tab-pane fade show active" id="api">
    @if(session('wa_gateway_status'))
        <div class="alert {{ (session('wa_gateway_status')['ok'] ?? false) && (session('wa_gateway_status')['connected'] ?? false) ? 'alert-success' : 'alert-warning' }}">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <strong>Status Gateway:</strong>
                    {{ (session('wa_gateway_status')['ok'] ?? false) && (session('wa_gateway_status')['connected'] ?? false) ? 'Terhubung' : 'Belum Terhubung' }}
                </div>
                <small class="text-muted">{{ session('wa_gateway_status')['message'] ?? '-' }}</small>
            </div>
        </div>
    @endif

    <div class="alert alert-info">
        <div class="fw-semibold mb-1">Status Konfigurasi Tersimpan</div>
        <div>API URL: <code>{{ $settings['whatsapp_api_url']->value ?: '-' }}</code></div>
        <div>API Key: <code>{{ $maskedWaApiKey ?: 'Belum diatur' }}</code></div>
    </div>

    <div class="alert {{ ($ticketGroupStatus['ready'] ?? false) ? 'alert-success' : 'alert-warning' }}">
        <div class="fw-semibold mb-1">Status Notifikasi Grup Tiket</div>
        <div>{{ $ticketGroupStatus['message'] ?? 'Status tidak tersedia.' }}</div>
        @if(!($ticketGroupStatus['ready'] ?? false))
            <div class="small text-muted mt-2">
                Lengkapi `API Key` dan `Group ID Tiket` agar notifikasi tiket ke grup WhatsApp bisa berjalan.
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('whatsapp.check-status') }}" class="mb-3">
        @csrf
        <button type="submit" class="btn btn-outline-dark btn-sm">
            <i class="fa-solid fa-signal me-1"></i> Cek Status Gateway
        </button>
        <span class="text-muted ms-2 small">Tidak menyimpan perubahan form.</span>
    </form>

    <form method="POST" action="{{ route('whatsapp.update') }}">
        @csrf
        <div class="alert alert-secondary">
            Endpoint harus base URL. Sistem otomatis append <code>/send</code> untuk Fonnte.
        </div>
        <div class="mb-3">
            <label>API URL</label>
            <input type="text" class="form-control" name="whatsapp_api_url" value="{{ $settings['whatsapp_api_url']->value }}">
        </div>
        <div class="mb-3">
            <label>API Key</label>
            <div class="input-group">
                <input type="password" class="form-control" id="whatsapp_api_key_display" value="{{ $settings['whatsapp_api_key']->value }}" placeholder="API key WhatsApp">
                <button class="btn btn-outline-secondary" type="button" id="toggle_api_key">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
            <div class="form-text">Klik mata untuk melihat/menyembunyikan API key. Isi baru jika ingin mengganti.</div>
            <input type="hidden" name="whatsapp_api_key" id="whatsapp_api_key_hidden" value="">
        </div>
        <div class="mb-3">
            <label>WABLAS Secret Key (Opsional)</label>
            <input type="password" class="form-control" name="whatsapp_secret_key" placeholder="Isi Secret Key untuk WABLAS (jika dibutuhkan)">
            <div class="form-text">Kosongkan jika tidak menggunakan WABLAS atau secret key tidak dibutuhkan.</div>
        </div>

        <div class="card bg-light border-0 mb-3">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-bold mb-0">
                    <i class="fab fa-whatsapp me-2"></i>
                    WhatsApp Group Notification Settings
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tiket Notification</label>
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="whatsapp_ticket_notification_enabled" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_ticket_notification_enabled" name="whatsapp_ticket_notification_enabled" value="1" {{ $settings['whatsapp_ticket_notification_enabled']->value == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="whatsapp_ticket_notification_enabled">Aktifkan Notifikasi Tiket</label>
                            </div>
                            <input type="text" class="form-control" name="whatsapp_ticket_group_id" value="{{ $settings['whatsapp_ticket_group_id']->value }}" placeholder="Group ID Tiket">
                            <div class="form-text">ID grup WhatsApp untuk notifikasi tiket baru & update status.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Absensi Notification</label>
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="whatsapp_attendance_notification_enabled" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_attendance_notification_enabled" name="whatsapp_attendance_notification_enabled" value="1" {{ $settings['whatsapp_attendance_notification_enabled']->value == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="whatsapp_attendance_notification_enabled">Aktifkan Notifikasi Absensi</label>
                            </div>
                            <input type="text" class="form-control" name="whatsapp_attendance_group_id" value="{{ $settings['whatsapp_attendance_group_id']->value }}" placeholder="Group ID Absensi">
                            <div class="form-text">ID grup WhatsApp untuk notifikasi absensi teknisi.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Modem UP Notification</label>
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="whatsapp_modem_up_notification_enabled" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_modem_up_notification_enabled" name="whatsapp_modem_up_notification_enabled" value="1" {{ $settings['whatsapp_modem_up_notification_enabled']->value == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="whatsapp_modem_up_notification_enabled">Aktifkan Notifikasi Modem UP</label>
                            </div>
                            <input type="text" class="form-control" name="whatsapp_modem_up_group_id" value="{{ $settings['whatsapp_modem_up_group_id']->value }}" placeholder="Group ID Modem UP">
                            <div class="form-text">ID grup WhatsApp untuk notifikasi modem UP (Recovery).</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Modem DOWN Notification</label>
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="whatsapp_modem_down_notification_enabled" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_modem_down_notification_enabled" name="whatsapp_modem_down_notification_enabled" value="1" {{ $settings['whatsapp_modem_down_notification_enabled']->value == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="whatsapp_modem_down_notification_enabled">Aktifkan Notifikasi Modem DOWN</label>
                            </div>
                            <input type="text" class="form-control" name="whatsapp_modem_down_group_id" value="{{ $settings['whatsapp_modem_down_group_id']->value }}" placeholder="Group ID Modem DOWN">
                            <div class="form-text">ID grup WhatsApp untuk notifikasi modem DOWN.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Modem RECAP Notification</label>
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="whatsapp_modem_recap_notification_enabled" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_modem_recap_notification_enabled" name="whatsapp_modem_recap_notification_enabled" value="1" {{ $settings['whatsapp_modem_recap_notification_enabled']->value == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="whatsapp_modem_recap_notification_enabled">Aktifkan Notifikasi Modem RECAP</label>
                            </div>
                            <input type="text" class="form-control" name="whatsapp_modem_recap_group_id" value="{{ $settings['whatsapp_modem_recap_group_id']->value }}" placeholder="Group ID Modem RECAP">
                            <div class="form-text">ID grup WhatsApp untuk notifikasi rekap berkala GenieACS.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">
        
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="fa-solid fa-save"></i> Simpan Konfigurasi
            </button>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="tab" data-bs-target="#test">
                <i class="fa-solid fa-paper-plane"></i> Buka Form Send Test
            </button>
        </div>
    </form>

    <div class="alert alert-light border mt-3 mb-0">
        <div class="fw-semibold mb-2">Panduan Singkat Pengaturan API WhatsApp & QRIS</div>
        <div>1. Isi <strong>API URL</strong> dan <strong>API Key Baru</strong> (WhatsApp), lalu klik <strong>Simpan Konfigurasi</strong>.</div>
        <div>2. Klik <strong>Cek Status Gateway</strong> untuk memastikan device status <strong>Terhubung</strong>.</div>
        <div>3. Jika status belum terhubung, login ke panel provider (Fonnte) dan scan QR perangkat WA.</div>
        <div>4. Untuk QRIS: Isi <strong>Duitku Merchant Code</strong> dan <strong>Duitku API Key Baru</strong>, lalu simpan.</div>
        <div>5. Salin <strong>URL Callback Duitku</strong> dan masukkan ke panel pengaturan webhook Duitku Anda.</div>
        <div>6. Setelah terhubung, buka tab <strong>Testing</strong> lalu kirim <strong>Send Test</strong> ke nomor tujuan.</div>
    </div>
</div>
