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
        $controller = new \App\Http\Controllers\TechnicianAttendanceController();
        // We need a way to call the private method or replicate logic.
        // For simplicity and to match the existing codebase pattern,
        // we'll use the controller's logic but we need to make it accessible.
        
        // Re-implementing a simplified version of resolveTodayShiftInfo for a specific date
        $user = $this->user;
        $date = $this->clock_in;
        
        $group = 'technician';
        if ($user->role) {
            $roleName = strtolower($user->role->name);
            if (str_contains($roleName, 'wash')) {
                $group = 'wash';
            }
        }

        $status = null;
        $roleName = strtolower((string) ($user->role?->name ?? ''));
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

        $shift1Start = \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_shift_1_start' : 'schedule_teknisi_shift_1_start', '08:00');
        $shift1End = \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_shift_1_end' : 'schedule_teknisi_shift_1_end', '17:00');
        $shift2Start = \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_shift_2_start' : 'schedule_teknisi_shift_2_start', '15:00');
        $shift2End = \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_shift_2_end' : 'schedule_teknisi_shift_2_end', '00:00');
        $longStart = \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_longshift_start' : 'schedule_teknisi_longshift_start', '08:00');
        $longEnd = \App\Models\Setting::getValue($group === 'wash' ? 'schedule_wash_longshift_end' : 'schedule_teknisi_longshift_end', '20:00');

        $start = \App\Models\Setting::getValue('attendance_clock_in_start', '07:00');
        $end = \App\Models\Setting::getValue('attendance_clock_in_end', '13:00');

        if ($status === 'longshift') {
            $start = $longStart;
            $end = $longEnd;
        } elseif ($status === 'piket') {
            $start = $shift1Start;
            $end = $shift1End;
        } elseif ($status === 'backup') {
            $start = $shift2Start;
            $end = $shift2End;
        }

        return [
            'start' => $start,
            'end' => $end,
            'status' => $status ?? 'default',
        ];
    }
}
