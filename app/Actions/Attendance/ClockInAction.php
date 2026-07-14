<?php

namespace App\Actions\Attendance;

use App\Events\Attendance\ClockInCreated;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\User;
use App\Notifications\Attendance\ClockInNotification;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\LocationVerificationService;
use App\Services\WhatsApp\WhatsAppIntegrationRouter;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClockInAction
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly LocationVerificationService $locationVerificationService,
        private readonly WhatsAppIntegrationRouter $whatsAppRouter
    ) {}

    public function execute(User $user, array $data): TechnicianAttendance
    {
        $lock = Cache::lock("attendance:clockin:user:{$user->id}", 10);

        if (!$lock->get()) {
            throw new \RuntimeException('Terjadi kesalahan, silakan coba lagi.');
        }

        try {
            DB::beginTransaction();

            $now = Carbon::now();
            $today = Carbon::today();
            $clockInStart = $this->attendanceService->resolveClockInWindow($user);

            if ($clockInStart['status'] === 'off') {
                if (!$this->attendanceService->canViewAllAttendanceData($user)) {
                    throw new \RuntimeException('Anda tidak memiliki jadwal hari ini dan tidak diizinkan melakukan absensi.');
                }
            }

            if ($this->attendanceService->hasApprovedLeave($user, $today)) {
                throw new \RuntimeException('Anda tidak dapat melakukan absensi karena sedang cuti/izin.');
            }

            $existingAttendance = $this->attendanceService->getTodayAttendance($user);
            if ($existingAttendance && $existingAttendance->clock_in) {
                throw new \RuntimeException('Anda sudah melakukan absensi masuk hari ini.');
            }
            if ($existingAttendance && $existingAttendance->status === 'alpha') {
                throw new \RuntimeException('Anda sudah tercatat sebagai Alpha hari ini. Tidak bisa melakukan clock-in.');
            }

            $allowAfterCutoff = (bool) Setting::getValue('attendance_allow_after_cutoff', false);
            if (!$allowAfterCutoff && $this->attendanceService->isPastCutoffTime($clockInStart['shift_cutoff'], $now)) {
                $cutoffTime = $clockInStart['shift_cutoff'];
                throw new \RuntimeException("Anda tidak bisa melakukan absen sudah melewati batas toleransi absen jam {$cutoffTime} WIB. Status kehadiran Anda akan dicatat sebagai Alpha.");
            }

            $maxDistance = (float) Setting::getValue(
                'attendance_radius',
                Setting::getValue('attendance_max_distance_meters', 100)
            );
            $officeLat = (float) Setting::getValue(
                'attendance_office_lat',
                Setting::getValue('office_latitude', 0)
            );
            $officeLng = (float) Setting::getValue(
                'attendance_office_lng',
                Setting::getValue('office_longitude', 0)
            );
            $enableLocationCheck = $officeLat !== 0.0 && $officeLng !== 0.0;

            $lat = (float) ($data['lat'] ?? 0);
            $lng = (float) ($data['lng'] ?? 0);

            if ($enableLocationCheck && ($lat !== 0.0 || $lng !== 0.0)) {
                $distance = $this->attendanceService->calculateDistance(
                    $lat,
                    $lng,
                    $officeLat,
                    $officeLng
                );

                if ($distance > $maxDistance) {
                    throw new \RuntimeException('Anda terlalu jauh dari kantor untuk melakukan absensi.');
                }
            }

            $deviceFingerprint = $this->attendanceService->resolveAttendanceDeviceFingerprint($data['request']);
            $photoRequiredValue = Setting::getValue(
                'attendance_photo_required',
                Setting::getValue('attendance_enable_photo', '1')
            );
            $enablePhoto = in_array(strtolower((string) $photoRequiredValue), ['1', 'true', 'yes', 'on'], true);
            $photoClockIn = null;

            if ($enablePhoto && (! isset($data['photo']) || ! $data['photo'] instanceof UploadedFile)) {
                throw new \RuntimeException('Foto selfie wajib diunggah untuk absensi.');
            }

            if ($enablePhoto && isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $maxKb = $this->attendanceService->resolveAttendancePhotoMaxKb($user);
                if ($data['photo']->getSize() > $maxKb * 1024) {
                    throw new \RuntimeException("Ukuran foto terlalu besar. Maksimal {$maxKb} KB.");
                }

                $photoClockIn = $data['photo']->store('attendance/clockin', 'public');
            }

            $status = $this->attendanceService->determineClockInStatus(
                $clockInStart['official_start'],
                $clockInStart['shift_cutoff'],
                $now
            );

            $attendanceData = [
                'user_id' => $user->id,
                'clock_in' => $now,
                'work_date' => $today->toDateString(),
                'photo_clock_in' => $photoClockIn,
                'lat_clock_in' => $lat !== 0.0 ? $lat : null,
                'lng_clock_in' => $lng !== 0.0 ? $lng : null,
                'device_fingerprint_clock_in' => $deviceFingerprint,
                'ip_clock_in' => $data['request']->ip() ?? null,
                'user_agent_clock_in' => $data['request']->userAgent() ?? null,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'source' => 'web',
            ];

            $locationNotes = $this->locationVerificationService->verifyLocation($user, $lat, $lng);
            if ($locationNotes) {
                if (!empty($attendanceData['notes'])) {
                    $attendanceData['notes'] = $locationNotes . ' | ' . $attendanceData['notes'];
                } else {
                    $attendanceData['notes'] = $locationNotes;
                }
            }

            $attendance = $existingAttendance
                ? $existingAttendance->fill($attendanceData)
                : new TechnicianAttendance($attendanceData);

            $attendance->save();

            DB::commit();

            try {
                event(new ClockInCreated($attendance));
                $user->notify(new ClockInNotification($attendance));
                $this->whatsAppRouter->sendAttendanceNotification($user, 'clock_in', [
                    'status' => $status,
                ]);
            } catch (\Throwable $e) {
                Log::error("Failed to send clock-in notification: {$e->getMessage()}", ['user_id' => $user->id]);
            }

            return $attendance;
        } catch (\Throwable $e) {
            DB::rollBack();
            if (isset($photoClockIn) && Storage::disk('public')->exists($photoClockIn)) {
                Storage::disk('public')->delete($photoClockIn);
            }
            throw $e;
        } finally {
            $lock->release();
        }
    }
}
