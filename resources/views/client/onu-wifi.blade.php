@extends('layouts.app')

@section('title', 'Ganti Password WiFi ONU')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Ganti Password WiFi ONU</h1>
            <p class="text-muted mb-0">Portal pelanggan untuk mengganti password WiFi perangkat ONU</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (!empty($ownershipWarning))
        <div class="alert alert-{{ $ownershipBlocked ? 'danger' : 'warning' }} alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-{{ $ownershipBlocked ? 'shield-halved' : 'circle-info' }} me-2"></i>
            {!! $ownershipWarning !!}
            @if (!empty($ownershipCheck))
                <div class="mt-2 small opacity-90">
                    <strong>Poin verifikasi perangkat ({{ $ownershipCheck['level'] ?? 0 }}/4):</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        <li class="{{ ($ownershipCheck['checks']['serial_exists'] ?? false) === true ? 'text-success' : 'text-danger' }}">
                            ✅ Serial number ONU ditemukan di database GenieACS
                        </li>
                        <li class="{{ ($ownershipCheck['checks']['pppoe_username_match'] ?? null) === true ? 'text-success' : (($ownershipCheck['checks']['pppoe_username_match'] === false) ? 'text-danger' : 'text-muted') }}">
                            @if(($ownershipCheck['checks']['pppoe_username_match'] ?? null) === true) ✅ @elseif(($ownershipCheck['checks']['pppoe_username_match'] ?? null) === false) ❌ @else ⚪ @endif
                            Username PPPoE perangkat cocok dengan data pelanggan
                        </li>
                        <li class="{{ ($ownershipCheck['checks']['wan_mac_match'] ?? null) === true ? 'text-success' : (($ownershipCheck['checks']['wan_mac_match'] === false) ? 'text-danger' : 'text-muted') }}">
                            @if(($ownershipCheck['checks']['wan_mac_match'] ?? null) === true) ✅ @elseif(($ownershipCheck['checks']['wan_mac_match'] ?? null) === false) ❌ @else ⚪ @endif
                            MAC Address WAN perangkat cocok dengan data pelanggan
                        </li>
                        <li class="{{ ($ownershipCheck['checks']['tag_customer_id_match'] ?? null) === true ? 'text-success' : (($ownershipCheck['checks']['tag_customer_id_match'] === false) ? 'text-danger' : 'text-muted') }}">
                            @if(($ownershipCheck['checks']['tag_customer_id_match'] ?? null) === true) ✅ @elseif(($ownershipCheck['checks']['tag_customer_id_match'] ?? null) === false) ❌ @else ⚪ @endif
                            Tag ID pelanggan perangkat cocok dengan database
                        </li>
                    </ul>
                </div>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $formDisabled = !empty($ownershipBlocked);
    @endphp

    @if (!$onuSerial)
        <div class="card shadow-sm border-warning">
            <div class="card-body text-center py-5">
                <i class="fa-solid fa-triangle-exclamation text-warning fa-4x mb-3"></i>
                <h4 class="text-warning">ONU Belum Terdaftar</h4>
                <p class="text-muted mb-0">Pelanggan ini belum memiliki perangkat ONU yang terdaftar di sistem.<br>Silakan hubungi admin ISP untuk pendataan perangkat.</p>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-router me-2 text-primary"></i>Status ONU</h6>
                        @if ($isOnline)
                            <span class="badge bg-success"><i class="fa-solid fa-circle me-1" style="font-size: 8px"></i>Online</span>
                        @else
                            <span class="badge bg-secondary"><i class="fa-solid fa-circle me-1" style="font-size: 8px"></i>Offline</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width: 40%">Serial Number</td>
                                    <td class="fw-mono fw-bold">{{ $onuSerial }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pelanggan</td>
                                    <td>{{ $customer->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Paket</td>
                                    <td>{{ $customer->package?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Last Inform</td>
                                    <td>
                                        @if ($lastInform)
                                            <small>{{ $lastInform->diffForHumans() }}</small>
                                            <div class="text-muted small">{{ $lastInform->format('d M Y H:i') }}</div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @if ($isOnline && $wlan2G)
                                    <tr>
                                        <td class="text-muted">Perangkat 2.4G</td>
                                        <td>{{ $wlan2G['connected_devices'] ?? 0 }} device</td>
                                    </tr>
                                @endif
                                @if ($isOnline && $has5Ghz && $wlan5G)
                                    <tr>
                                        <td class="text-muted">Perangkat 5G</td>
                                        <td>{{ $wlan5G['connected_devices'] ?? 0 }} device</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        @if (!$isOnline)
                            <div class="alert alert-warning mt-3 mb-0 small">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                ONU sedang offline. Perubahan akan dikirim ke antrian dan berlaku saat ONU online kembali (maks 1 jam).
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <form id="wifiForm" method="POST" action="{{ route('client.onu-wifi.update') }}" onsubmit="return confirmSubmit(event)">
                    @csrf
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <ul class="nav nav-tabs card-header-tabs mb-0 pb-0 border-bottom-0" id="bandTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tab-2g" data-bs-toggle="tab" data-bs-target="#panel-2g" type="button" role="tab">
                                        <i class="fa-solid fa-signal me-1"></i>2.4 GHz
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ !$has5Ghz ? 'disabled' : '' }}" id="tab-5g" data-bs-toggle="tab" data-bs-target="#panel-5g" type="button" role="tab" {{ !$has5Ghz ? 'aria-disabled="true"' : '' }}>
                                        <i class="fa-solid fa-wifi me-1"></i>5 GHz
                                        @if (!$has5Ghz)
                                            <span class="badge bg-secondary ms-1" style="font-size: 10px">N/A</span>
                                        @endif
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="bandTabsContent">
                                <div class="tab-pane fade show active" id="panel-2g" role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="ssid_2g" class="form-label">Nama WiFi (SSID) 2.4 GHz</label>
                                            <input id="ssid_2g" name="ssid_2g" type="text" maxlength="32"
                                                class="form-control @error('ssid_2g') is-invalid @enderror"
                                                value="{{ old('ssid_2g', $wlan2G['ssid'] ?? '') }}"
                                                placeholder="Contoh: RumahAnda_2G"
                                                {{ $formDisabled ? 'disabled' : '' }}>
                                            <small class="text-muted">Kosongkan jika tidak ingin mengubah SSID</small>
                                            @error('ssid_2g')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label for="password_2g" class="form-label">Password WiFi 2.4 GHz <small class="text-muted">(min 8 karakter)</small></label>
                                            <div class="input-group">
                                                <input id="password_2g" name="password_2g" type="password" maxlength="63"
                                                    class="form-control @error('password_2g') is-invalid @enderror"
                                                    value="{{ old('password_2g') }}"
                                                    placeholder="Min 8 karakter, kosongkan = tidak ubah"
                                                    autocomplete="new-password"
                                                    {{ $formDisabled ? 'disabled' : '' }}>
                                                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password_2g" aria-label="Tampilkan">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            @error('password_2g')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            @if (!empty($wlan2G['password']))
                                                <small class="text-success d-block mt-1">
                                                    <i class="fa-solid fa-circle-check me-1"></i>Password saat ini tersimpan di ONU
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="panel-5g" role="tabpanel">
                                    @if ($has5Ghz)
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="same_password" name="same_password" value="1" {{ old('same_password') ? 'checked' : '' }} {{ $formDisabled ? 'disabled' : '' }}>
                                                <label class="form-check-label fw-bold" for="same_password">
                                                    Gunakan SSID & Password SAMA dengan 2.4 GHz
                                                </label>
                                            </div>
                                            <small class="text-muted">Jika dicentang, SSID 5GHz akan otomatis ditambah suffix "_5G"</small>
                                        </div>
                                        <div class="row g-3" id="fields-5g">
                                            <div class="col-12">
                                                <label for="ssid_5g" class="form-label">Nama WiFi (SSID) 5 GHz</label>
                                                <input id="ssid_5g" name="ssid_5g" type="text" maxlength="32"
                                                    class="form-control @error('ssid_5g') is-invalid @enderror"
                                                    value="{{ old('ssid_5g', $wlan5G['ssid'] ?? '') }}"
                                                    placeholder="Contoh: RumahAnda_5G"
                                                    {{ $formDisabled ? 'disabled' : '' }}>
                                                <small class="text-muted">Kosongkan jika tidak ingin mengubah SSID</small>
                                                @error('ssid_5g')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-12">
                                                <label for="password_5g" class="form-label">Password WiFi 5 GHz <small class="text-muted">(min 8 karakter)</small></label>
                                                <div class="input-group">
                                                    <input id="password_5g" name="password_5g" type="password" maxlength="63"
                                                        class="form-control @error('password_5g') is-invalid @enderror"
                                                        value="{{ old('password_5g') }}"
                                                        placeholder="Min 8 karakter"
                                                        autocomplete="new-password"
                                                        {{ $formDisabled ? 'disabled' : '' }}>
                                                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="password_5g" aria-label="Tampilkan">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                </div>
                                                @error('password_5g')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-wifi-slash fa-3x mb-3 d-block"></i>
                                            <p class="mb-0">Perangkat ONU Anda tidak mendukung jaringan 5 GHz.<br>Hanya jaringan 2.4 GHz yang tersedia.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0 fw-bold"><i class="fa-solid fa-shield-halved me-2"></i>Verifikasi Keamanan (2 Langkah)</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info small">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                <strong>WAJIB demi keamanan Anda, kenapa harus 2 langkah?</strong><br>
                                <ol class="mb-0 mt-1 ps-3">
                                    <li><strong>Password Akun MSTORE</strong> → membuktikan itu <em>Anda</em> yang login (bukan orang lain pinjam HP Anda yang lupa log out).</li>
                                    <li><strong>Kode OTP via WhatsApp</strong> → bukti tambahan Anda memegang nomor HP yang terdaftar sebagai Pemilik Akun Pelanggan.</li>
                                </ol>
                                <small class="text-muted mt-1 d-block">⚠️ Mengubah password WiFi = tindakan KRITIS. TANPA OTP, jika HP Anda tertinggal di meja, siapa pun bisa ubah WiFi rumah Anda & boot semua perangkat keluarga!</small>
                            </div>

                            @if(!empty($registeredWaDisplay))
                                <div class="alert alert-success small mb-3">
                                    <i class="fa-brands fa-whatsapp me-1 text-success"></i>
                                    <strong>Nomor WhatsApp Terdaftar:</strong>
                                    <code class="fw-bold text-nowrap">{{ $registeredWaDisplay }}</code>
                                    <span class="text-muted">(mask: {{ $registeredWaMasked ?? '-' }})</span>
                                    <br>
                                    <small class="text-muted">OTP akan dikirim ke nomor di atas. Jika <strong>nomor ini SALAH / tidak aktif</strong>, hubungi admin ISP kami sebelum lanjut.</small>
                                </div>
                            @else
                                <div class="alert alert-danger small mb-3">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                    <strong>⚠️ Nomor WhatsApp TIDAK DITEMUKAN di akun Anda.</strong><br>
                                    Fitur kirim OTP tidak bisa berjalan. Silakan <strong>hubungi Admin / CS ISP</strong> kami untuk mendaftarkan nomor HP aktif Anda ke data pelanggan.
                                </div>
                            @endif

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="account_password" class="form-label fw-bold">1. Password Akun MSTORE</label>
                                    <div class="input-group">
                                        <input id="account_password" name="account_password" type="password" required
                                            class="form-control @error('account_password') is-invalid @enderror"
                                            placeholder="Masukkan password login MSTORE"
                                            autocomplete="current-password"
                                            {{ $formDisabled || empty($registeredWaDisplay) ? 'disabled' : '' }}>
                                        <button class="btn btn-outline-secondary" type="button" data-toggle-password="account_password">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                    @error('account_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">&nbsp;</label>
                                    <button type="button" id="sendOtpBtn" class="btn btn-outline-primary w-100" onclick="sendOtp()" {{ $formDisabled || empty($registeredWaDisplay) ? 'disabled' : '' }}>
                                        <i class="fa-brands fa-whatsapp me-1"></i>
                                        <span id="sendOtpText">
                                            @if(!empty($registeredWaDisplay))
                                                2. Kirim OTP ke WA {{ $registeredWaDisplay }}
                                            @else
                                                ⚠️ Nomor WA Tidak Ada
                                            @endif
                                        </span>
                                    </button>
                                    <div id="otpStatus" class="small mt-1"></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="otp" class="form-label fw-bold">3. Kode OTP <small class="text-muted">(6 digit dari WhatsApp)</small></label>
                                    <input id="otp" name="otp" type="text" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                        class="form-control form-control-lg @error('otp') is-invalid @enderror text-center fw-bold tracking-widest"
                                        placeholder="000000"
                                        value="{{ old('otp') }}"
                                        autocomplete="one-time-code"
                                        {{ $formDisabled || empty($registeredWaDisplay) ? 'disabled' : '' }}>
                                    @error('otp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm" {{ $formDisabled || empty($registeredWaDisplay) ? 'disabled' : '' }}>
                            <i class="fa-solid fa-floppy-disk me-2"></i>
                            {{ $formDisabled ? 'Perubahan WiFi DIBLOKIR (Verifikasi Gagal)' : (empty($registeredWaDisplay) ? 'Tidak ada Nomor WA terdaftar' : 'Simpan Perubahan WiFi') }}
                        </button>
                    </div>
                </form>

                <div class="alert alert-warning mt-4 shadow-sm">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Peringatan Penting!</h6>
                    <ul class="mb-0 small ps-3">
                        <li>Semua perangkat yang <strong>TERHUBUNG ke WiFi akan TERPUTUS</strong> setelah perubahan disimpan.</li>
                        <li>Anda harus <strong>connect ulang</strong> HP, laptop, TV, smartwatch, CCTV, dll menggunakan password baru.</li>
                        <li>Simpan password baru di tempat yang aman. Kami juga mengirimkan salinan ke WhatsApp Anda.</li>
                        <li>Perubahan hanya bisa dilakukan <strong>MAKS 3 KALI per JAM</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-toggle-password]').forEach((toggleButton) => {
        toggleButton.addEventListener('click', function () {
            const inputId = this.getAttribute('data-toggle-password');
            const input = document.getElementById(inputId);
            if (!input) return;
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon?.classList.remove('fa-eye');
                icon?.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon?.classList.remove('fa-eye-slash');
                icon?.classList.add('fa-eye');
            }
        });
    });

    const samePass = document.getElementById('same_password');
    const fields5g = document.getElementById('fields-5g');
    if (samePass && fields5g) {
        function toggleSamePass() {
            const checked = samePass.checked;
            fields5g.style.opacity = checked ? '0.5' : '1';
            fields5g.style.pointerEvents = checked ? 'none' : 'auto';
            const inputs = fields5g.querySelectorAll('input');
            inputs.forEach(i => { if (checked) i.value = ''; });
        }
        samePass.addEventListener('change', toggleSamePass);
        toggleSamePass();
    }

    const otpInput = document.getElementById('otp');
    if (otpInput) {
        otpInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    }

    let otpCooldown = 0;
    function startCooldown(seconds = 60) {
        otpCooldown = seconds;
        const btn = document.getElementById('sendOtpBtn');
        const txt = document.getElementById('sendOtpText');
        const status = document.getElementById('otpStatus');
        btn.disabled = true;
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-secondary');

        const interval = setInterval(() => {
            otpCooldown--;
            txt.textContent = `Tunggu ${otpCooldown} detik...`;
            if (otpCooldown <= 0) {
                clearInterval(interval);
                btn.disabled = false;
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-outline-primary');
                txt.textContent = 'Kirim Ulang OTP';
                status.textContent = '';
            }
        }, 1000);
    }

    function sendOtp() {
        const accPass = document.getElementById('account_password').value;
        const statusEl = document.getElementById('otpStatus');
        statusEl.className = 'small mt-1 text-primary';
        statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Mengirim OTP...';

        const formData = new FormData();
        formData.append('account_password', accPass);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route('client.onu-wifi.send_otp') }}', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
        .then(async res => {
            const data = await res.json();
            if (res.ok && data.success) {
                statusEl.className = 'small mt-1 text-success';
                statusEl.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>' + (data.message || 'OTP dikirim. Cek WhatsApp Anda!');
                startCooldown(60);
                setTimeout(() => document.getElementById('otp').focus(), 300);
            } else {
                statusEl.className = 'small mt-1 text-danger';
                statusEl.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>' + (data.message || 'Gagal kirim OTP. Cek password akun Anda.');
                if (res.status === 429) {
                    startCooldown(60);
                }
            }
        })
        .catch(err => {
            statusEl.className = 'small mt-1 text-danger';
            statusEl.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Koneksi error. Silakan coba lagi.';
        });
    }

    function confirmSubmit(e) {
        const has2gPass = !!document.getElementById('password_2g')?.value;
        const has5gPass = !!document.getElementById('password_5g')?.value;
        const has2gSsid = !!document.getElementById('ssid_2g')?.value?.trim();
        const has5gSsid = !!document.getElementById('ssid_5g')?.value?.trim();
        if (!has2gPass && !has5gPass && !has2gSsid && !has5gSsid) {
            alert('Setidaknya isi salah satu: SSID atau Password untuk 2.4GHz atau 5GHz.');
            e.preventDefault();
            return false;
        }
        if (has2gPass || has5gPass) {
            return confirm('⚠️ SEMUA PERANGKAT yang terhubung ke WiFi AKAN TERPUTUS!\n\nAnda harus connect ulang HP, laptop, CCTV, dll dengan password baru.\n\nLANJUTKAN?');
        }
        return confirm('Simpan perubahan pengaturan WiFi?');
    }
</script>
@endpush
