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
                        </div>
                    </div>

                    @php
                        $days = [
                            'Monday' => 'Senin',
                            'Tuesday' => 'Selasa',
                            'Wednesday' => 'Rabu',
                            'Thursday' => 'Kamis',
                            'Friday' => 'Jumat',
                            'Saturday' => 'Sabtu',
                            'Sunday' => 'Minggu',
                        ];
                        $scheduleTeknisi = json_decode($settings['weekly_schedule_teknisi'] ?? '{}', true);
                        $scheduleWash = json_decode($settings['weekly_schedule_wash'] ?? '{}', true);
                    @endphp

                    <!-- ==================== SHIFT TEKNISI ==================== -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-user-gear me-1"></i> Shift Teknisi
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th class="align-middle" style="width:35%">Nama Shift</th>
                                        <th class="align-middle" style="width:30%">Jam Mulai</th>
                                        <th class="align-middle" style="width:30%">Jam Selesai</th>
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
                                        <th class="align-middle" style="width:35%">Nama Shift</th>
                                        <th class="align-middle" style="width:30%">Jam Mulai</th>
                                        <th class="align-middle" style="width:30%">Jam Selesai</th>
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
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted d-block mt-2">* Jadwal mingguan per grup akan merujuk ke Shift 1 / Shift 2 / Longshift masing-masing grup</small>
                    </div>

                    <div class="alert alert-info border mb-4">
                        <div class="fw-semibold mb-2">
                            <i class="fa-solid fa-circle-info me-1"></i>Cara Pengaturan Jadwal Mingguan
                        </div>
                        <ol class="mb-0 ps-3 small">
                            <li class="mb-1">Atur dulu jam Shift 1, Shift 2, dan Longshift di bagian atas (Teknisi/Wash).</li>
                            <li class="mb-1">Di tabel mingguan, centang kolom <strong>Aktif</strong> untuk hari kerja.</li>
                            <li class="mb-1">Pilih jenis shift hari itu: <strong>Shift 1</strong>, <strong>Shift 2</strong>, atau <strong>Longshift</strong>.</li>
                            <li class="mb-1">Hari yang tidak dicentang akan dianggap <strong>OFF</strong> saat dipakai sebagai referensi.</li>
                            <li>Simpan pengaturan, lalu buka menu <strong>Schedules</strong> untuk atur jadwal per orang (S1/S2/LS/OFF).</li>
                        </ol>
                    </div>

                    <!-- ==================== JADWAL MINGGUAN TEKNISI ==================== -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-calendar-week me-1"></i> Jadwal Mingguan Teknisi
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th class="align-middle">Hari</th>
                                        <th class="align-middle" style="width:90px">Aktif</th>
                                        <th class="align-middle" style="width:200px">Pilih Shift</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($days as $key => $label)
                                        @php
                                            $dayData = $scheduleTeknisi[$key] ?? ['enabled' => false, 'shift' => 'shift1'];
                                            $oldDay = old("weekly_schedule_teknisi.{$key}");
                                            $isEnabled = $oldDay ? ($oldDay['enabled'] ?? false) : !empty($dayData['enabled']);
                                            $selectedShift = $oldDay ? ($oldDay['shift'] ?? 'shift1') : ($dayData['shift'] ?? 'shift1');
                                        @endphp
                                        <tr>
                                            <td class="fw-medium align-middle ps-3">{{ $label }}</td>
                                            <td class="text-center align-middle">
                                                <input type="hidden" name="weekly_schedule_teknisi[{{ $key }}][enabled]" value="0">
                                                <input class="form-check-input" type="checkbox"
                                                    name="weekly_schedule_teknisi[{{ $key }}][enabled]" value="1"
                                                    {{ $isEnabled ? 'checked' : '' }}>
                                            </td>
                                            <td class="align-middle">
                                                <select name="weekly_schedule_teknisi[{{ $key }}][shift]" class="form-select form-select-sm schedule-shift">
                                                    <option value="shift1" {{ $selectedShift === 'shift1' ? 'selected' : '' }}>Shift 1</option>
                                                    <option value="shift2" {{ $selectedShift === 'shift2' ? 'selected' : '' }}>Shift 2</option>
                                                    <option value="longshift" {{ $selectedShift === 'longshift' ? 'selected' : '' }}>Longshift</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ==================== JADWAL MINGGUAN OPERATOR WASH ==================== -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-calendar-days me-1"></i> Jadwal Mingguan Operator Wash
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th class="align-middle">Hari</th>
                                        <th class="align-middle" style="width:90px">Aktif</th>
                                        <th class="align-middle" style="width:200px">Pilih Shift</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($days as $key => $label)
                                        @php
                                            $dayData = $scheduleWash[$key] ?? ['enabled' => false, 'shift' => 'shift1'];
                                            $oldDay = old("weekly_schedule_wash.{$key}");
                                            $isEnabled = $oldDay ? ($oldDay['enabled'] ?? false) : !empty($dayData['enabled']);
                                            $selectedShift = $oldDay ? ($oldDay['shift'] ?? 'shift1') : ($dayData['shift'] ?? 'shift1');
                                        @endphp
                                        <tr>
                                            <td class="fw-medium align-middle ps-3">{{ $label }}</td>
                                            <td class="text-center align-middle">
                                                <input type="hidden" name="weekly_schedule_wash[{{ $key }}][enabled]" value="0">
                                                <input class="form-check-input" type="checkbox"
                                                    name="weekly_schedule_wash[{{ $key }}][enabled]" value="1"
                                                    {{ $isEnabled ? 'checked' : '' }}>
                                            </td>
                                            <td class="align-middle">
                                                <select name="weekly_schedule_wash[{{ $key }}][shift]" class="form-select form-select-sm schedule-shift">
                                                    <option value="shift1" {{ $selectedShift === 'shift1' ? 'selected' : '' }}>Shift 1</option>
                                                    <option value="shift2" {{ $selectedShift === 'shift2' ? 'selected' : '' }}>Shift 2</option>
                                                    <option value="longshift" {{ $selectedShift === 'longshift' ? 'selected' : '' }}>Longshift</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Disable select shift jika hari tidak aktif
    document.querySelectorAll('input[type="checkbox"][name*="[enabled]"]').forEach(cb => {
        const select = cb.closest('tr').querySelector('.schedule-shift');
        toggleShiftSelect(cb, select);
        
        cb.addEventListener('change', function() {
            toggleShiftSelect(this, select);
        });
    });
    
    function toggleShiftSelect(checkbox, select) {
        select.disabled = !checkbox.checked;
        select.style.opacity = checkbox.checked ? '1' : '0.5';
    }
});
</script>
@endpush
@endsection
