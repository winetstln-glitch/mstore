<?php

namespace App\Services;

use App\Models\TechnicianAttendance;
use App\Models\User;
use Carbon\Carbon;

class TechnicianService
{
    public function clockIn(User $user, $lat, $lng, $photoPath = null)
    {
        // Check if already clocked in today
        $today = Carbon::today();
        $existing = TechnicianAttendance::where('user_id', $user->id)
            ->whereDate('clock_in', $today)
            ->first();

        if ($existing) {
            return $existing; // Already clocked in
        }

        return TechnicianAttendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now(),
            'lat_clock_in' => $lat,
            'lng_clock_in' => $lng,
            'photo_clock_in' => $photoPath,
            'status' => 'present'
        ]);
    }

    public function clockOut(User $user, $lat, $lng, $photoPath = null)
    {
        $today = Carbon::today();
        $attendance = TechnicianAttendance::where('user_id', $user->id)
            ->whereDate('clock_in', $today)
            ->whereNull('clock_out')
            ->first();

        if (!$attendance) {
            return false; // Not clocked in or already clocked out
        }

        $attendance->update([
            'clock_out' => Carbon::now(),
            'lat_clock_out' => $lat,
            'lng_clock_out' => $lng,
            'photo_clock_out' => $photoPath
        ]);

        return $attendance;
    }

    public function getHistory(User $user, $month, $year)
    {
        return TechnicianAttendance::where('user_id', $user->id)
            ->whereMonth('clock_in', $month)
            ->whereYear('clock_in', $year)
            ->get();
    }
}
