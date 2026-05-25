<?php

namespace App\Actions\Attendance;

use App\Events\Attendance\ClockOutCreated;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\User;
use App\Notifications\Attendance\ClockOutNotification;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\LocationVerificationService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClockOutAction
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly LocationVerificationService $locationVerificationService
    ) {}

    public function execute(User $user, TechnicianAttendance $attendance, array $data): TechnicianAttendance
    {
        $lock = Cache::lock("attendance:clockout:user:{$user->id}", 10);

        if (!$lock->get()) {
            throw new \RuntimeException('Terjadi kesalahan, silakan coba lagi.');
        }

        try {
            DB::beginTransaction();

            $now = Carbon::now();
            $deviceFingerprint = $this->attendanceService->resolveAttendanceDeviceFingerprint($data['request']);
            $enablePhoto = (bool) Setting::getValue('attendance_enable_photo', true);
            $photoClockOut = null;

            if ($enablePhoto && isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $maxKb = $this->attendanceService->resolveAttendancePhotoMaxKb($user);
                if ($data['photo']->getSize() > $maxKb * 1024) {
                    throw new \RuntimeException("Ukuran foto terlalu besar. Maksimal {$maxKb} KB.");
                }

                $photoClockOut = $data['photo']->store('attendance/clockout', 'public');
            }

            $lat = (float) ($data['lat'] ?? 0);
            $lng = (float) ($data['lng'] ?? 0);

            $attendanceData = [
                'clock_out' => $now,
                'photo_clock_out' => $photoClockOut,
                'lat_clock_out' => $lat !== 0.0 ? $lat : null,
                'lng_clock_out' => $lng !== 0.0 ? $lng : null,
                'device_fingerprint_clock_out' => $deviceFingerprint,
                'ip_clock_out' => $data['request']->ip() ?? null,
                'user_agent_clock_out' => $data['request']->userAgent() ?? null,
            ];

            if (!empty($data['notes'])) {
                if (!empty($attendance->notes)) {
                    $attendanceData['notes'] = $attendance->notes . ' | Clock Out: ' . $data['notes'];
                } else {
                    $attendanceData['notes'] = 'Clock Out: ' . $data['notes'];
                }
            }

            $attendance->fill($attendanceData);
            $attendance->save();

            DB::commit();

            try {
                event(new ClockOutCreated($attendance));
                $user->notify(new ClockOutNotification($attendance));
            } catch (\Throwable $e) {
                Log::error("Failed to send clock-out notification: {$e->getMessage()}", ['user_id' => $user->id]);
            }

            return $attendance;
        } catch (\Throwable $e) {
            DB::rollBack();
            if (isset($photoClockOut) && Storage::disk('public')->exists($photoClockOut)) {
                Storage::disk('public')->delete($photoClockOut);
            }
            throw $e;
        } finally {
            $lock->release();
        }
    }
}
