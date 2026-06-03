@extends('layouts.app')

@section('title', 'WhatsApp Admin Panel')

@section('content')
<div class="card shadow-sm">

    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa-brands fa-whatsapp me-2"></i>
            WhatsApp System Configuration (Admin)
        </h5>
    </div>

    <div class="card-body">

        {{-- NAV TABS --}}
        <ul class="nav nav-tabs mb-4" id="waTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#api">
                    API & Connection
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#template">
                    Template Engine
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#autoreply">
                    Auto Reply & Webhook
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#isp">
                    ISP Automation
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#test">
                    Testing
                </button>
            </li>
            <li class="nav-item ms-auto">
                <a href="{{ route('whatsapp.logs') }}" class="nav-link text-secondary">
                    <i class="fa-solid fa-list"></i> Logs
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('whatsapp.builder.index') }}" class="nav-link text-success">
                    <i class="fa-solid fa-robot"></i> Bot Builder
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <div class="tab-pane fade show active" id="api">
                @php
                    $savedApiUrl = \App\Models\Setting::getValue('whatsapp_api_url', env('WHATSAPP_API_URL'));
                    $savedApiKey = \App\Models\Setting::getValue('whatsapp_api_key', env('WHATSAPP_API_KEY'));
                    $maskedApiKey = is_string($savedApiKey) && $savedApiKey !== ''
                        ? str_repeat('*', max(strlen($savedApiKey) - 4, 0)).substr($savedApiKey, -4)
                        : null;
                @endphp
                @php($gatewayStatus = session('wa_gateway_status'))
                @if(is_array($gatewayStatus))
                    <div class="alert {{ ($gatewayStatus['ok'] ?? false) && ($gatewayStatus['connected'] ?? false) ? 'alert-success' : 'alert-warning' }}">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <strong>Status Gateway:</strong>
                                {{ ($gatewayStatus['ok'] ?? false) && ($gatewayStatus['connected'] ?? false) ? 'Terhubung' : 'Belum Terhubung' }}
                            </div>
                            <small class="text-muted">{{ $gatewayStatus['message'] ?? '-' }}</small>
                        </div>
                    </div>
                @endif

                <div class="alert alert-info">
                    <div class="fw-semibold mb-1">Status Konfigurasi Tersimpan</div>
                    <div>API URL: <code>{{ $savedApiUrl ?: '-' }}</code></div>
                    <div>API Key: <code>{{ $maskedApiKey ?: 'Belum diatur' }}</code></div>
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
                        <input type="text" class="form-control" name="whatsapp_api_url" value="{{ $savedApiUrl }}">
                    </div>
                    <div class="mb-3">
                        <label>API Key Baru</label>
                        <input type="password" class="form-control" name="whatsapp_api_key" placeholder="Isi hanya jika ingin mengganti API key">
                        <div class="form-text">Kosongkan jika tidak ingin mengubah API key yang sudah tersimpan.</div>
                    </div>

                    <div class="card bg-light border-0 mb-3">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="fw-bold mb-0">Group Notification Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Tiket Notification</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_ticket_notification_enabled" name="whatsapp_ticket_notification_enabled" value="1" {{ \App\Models\Setting::getValue('whatsapp_ticket_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="whatsapp_ticket_notification_enabled">Aktifkan Notifikasi Tiket</label>
                                        </div>
                                        <input type="text" class="form-control" name="whatsapp_ticket_group_id" value="{{ \App\Models\Setting::getValue('whatsapp_ticket_group_id') }}" placeholder="Group ID Tiket">
                                        <div class="form-text">ID grup WhatsApp untuk notifikasi tiket baru & update status.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Absensi Notification</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_attendance_notification_enabled" name="whatsapp_attendance_notification_enabled" value="1" {{ \App\Models\Setting::getValue('whatsapp_attendance_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="whatsapp_attendance_notification_enabled">Aktifkan Notifikasi Absensi</label>
                                        </div>
                                        <input type="text" class="form-control" name="whatsapp_attendance_group_id" value="{{ \App\Models\Setting::getValue('whatsapp_attendance_group_id') }}" placeholder="Group ID Absensi">
                                        <div class="form-text">ID grup WhatsApp untuk notifikasi absensi teknisi.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Modem UP Notification</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_modem_up_notification_enabled" name="whatsapp_modem_up_notification_enabled" value="1" {{ \App\Models\Setting::getValue('whatsapp_modem_up_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="whatsapp_modem_up_notification_enabled">Aktifkan Notifikasi Modem UP</label>
                                        </div>
                                        <input type="text" class="form-control" name="whatsapp_modem_up_group_id" value="{{ \App\Models\Setting::getValue('whatsapp_modem_up_group_id') }}" placeholder="Group ID Modem UP">
                                        <div class="form-text">ID grup WhatsApp untuk notifikasi modem UP (Recovery).</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Modem DOWN Notification</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_modem_down_notification_enabled" name="whatsapp_modem_down_notification_enabled" value="1" {{ \App\Models\Setting::getValue('whatsapp_modem_down_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="whatsapp_modem_down_notification_enabled">Aktifkan Notifikasi Modem DOWN</label>
                                        </div>
                                        <input type="text" class="form-control" name="whatsapp_modem_down_group_id" value="{{ \App\Models\Setting::getValue('whatsapp_modem_down_group_id') }}" placeholder="Group ID Modem DOWN">
                                        <div class="form-text">ID grup WhatsApp untuk notifikasi modem DOWN.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Modem RECAP Notification</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_modem_recap_notification_enabled" name="whatsapp_modem_recap_notification_enabled" value="1" {{ \App\Models\Setting::getValue('whatsapp_modem_recap_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="whatsapp_modem_recap_notification_enabled">Aktifkan Notifikasi Modem RECAP</label>
                                        </div>
                                        <input type="text" class="form-control" name="whatsapp_modem_recap_group_id" value="{{ \App\Models\Setting::getValue('whatsapp_modem_recap_group_id') }}" placeholder="Group ID Modem RECAP">
                                        <div class="form-text">ID grup WhatsApp untuk notifikasi rekap berkala GenieACS.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-save"></i> Simpan Konfigurasi
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="tab" data-bs-target="#test">
                            <i class="fa-solid fa-paper-plane"></i> Buka Form Send Test
                        </button>
                    </div>
                </form>

                <hr class="my-4">
                    
                    {{-- Duitku QRIS Settings --}}
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-qrcode me-2"></i>
                                QRIS Duitku (Pembayaran Voucher Hotspot)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <div class="fw-semibold mb-1">Status Konfigurasi QRIS Duitku</div>
                                <div>Merchant Code: <code>{{ \App\Models\Setting::getValue('duitku_merchant_code', '') ?: '-' }}</code></div>
                                <div>API Key: <code>
                                    @php
                                        $k = \App\Models\Setting::getValue('duitku_api_key', '');
                                        echo is_string($k) && $k !== '' ? str_repeat('*', max(strlen($k) - 4, 0)).substr($k, -4) : 'Belum diatur';
                                    @endphp
                                </code></div>
                                <div>Mode: <code>{{ \App\Models\Setting::getValue('duitku_sandbox', '1') == '1' ? 'Sandbox (Testing)' : 'Production' }}</code></div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label>Duitku Merchant Code</label>
                                        <input type="text" class="form-control" name="duitku_merchant_code" value="{{ \App\Models\Setting::getValue('duitku_merchant_code', '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label>Duitku API Key Baru</label>
                                        <input type="password" class="form-control" name="duitku_api_key" placeholder="Isi hanya jika ingin mengganti API key">
                                        <div class="form-text">Kosongkan jika tidak ingin mengubah API key yang sudah tersimpan.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="duitku_sandbox" name="duitku_sandbox" value="1" {{ \App\Models\Setting::getValue('duitku_sandbox', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="duitku_sandbox">Gunakan Duitku Sandbox (Untuk Testing)</label>
                                </div>
                            </div>
                            
                            <div class="alert alert-secondary">
                                <div class="fw-semibold mb-2">URL Callback untuk Duitku</div>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" value="{{ route('voucher.payment.callback') }}" id="duitkuCallbackUrl" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyDuitkuCallbackUrl()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="form-text">Salin URL ini dan masukkan ke panel pengaturan webhook Duitku Anda.</div>
                            </div>
                        </div>
                    </div>

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


            <div class="tab-pane fade" id="template">
                <form method="POST" action="{{ route('whatsapp.update') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <label>Template Notifikasi Tiket Teknisi</label>
                            <textarea class="form-control" rows="12" name="whatsapp_ticket_template" id="ticketTpl">{{ \App\Models\Setting::getValue('whatsapp_ticket_template', \App\Notifications\TicketAssignedNotification::defaultTemplate()) }}</textarea>
                            <div class="form-text">
                                Placeholder: <code>{technician_name}</code>, <code>{ticket_number}</code>, <code>{subject}</code>, <code>{customer_name}</code>, <code>{location}</code>, <code>{priority}</code>, <code>{description}</code>, <code>{url}</code>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="useTicketDefaultBtn">
                                Gunakan Template Default
                            </button>
                        </div>
                        <div class="col-md-6">
                            <label>Live Preview Tiket</label>
                            <div class="border p-3" style="min-height:300px; white-space:pre-wrap;" id="ticketPreview"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <label>ATK Receipt Template</label>
                            <textarea class="form-control" rows="12" name="whatsapp_atk_receipt_template" id="atkTpl">{{ \App\Models\Setting::getValue('whatsapp_atk_receipt_template', '') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label>Live Preview</label>
                            <div class="border p-3 " style="min-height:300px;" id="atkPreview"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <label>Wash Receipt Template</label>
                            <textarea class="form-control" rows="12" name="whatsapp_wash_receipt_template" id="washTpl">{{ \App\Models\Setting::getValue('whatsapp_wash_receipt_template', '') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label>Live Preview</label>
                            <div class="border p-3 " style="min-height:300px;" id="washPreview"></div>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            Update Template
                        </button>
                    </div>
                </form>

            </div>


            <div class="tab-pane fade" id="autoreply">
                <form method="POST" action="{{ route('whatsapp.update') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <div class="fw-semibold mb-2">
                                    <i class="fas fa-plug me-2"></i>
                                    Webhook Configuration
                                </div>
                                <div class="mb-2">
                                    <strong>URL Webhook:</strong>
                                    <div class="input-group mt-1">
                                        <input type="text" class="form-control font-monospace" 
                                               value="{{ route('api.whatsapp.webhook') }}" 
                                               id="webhookUrl" readonly>
                                        <button class="btn btn-outline-primary" type="button" onclick="copyWebhookUrl()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <strong>Verify Token:</strong>
                                    <span class="badge bg-info">{{ config('services.whatsapp.verify_token', 'your-verify-token-change-me') }}</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Enable Auto Reply</label>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_autoreply_enabled" name="whatsapp_autoreply_enabled" value="1" {{ \App\Models\Setting::getValue('whatsapp_autoreply_enabled', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="whatsapp_autoreply_enabled">Aktifkan Auto Reply</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-light border">
                                <div class="fw-semibold mb-2">
                                    <i class="fas fa-robot me-2"></i>
                                    Perintah Auto Reply (dibuat di Bot Builder)
                                </div>
                                <div class="text-muted">Semua menu yang Anda buat di Bot Builder akan otomatis aktif sebagai auto reply!</div>
                                <hr>
                                <div class="fw-semibold mb-2">Contoh Perintah:</div>
                                <div><code>menu/help</code> - Tampilkan menu</div>
                                <div><code>harga/jasa</code> - Info harga jasa</div>
                                <div><code>kontak/cs</code> - Hubungi CS</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i> Simpan Konfigurasi Auto Reply
                        </button>
                    </div>
                </form>
            </div>


            <div class="tab-pane fade" id="isp">
                <form method="POST" action="{{ route('whatsapp.update') }}">
                    @csrf
                    <label>Monthly Bill Template</label>
                    <textarea class="form-control mb-3" rows="6" name="whatsapp_isp_bill_template">{{ \App\Models\Setting::getValue('whatsapp_isp_bill_template', '') }}</textarea>
                    <label>Reminder Template</label>
                    <textarea class="form-control mb-3" rows="6" name="whatsapp_isp_reminder_template">{{ \App\Models\Setting::getValue('whatsapp_isp_reminder_template', '') }}</textarea>
                    <button type="submit" class="btn btn-primary">
                        Save ISP Templates
                    </button>
                </form>
            </div>

            <div class="tab-pane fade" id="test">
                <form method="POST" action="{{ route('whatsapp.test') }}">
                    @csrf
                    <label>Phone Number</label>
                    <input type="text" class="form-control mb-3" name="test_phone" placeholder="628xxxxxxxxxx">
                    <label>Select Template</label>
                    <select class="form-select mb-3" name="test_mode">
                        <option value="plain">Plain</option>
                        <option value="atk_receipt">ATK Receipt</option>
                        <option value="wash_receipt">Wash Receipt</option>
                        <option value="isp_bill">ISP Bill</option>
                    </select>
                    <button type="submit" class="btn btn-dark">
                        <i class="fa-solid fa-paper-plane"></i>
                        Send Test
                    </button>
                </form>
            </div>

@push('scripts')
<script>
    function renderLoop(tpl, items) {
        const re = /\{\{\#each\s+items\}\}([\s\S]*?)\{\{\/each\}\}/;
        const m = tpl.match(re);
        if (!m) return tpl;
        let out = '';
        items.forEach(it => {
            let seg = m[1];
            Object.keys(it).forEach(k => {
                seg = seg.replaceAll('{{' + k + '}}', String(it[k]));
            });
            out += seg;
        });
        return tpl.replace(m[0], out);
    }
    function renderSimple(tpl, vars) {
        let t = tpl;
        if (Array.isArray(vars.items)) {
            t = renderLoop(tpl, vars.items);
        }
        Object.keys(vars).forEach(k => {
            if (k !== 'items') {
                t = t.replaceAll('{{' + k + '}}', String(vars[k]));
            }
        });
        return t;
    }
    function renderTicketTemplate(tpl, vars) {
        let t = tpl;
        Object.keys(vars).forEach(k => {
            t = t.replaceAll('{' + k + '}', String(vars[k]));
        });
        return t;
    }
    const ticketVars = {
        technician_name: 'Teknisi Demo',
        ticket_number: 'TCK-2026-0001',
        subject: 'Internet putus sejak pagi',
        customer_name: 'Budi Santoso',
        location: '-6.200000, 106.816666',
        priority: 'High',
        description: 'Lampu LOS merah, mohon cek jalur ODP dan ONU.',
        url: @json(url('/tickets/1'))
    };
    const defaultTicketTemplate = @json(\App\Notifications\TicketAssignedNotification::defaultTemplate());
    const atkVars = {
        nama_toko: @json(config('app.name')),
        alamat_toko: @json(\App\Models\Setting::getValue('store_address','Jl. Contoh No. 1')),
        no_toko: @json(\App\Models\Setting::getValue('store_phone','081234567890')),
        invoice: 'ATK-TEST-001',
        tanggal: @json(now()->format('d-m-Y H:i')),
        nama_customer: 'Pelanggan Demo',
        subtotal: '15.000',
        diskon: '0',
        pajak: '0',
        grand_total: '15.000',
        metode_bayar: 'CASH',
        status: 'LUNAS',
        items: [
            { nama_produk: 'Pulpen', qty: '1', harga: '5.000', total: '5.000' },
            { nama_produk: 'Buku Tulis', qty: '1', harga: '10.000', total: '10.000' }
        ]
    };
    const washVars = {
        nama_usaha: @json(config('app.name')),
        alamat: @json(\App\Models\Setting::getValue('store_address','Jl. Contoh No. 1')),
        no_hp: @json(\App\Models\Setting::getValue('store_phone','081234567890')),
        invoice: 'WASH-TEST-001',
        tanggal: @json(now()->format('d-m-Y H:i')),
        nama_customer: 'Pelanggan Demo',
        jenis_kendaraan: 'Toyota',
        plat_nomor: 'B 1234 CD',
        subtotal: '25.000',
        diskon: '0',
        total: '25.000',
        metode_bayar: 'CASH',
        status: 'LUNAS',
        items: [
            { nama_layanan: 'Cuci Eksterior', harga: '15.000' },
            { nama_layanan: 'Cuci Interior', harga: '10.000' }
        ]
    };
    function copyWebhookUrl() {
        const webhookUrl = document.getElementById('webhookUrl');
        if (webhookUrl) {
            webhookUrl.select();
            webhookUrl.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(webhookUrl.value).then(() => {
                alert('Webhook URL berhasil disalin!');
            });
        }
    }
    function copyDuitkuCallbackUrl() {
        const callbackUrl = document.getElementById('duitkuCallbackUrl');
        if (callbackUrl) {
            callbackUrl.select();
            callbackUrl.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(callbackUrl.value).then(() => {
                alert('URL Callback Duitku berhasil disalin!');
            });
        }
    }
    function updatePreview(idTpl, idPrev, vars, renderer = renderSimple) {
        const elTpl = document.getElementById(idTpl);
        const elPrev = document.getElementById(idPrev);
        if (!elTpl || !elPrev) return;
        const result = renderer(elTpl.value, vars);
        elPrev.textContent = result;
    }
    document.addEventListener('DOMContentLoaded', function () {
        updatePreview('ticketTpl', 'ticketPreview', ticketVars, renderTicketTemplate);
        updatePreview('atkTpl', 'atkPreview', atkVars);
        updatePreview('washTpl', 'washPreview', washVars);
        const ticketEl = document.getElementById('ticketTpl');
        const atkEl = document.getElementById('atkTpl');
        const washEl = document.getElementById('washTpl');
        const defaultBtn = document.getElementById('useTicketDefaultBtn');
        if (ticketEl) ticketEl.addEventListener('input', () => updatePreview('ticketTpl', 'ticketPreview', ticketVars, renderTicketTemplate));
        if (defaultBtn && ticketEl) {
            defaultBtn.addEventListener('click', () => {
                ticketEl.value = defaultTicketTemplate;
                updatePreview('ticketTpl', 'ticketPreview', ticketVars, renderTicketTemplate);
            });
        }
        if (atkEl) atkEl.addEventListener('input', () => updatePreview('atkTpl', 'atkPreview', atkVars));
        if (washEl) washEl.addEventListener('input', () => updatePreview('washTpl', 'washPreview', washVars));
    });
</script>
@endpush
        </div>
    </div>
</div>
@endsection
