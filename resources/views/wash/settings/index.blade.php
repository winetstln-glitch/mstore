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
