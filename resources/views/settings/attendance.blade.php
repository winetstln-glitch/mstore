@extends('layouts.app')

@section('title', __('Pengaturan Absensi'))

@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card shadow-sm border-top-4 border-primary">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-sliders me-2"></i>{{ __('Pengaturan Absensi') }}</h5>
            </div>
            <div class="card-body">
                <!-- Panduan -->
                <div class="alert alert-info py-2 mb-4">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <strong>Panduan:</strong> Halaman ini untuk mengatur <strong>template jadwal default per grup</strong> (Teknisi, Operator Wash, Lainnya). Untuk mengatur jadwal <strong>individual per karyawan</strong>, gunakan menu <strong>"Jadwal Karyawan"</strong>!
                </div>
                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf

                    <!-- ==================== PENGATURAN UMUM ==================== -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-gear me-1"></i> Pengaturan Umum
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Absen Masuk Lebih Awal (menit)</label>
                                <input type="number" name="attendance_clock_in_early_minutes" 
                                    value="{{ old('attendance_clock_in_early_minutes', $settings['attendance_clock_in_early_minutes'] ?? 60) }}" 
                                    class="form-control" min="0">
                                <div class="form-text">Contoh: 60 = boleh absen 1 jam sebelum jam shift</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Wajib Foto Selfie</label>
                                <select name="attendance_photo_required" class="form-select">
                                    <option value="1" {{ ($settings['attendance_photo_required'] ?? 1) == 1 ? 'selected' : '' }}>Ya</option>
                                    <option value="0" {{ ($settings['attendance_photo_required'] ?? 1) == 0 ? 'selected' : '' }}>Tidak</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Office Latitude</label>
                                <input type="text" name="attendance_office_lat" 
                                    value="{{ old('attendance_office_lat', $settings['attendance_office_lat'] ?? '') }}" 
                                    class="form-control" placeholder="-6.xxxx">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Office Longitude</label>
                                <input type="text" name="attendance_office_lng" 
                                    value="{{ old('attendance_office_lng', $settings['attendance_office_lng'] ?? '') }}" 
                                    class="form-control" placeholder="106.xxxx">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Radius Absen (Meter)</label>
                                <input type="number" name="attendance_radius" 
                                    value="{{ old('attendance_radius', $settings['attendance_radius'] ?? 100) }}" 
                                    class="form-control" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Hari Kerja / Bulan</label>
                                <input type="number" name="attendance_working_days" 
                                    value="{{ old('attendance_working_days', $settings['attendance_working_days'] ?? 28) }}" 
                                    class="form-control" min="1" max="31">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Toleransi Terlambat (Menit)</label>
                                <input type="number" name="attendance_late_tolerance" 
                                    value="{{ old('attendance_late_tolerance', $settings['attendance_late_tolerance'] ?? 0) }}" 
                                    class="form-control" min="0">
                                <div class="form-text">Contoh: 15 = boleh terlambat 15 menit tanpa status late</div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== SHIFT TEKNISI ==================== -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-user-gear me-1"></i> Shift Teknisi
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th class="align-middle" style="width:30%">Nama Shift</th>
                                        <th class="align-middle" style="width:20%">Jam Mulai</th>
                                        <th class="align-middle" style="width:20%">Jam Selesai</th>
                                        <th class="align-middle" style="width:30%">Batas Absen (Maksimal Jam Masuk)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-medium align-middle ps-3">
                                            <span class="badge bg-primary me-2">S1</span> Shift 1 (Pagi)
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_teknisi_shift_1_start"
                                                value="{{ old('schedule_teknisi_shift_1_start', $settings['schedule_teknisi_shift_1_start'] ?? '08:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_teknisi_shift_1_end"
                                                value="{{ old('schedule_teknisi_shift_1_end', $settings['schedule_teknisi_shift_1_end'] ?? '17:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_teknisi_shift_1_cutoff"
                                                value="{{ old('schedule_teknisi_shift_1_cutoff', $settings['schedule_teknisi_shift_1_cutoff'] ?? '10:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium align-middle ps-3">
                                            <span class="badge bg-info text-dark me-2">S2</span> Shift 2 (Siang)
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_teknisi_shift_2_start"
                                                value="{{ old('schedule_teknisi_shift_2_start', $settings['schedule_teknisi_shift_2_start'] ?? '15:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_teknisi_shift_2_end"
                                                value="{{ old('schedule_teknisi_shift_2_end', $settings['schedule_teknisi_shift_2_end'] ?? '00:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_teknisi_shift_2_cutoff"
                                                value="{{ old('schedule_teknisi_shift_2_cutoff', $settings['schedule_teknisi_shift_2_cutoff'] ?? '17:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium align-middle ps-3">
                                            <span class="badge bg-warning text-dark me-2">12 Jam</span> Longshift
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_teknisi_longshift_start"
                                                value="{{ old('schedule_teknisi_longshift_start', $settings['schedule_teknisi_longshift_start'] ?? '08:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_teknisi_longshift_end"
                                                value="{{ old('schedule_teknisi_longshift_end', $settings['schedule_teknisi_longshift_end'] ?? '20:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_teknisi_longshift_cutoff"
                                                value="{{ old('schedule_teknisi_longshift_cutoff', $settings['schedule_teknisi_longshift_cutoff'] ?? '10:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ==================== SHIFT OPERATOR WASH ==================== -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-soap me-1"></i> Shift Operator Wash
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th class="align-middle" style="width:30%">Nama Shift</th>
                                        <th class="align-middle" style="width:20%">Jam Mulai</th>
                                        <th class="align-middle" style="width:20%">Jam Selesai</th>
                                        <th class="align-middle" style="width:30%">Batas Absen (Maksimal Jam Masuk)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-medium align-middle ps-3">
                                            <span class="badge bg-primary me-2">S1</span> Shift 1 (Pagi)
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_wash_shift_1_start"
                                                value="{{ old('schedule_wash_shift_1_start', $settings['schedule_wash_shift_1_start'] ?? '08:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_wash_shift_1_end"
                                                value="{{ old('schedule_wash_shift_1_end', $settings['schedule_wash_shift_1_end'] ?? '17:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_wash_shift_1_cutoff"
                                                value="{{ old('schedule_wash_shift_1_cutoff', $settings['schedule_wash_shift_1_cutoff'] ?? '10:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium align-middle ps-3">
                                            <span class="badge bg-info text-dark me-2">S2</span> Shift 2 (Siang)
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_wash_shift_2_start"
                                                value="{{ old('schedule_wash_shift_2_start', $settings['schedule_wash_shift_2_start'] ?? '13:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_wash_shift_2_end"
                                                value="{{ old('schedule_wash_shift_2_end', $settings['schedule_wash_shift_2_end'] ?? '22:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_wash_shift_2_cutoff"
                                                value="{{ old('schedule_wash_shift_2_cutoff', $settings['schedule_wash_shift_2_cutoff'] ?? '15:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium align-middle ps-3">
                                            <span class="badge bg-warning text-dark me-2">12 Jam</span> Longshift
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_wash_longshift_start"
                                                value="{{ old('schedule_wash_longshift_start', $settings['schedule_wash_longshift_start'] ?? '08:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_wash_longshift_end"
                                                value="{{ old('schedule_wash_longshift_end', $settings['schedule_wash_longshift_end'] ?? '20:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="time" name="schedule_wash_longshift_cutoff"
                                                value="{{ old('schedule_wash_longshift_cutoff', $settings['schedule_wash_longshift_cutoff'] ?? '10:00') }}"
                                                class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ==================== PENGATURAN NOTIFIKASI (WHATSAPP & TELEGRAM) ==================== -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-bell me-1"></i> Notifikasi Grup Absensi
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-2">
                                        <label class="form-label fw-bold mb-1"><i class="fa-brands fa-whatsapp me-1 text-success"></i> WhatsApp (Fonnte)</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_attendance_notification_enabled" name="whatsapp_attendance_notification_enabled" value="1" {{ ($settings['whatsapp_attendance_notification_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="whatsapp_attendance_notification_enabled">Aktifkan Notifikasi WhatsApp</label>
                                        </div>
                                        <input type="text" name="whatsapp_attendance_group_id" 
                                            value="{{ old('whatsapp_attendance_group_id', $settings['whatsapp_attendance_group_id'] ?? $settings['whatsapp_group_notification_id'] ?? '') }}" 
                                            class="form-control form-control-sm" placeholder="ID Grup WhatsApp (628xxx@g.us)">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-2">
                                        <label class="form-label fw-bold mb-1"><i class="fa-brands fa-telegram me-1 text-info"></i> Telegram</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="telegram_attendance_notification_enabled" name="telegram_attendance_notification_enabled" value="1" {{ ($settings['telegram_attendance_notification_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="telegram_attendance_notification_enabled">Aktifkan Notifikasi Telegram</label>
                                        </div>
                                        <input type="text" name="telegram_attendance_group_id" 
                                            value="{{ old('telegram_attendance_group_id', $settings['telegram_attendance_group_id'] ?? $settings['telegram_technician_group_chat_id'] ?? '') }}" 
                                            class="form-control form-control-sm" placeholder="ID Grup Telegram (-100xxx)">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-text small text-muted mt-2">
                            <i class="fa-solid fa-circle-info me-1"></i> Pengaturan lebih lanjut dapat diakses di menu 
                            <a href="{{ route('whatsapp.index') }}" class="text-success fw-bold">WhatsApp Settings</a> atau 
                            <a href="{{ route('telegram.index') }}" class="text-info fw-bold">Telegram Settings</a>.
                        </div>
                    </div>

                    <!-- ==================== PENGATURAN FOTO ==================== -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-camera me-1"></i> Pengaturan Foto
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Maks. Ukuran (KB)</label>
                                <input type="number" name="attendance_photo_max_kb" 
                                    value="{{ old('attendance_photo_max_kb', $settings['attendance_photo_max_kb'] ?? 2048) }}" 
                                    class="form-control" min="100" max="5120">
                                <div class="form-text">Rekomendasi: 1024-3072 KB</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Maks. Lebar (px)</label>
                                <input type="number" name="attendance_photo_max_width" 
                                    value="{{ old('attendance_photo_max_width', $settings['attendance_photo_max_width'] ?? 1280) }}" 
                                    class="form-control" min="320" max="2560">
                                <div class="form-text">Foto di-resize otomatis</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Kualitas Kompresi</label>
                                <input type="number" name="attendance_photo_compress_quality" 
                                    value="{{ old('attendance_photo_compress_quality', $settings['attendance_photo_compress_quality'] ?? 70) }}" 
                                    class="form-control" min="45" max="95">
                                <div class="form-text">Rentang 45-95, kecil = cepat</div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== TOMBOL SIMPAN ==================== -->
                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa-solid fa-save me-1"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection
