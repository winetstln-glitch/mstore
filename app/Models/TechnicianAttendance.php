<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'photo_clock_in',
        'photo_clock_out',
        'lat_clock_in',
        'lng_clock_in',
        'lat_clock_out',
        'lng_clock_out',
        'device_fingerprint_clock_in',
        'device_fingerprint_clock_out',
        'ip_clock_in',
        'ip_clock_out',
        'user_agent_clock_in',
        'user_agent_clock_out',
        'status',
        'late_minutes',
        'permission_minutes',
        'notes',
        'generated_type',
        'edited_by',
        'edit_reason',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Get shift info for this attendance record.
     */
    public function getShiftInfoAttribute()
    {
        $user = $this->user;
        $date = $this->work_date ?? $this->clock_in ?? now();
        
        $group = 'teknisi';
        $roleName = strtolower((string) ($user->role?->name ?? ''));
        if (in_array($roleName, ['kasir-wash', 'karyawan-wash'], true)) {
            $group = 'wash';
        } else {
            static $washEmployeesCache = null;
            if ($washEmployeesCache === null) {
                $washEmployeesCache = \Schema::hasTable('wash_employees') 
                    ? \App\Models\WashEmployee::pluck('user_id')->all() 
                    : [];
            }
            if (in_array($user->id, $washEmployeesCache)) {
                $group = 'wash';
            }
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
