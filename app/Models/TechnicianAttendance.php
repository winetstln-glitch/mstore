<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianAttendance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get shift info for this attendance record.
     */
    public function getShiftInfoAttribute()
    {
        $user = $this->user;
        $date = $this->clock_in;
        
        $group = 'teknisi';
        $roleName = strtolower((string) ($user->role?->name ?? ''));
        if (in_array($roleName, ['kasir-wash', 'karyawan-wash'], true)) {
            $group = 'wash';
        } elseif (\Schema::hasTable('wash_employees') && \App\Models\WashEmployee::where('user_id', $user->id)->exists()) {
            $group = 'wash';
        }

        $status = null;
        $isExcludedFromSchedule = in_array($roleName, ['admin', 'leader', 'owner', 'owner-pendiri', 'direktur', 'coordinator'], true);

        if (! $isExcludedFromSchedule) {
            $daily = \App\Models\TechnicianDailySchedule::where('user_id', $user->id)
                ->whereDate('date', $date->toDateString())
                ->first();
            if ($daily) {
                $status = $daily->status;
            }

            if ($status === null) {
                $weekly = \App\Models\TechnicianSchedule::where('user_id', $user->id)
                    ->where('year', $date->year)
                    ->where('week_number', $date->weekOfYear)
                    ->first();
                if ($weekly) {
                    $status = $weekly->status;
                }
            }
        }

        // If no schedule found, or excluded (admin/leader), default to 'piket' (Shift 1)
        if (! in_array($status, ['piket', 'backup', 'longshift', 'off'], true)) {
            $status = 'piket';
        }

        $shiftConfig = [
            'shift_1_start' => \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_shift_1_start' : 'schedule_teknisi_shift_1_start', '08:00'),
            'shift_1_end' => \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_shift_1_end' : 'schedule_teknisi_shift_1_end', '17:00'),
            'shift_2_start' => \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_shift_2_start' : 'schedule_teknisi_shift_2_start', '15:00'),
            'shift_2_end' => \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_shift_2_end' : 'schedule_teknisi_shift_2_end', '00:00'),
            'longshift_start' => \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_longshift_start' : 'schedule_teknisi_longshift_start', '08:00'),
            'longshift_end' => \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_longshift_end' : 'schedule_teknisi_longshift_end', '20:00'),
        ];

        $shiftLabel = '-';
        $shiftStart = '-';
        $shiftEnd = '-';

        if ($status === 'longshift') {
            $shiftLabel = 'Longshift';
            $shiftStart = $shiftConfig['longshift_start'];
            $shiftEnd = $shiftConfig['longshift_end'];
        } elseif ($status === 'piket') {
            // Check if this day is configured as shift1/shift2/longshift in weekly settings
            $settingKey = $group === 'wash' ? 'weekly_schedule_wash' : 'weekly_schedule_teknisi';
            $scheduleRaw = (string) \App\Models\Setting::getValue($settingKey, '{}');
            $schedule = json_decode($scheduleRaw, true);
            $dayName = $date->englishDayOfWeek;
            
            $dayConfig = $schedule[$dayName] ?? null;
            $isShiftEnabled = !empty($dayConfig['enabled']);
            $mappedShift = $isShiftEnabled ? ($dayConfig['shift'] ?? 'shift1') : 'shift1';

            if ($mappedShift === 'longshift') {
                $shiftLabel = 'Longshift';
                $shiftStart = $shiftConfig['longshift_start'];
                $shiftEnd = $shiftConfig['longshift_end'];
            } elseif ($mappedShift === 'shift2') {
                $shiftLabel = 'Shift 2';
                $shiftStart = $shiftConfig['shift_2_start'];
                $shiftEnd = $shiftConfig['shift_2_end'];
            } else {
                $shiftLabel = 'Shift 1';
                $shiftStart = $shiftConfig['shift_1_start'];
                $shiftEnd = $shiftConfig['shift_1_end'];
            }
        } elseif ($status === 'backup') {
            $shiftLabel = 'Shift 2';
            $shiftStart = $shiftConfig['shift_2_start'];
            $shiftEnd = $shiftConfig['shift_2_end'];
        }

        return [
            'start' => $shiftStart,
            'end' => $shiftEnd,
            'label' => $shiftLabel,
            'status' => $status,
        ];
    }
}
