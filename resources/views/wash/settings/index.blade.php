@extends('layouts.app')

@section('title', __('Pengaturan Wash'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold">{{ __('Pengaturan Wash') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.wash.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-store me-1"></i> Identitas Toko Wash
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="wash_store_name" class="form-label fw-medium">Nama Toko Wash</label>
                                <input type="text" class="form-control" id="wash_store_name" name="wash_store_name" value="{{ \App\Models\Setting::getValue('wash_store_name', \App\Models\Setting::getValue('store_name', config('app.name', 'MStore'))) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="wash_store_phone" class="form-label fw-medium">Telepon Toko Wash</label>
                                <input type="text" class="form-control" id="wash_store_phone" name="wash_store_phone" value="{{ \App\Models\Setting::getValue('wash_store_phone', \App\Models\Setting::getValue('store_phone', '081234567890')) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="wash_store_logo_file" class="form-label fw-medium">Upload Logo Toko Wash</label>
                                <input type="file" class="form-control" id="wash_store_logo_file" name="wash_store_logo_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                @if(\App\Models\Setting::getValue('wash_store_logo'))
                                    <img src="{{ str_starts_with(\App\Models\Setting::getValue('wash_store_logo'), 'http') ? \App\Models\Setting::getValue('wash_store_logo') : asset(\App\Models\Setting::getValue('wash_store_logo')) }}" alt="Logo Toko Wash" class="img-thumbnail mt-2" style="max-height: 56px;">
                                    <div class="form-check mt-2">
                                        <input type="hidden" name="clear_wash_store_logo" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="clear_wash_store_logo" name="clear_wash_store_logo">
                                        <label class="form-check-label text-danger" for="clear_wash_store_logo">Hapus logo toko Wash</label>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label for="wash_store_address" class="form-label fw-medium">Alamat Toko Wash</label>
                                <textarea class="form-control" id="wash_store_address" name="wash_store_address" rows="3">{{ \App\Models\Setting::getValue('wash_store_address', \App\Models\Setting::getValue('store_address', 'Jl. Contoh No. 1')) }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-receipt me-1"></i> Template Nota Wash
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="wash_receipt_title" class="form-label fw-medium">Judul Nota</label>
                                <input type="text" class="form-control" id="wash_receipt_title" name="wash_receipt_title" value="{{ \App\Models\Setting::getValue('wash_receipt_title', 'NOTA PEMBAYARAN') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="wash_receipt_footer_title" class="form-label fw-medium">Judul Footer Nota</label>
                                <input type="text" class="form-control" id="wash_receipt_footer_title" name="wash_receipt_footer_title" value="{{ \App\Models\Setting::getValue('wash_receipt_footer_title', '*** TERIMA KASIH ***') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="wash_receipt_footer_message" class="form-label fw-medium">Pesan Footer Nota</label>
                                <textarea class="form-control" id="wash_receipt_footer_message" name="wash_receipt_footer_message" rows="3">{{ \App\Models\Setting::getValue('wash_receipt_footer_message', 'Kepuasan Anda Kebanggaan Kami.') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="wash_receipt_footer_note" class="form-label fw-medium">Catatan Tambahan Footer</label>
                                <textarea class="form-control" id="wash_receipt_footer_note" name="wash_receipt_footer_note" rows="3">{{ \App\Models\Setting::getValue('wash_receipt_footer_note', 'Periksa kembali barang bawaan Anda sebelum meninggalkan lokasi.') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="wash_receipt_powered_by" class="form-label fw-medium">Teks Powered</label>
                                <input type="text" class="form-control" id="wash_receipt_powered_by" name="wash_receipt_powered_by" value="{{ \App\Models\Setting::getValue('wash_receipt_powered_by', 'POWERED BY MSTORE') }}">
                            </div>
                            <div class="col-12">
                                <label for="wash_receipt_holiday_greeting" class="form-label fw-medium">Ucapan Hari Raya</label>
                                <textarea class="form-control" id="wash_receipt_holiday_greeting" name="wash_receipt_holiday_greeting" rows="3">{{ \App\Models\Setting::getValue('wash_receipt_holiday_greeting', 'Selamat Hari Raya  Idhul Fitri Mohon Maaf Lahir & Batin.') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-user-gear me-1"></i> Akun Wash
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="wash_account_username" class="form-label fw-medium">Username Akun</label>
                                <input type="text" class="form-control" id="wash_account_username" name="wash_account_username" value="{{ \App\Models\Setting::getValue('wash_account_username', '') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="wash_account_password" class="form-label fw-medium">Password Akun</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="wash_account_password" name="wash_account_password" value="{{ \App\Models\Setting::getValue('wash_account_password', '') }}" autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="wash_account_password" aria-label="Tampilkan/Sembunyikan Password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-qrcode me-1"></i> Pembayaran QRIS
                        </h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="pos_qris_text" class="form-label fw-medium">Data String QRIS</label>
                                <textarea class="form-control" id="pos_qris_text" name="pos_qris_text" rows="3">{{ \App\Models\Setting::getValue('pos_qris_text', '') }}</textarea>
                                <div class="small text-muted mt-1">Isi QRIS statis atau data QRIS yang dipakai di kasir Wash.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-gift me-1"></i> Bonus Loyalty Cuci
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="wash_loyalty_target" class="form-label fw-medium">Target Cuci untuk Mendapatkan Bonus Gratis</label>
                                <input type="number" min="1" class="form-control" id="wash_loyalty_target" name="wash_loyalty_target" value="{{ \App\Models\Setting::getValue('wash_loyalty_target', '11') }}">
                                <div class="small text-muted mt-1">Misalnya isi 11, maka pada cuci ke-11 akan mendapat bonus gratis 1 layanan.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-hand-holding-dollar me-1"></i> Tarif Komisi Karyawan Wash
                        </h6>
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <div class="small text-muted mb-2">
                                    Tarif komisi per item layanan (dalam Rupiah) berdasarkan jenis kendaraan dan ukuran.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold mb-3 text-secondary">
                                            <i class="fa-solid fa-car me-1"></i> Mobil (Car)
                                        </h6>
                                        <div class="mb-3">
                                            <label for="wash_commission_car_small_medium" class="form-label fw-medium">Kecil &amp; Sedang (Small/Medium)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white">Rp</span>
                                                <input type="number" min="0" class="form-control" id="wash_commission_car_small_medium" name="wash_commission_car_small_medium" value="{{ \App\Models\Setting::getValue('wash_commission_car_small_medium', '13000') }}">
                                            </div>
                                            <div class="small text-muted mt-1">Contoh: Avanza, Xenia, Vios, Jazz, dll.</div>
                                        </div>
                                        <div>
                                            <label for="wash_commission_car_large_xlarge" class="form-label fw-medium">Besar &amp; Extra Besar (Large/XL)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white">Rp</span>
                                                <input type="number" min="0" class="form-control" id="wash_commission_car_large_xlarge" name="wash_commission_car_large_xlarge" value="{{ \App\Models\Setting::getValue('wash_commission_car_large_xlarge', '15000') }}">
                                            </div>
                                            <div class="small text-muted mt-1">Contoh: Fortuner, Pajero, Alphard, Innova, dll.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold mb-3 text-secondary">
                                            <i class="fa-solid fa-motorcycle me-1"></i> Motor (Motorcycle)
                                        </h6>
                                        <div class="mb-3">
                                            <label for="wash_commission_motor_small_medium" class="form-label fw-medium">Kecil &amp; Sedang (Small/Medium)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white">Rp</span>
                                                <input type="number" min="0" class="form-control" id="wash_commission_motor_small_medium" name="wash_commission_motor_small_medium" value="{{ \App\Models\Setting::getValue('wash_commission_motor_small_medium', '6000') }}">
                                            </div>
                                            <div class="small text-muted mt-1">Contoh: Beat, Mio, Vario 125, Supra X, dll.</div>
                                        </div>
                                        <div>
                                            <label for="wash_commission_motor_large_xlarge" class="form-label fw-medium">Besar &amp; Extra Besar (Large/XL)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white">Rp</span>
                                                <input type="number" min="0" class="form-control" id="wash_commission_motor_large_xlarge" name="wash_commission_motor_large_xlarge" value="{{ \App\Models\Setting::getValue('wash_commission_motor_large_xlarge', '8000') }}">
                                            </div>
                                            <div class="small text-muted mt-1">Contoh: NMAX, PCX, XMAX, Aerox, Ninja, Harley, dll.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="hidden" name="wash_commission_exclude_free_wash" value="0">
                                    <input class="form-check-input" type="checkbox" value="1" id="wash_commission_exclude_free_wash" name="wash_commission_exclude_free_wash" {{ \App\Models\Setting::getValue('wash_commission_exclude_free_wash', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="wash_commission_exclude_free_wash">Jangan hitung komisi untuk cuci gratis (Rp 0)</label>
                                </div>
                                <div class="small text-muted mt-1 ms-4">Transaksi gratis/total Rp 0 tidak menghasilkan komisi.</div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="hidden" name="wash_commission_only_main_services" value="0">
                                    <input class="form-check-input" type="checkbox" value="1" id="wash_commission_only_main_services" name="wash_commission_only_main_services" {{ \App\Models\Setting::getValue('wash_commission_only_main_services', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="wash_commission_only_main_services">Hanya layanan utama (main services)</label>
                                </div>
                                <div class="small text-muted mt-1 ms-4">Hanya layanan kategori "main" yang dihitung komisi.</div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="hidden" name="wash_commission_require_employee" value="0">
                                    <input class="form-check-input" type="checkbox" value="1" id="wash_commission_require_employee" name="wash_commission_require_employee" {{ \App\Models\Setting::getValue('wash_commission_require_employee', '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="wash_commission_require_employee">Wajib pilih karyawan per item</label>
                                </div>
                                <div class="small text-muted mt-1 ms-4">Jika aktif, tampilkan peringatan di POS jika item tanpa karyawan.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-calendar-days me-1"></i> Jadwal Harga Hari Raya
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="wash_holiday_pricing_start_date" class="form-label fw-medium">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="wash_holiday_pricing_start_date" name="wash_holiday_pricing_start_date" value="{{ \App\Models\Setting::getValue('wash_holiday_pricing_start_date', '') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="wash_holiday_pricing_end_date" class="form-label fw-medium">Tanggal Selesai</label>
                                <input type="date" class="form-control" id="wash_holiday_pricing_end_date" name="wash_holiday_pricing_end_date" value="{{ \App\Models\Setting::getValue('wash_holiday_pricing_end_date', '') }}">
                            </div>
                            <div class="col-12">
                                <div class="small text-muted">Harga hari raya aktif otomatis jika tanggal hari ini berada di antara tanggal mulai dan tanggal selesai.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-brands fa-telegram me-1"></i> Notifikasi Telegram
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="hidden" name="telegram_wash_notification_enabled" value="0">
                                    <input class="form-check-input" type="checkbox" value="1" id="telegram_wash_notification_enabled" name="telegram_wash_notification_enabled" {{ \App\Models\Setting::getValue('telegram_wash_notification_enabled', '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="telegram_wash_notification_enabled">Aktifkan Notifikasi Transaksi Wash</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="telegram_wash_group_id" class="form-label fw-medium">ID Grup Telegram</label>
                                <input type="text" class="form-control" id="telegram_wash_group_id" name="telegram_wash_group_id" value="{{ \App\Models\Setting::getValue('telegram_wash_group_id', '') }}">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Simpan Pengaturan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-toggle-password]').forEach((toggleButton) => {
        toggleButton.addEventListener('click', function () {
            const inputId = this.getAttribute('data-toggle-password');
            const input = document.getElementById(inputId);
            if (!input) {
                return;
            }
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon?.classList.remove('fa-eye');
                icon?.classList.add('fa-eye-slash');
                return;
            }
            input.type = 'password';
            icon?.classList.remove('fa-eye-slash');
            icon?.classList.add('fa-eye');
        });
    });
</script>
@endpush
