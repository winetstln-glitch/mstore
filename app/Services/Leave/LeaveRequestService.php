<?php

namespace App\Services\Leave;

use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    public function __construct() {}

    public function getLeaveRequestsQuery(?int $userId = null, ?string $status = null): Builder
    {
        $query = LeaveRequest::with('user', 'user.role', 'approver');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest();
    }

    public function calculateLeaveDays(User $user, Carbon $startDate, Carbon $endDate, string $type): int
    {
        // Pre-load semua hari libur dalam range sekaligus — satu query saja
        $holidays = PublicHoliday::whereBetween('date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ])->pluck('date')->map(fn ($d) => (string) $d)->all();

        $totalDays = 0;
        $current   = $startDate->copy();

        while ($current->lte($endDate)) {
            if (! $this->isWeekend($current) && ! in_array($current->toDateString(), $holidays)) {
                $totalDays++;
            }
            $current->addDay();
        }

        return $totalDays;
    }

    public function isWeekend(Carbon $date): bool
    {
        return $date->isSaturday() || $date->isSunday();
    }

    /**
     * Cek hari libur untuk tanggal tunggal (tetap ada untuk kompatibilitas).
     * Untuk kalkulasi range hari, gunakan calculateLeaveDays() yang sudah dioptimasi.
     */
    public function isPublicHoliday(Carbon $date): bool
    {
        return PublicHoliday::whereDate('date', $date->toDateString())->exists();
    }

    public function hasEnoughLeaveQuota(User $user, int $requestedDays, string $type): bool
    {
        if ($type === 'sick') {
            $remaining = $user->sick_leave_quota - $user->sick_leave_used;
            return $remaining >= $requestedDays;
        }

        $remaining = $user->annual_leave_quota - $user->annual_leave_used;
        return $remaining >= $requestedDays;
    }

    public function createLeaveRequest(User $user, array $data): LeaveRequest
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $type = $data['type'] ?? 'leave';

        $leaveDays = $this->calculateLeaveDays($user, $startDate, $endDate, $type);

        return LeaveRequest::create([
            'user_id' => $user->id,
            'type' => $type,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'reason' => $data['reason'],
            'leave_days_used' => $leaveDays,
            'document_path' => $data['document_path'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function approveLeaveRequest(LeaveRequest $leaveRequest, User $approver): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver) {
            $leaveRequest->update([
                'status'      => 'approved',
                'approved_by' => $approver->id,
            ]);

            $this->updateLeaveQuota($leaveRequest);

            // Create or update attendance records for the leave period
            $attendanceStatus = match ($leaveRequest->type) {
                'sick'       => 'sick',
                'permission' => 'permit',
                default      => 'leave',
            };

            $start = $leaveRequest->start_date;
            $end   = $leaveRequest->end_date;

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $attendance = \App\Models\TechnicianAttendance::where('user_id', $leaveRequest->user_id)
                    ->where(function ($q) use ($date) {
                        $q->whereDate('clock_in', $date->toDateString())
                          ->orWhereDate('work_date', $date->toDateString());
                    })
                    ->first();

                if (! $attendance) {
                    \App\Models\TechnicianAttendance::create([
                        'user_id'        => $leaveRequest->user_id,
                        'work_date'      => $date->toDateString(),
                        'clock_in'       => $date->toDateString().' 08:00:00',
                        'clock_out'      => $date->toDateString().' 17:00:00',
                        'status'         => $attendanceStatus,
                        'notes'          => ucfirst($attendanceStatus).' otomatis dari pengajuan cuti #'.$leaveRequest->id,
                        'generated_type' => 'leave_request',
                    ]);
                } elseif ($attendance->status === 'alpha') {
                    $attendance->update([
                        'status'         => $attendanceStatus,
                        'notes'          => ucfirst($attendanceStatus).' otomatis dari pengajuan cuti #'.$leaveRequest->id.' (dari alpha)',
                        'generated_type' => 'leave_request',
                    ]);
                }
            }

            return $leaveRequest;
        });
    }

    public function rejectLeaveRequest(LeaveRequest $leaveRequest, string $reason): LeaveRequest
    {
        $leaveRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $leaveRequest;
    }

    private function updateLeaveQuota(LeaveRequest $leaveRequest): void
    {
        $user = $leaveRequest->user;
        $days = $leaveRequest->leave_days_used;

        if ($leaveRequest->type === 'sick') {
            $user->increment('sick_leave_used', $days);
        } else {
            $user->increment('annual_leave_used', $days);
        }
    }

    public function getUserLeaveBalance(User $user): array
    {
        return [
            'annual' => [
                'quota' => $user->annual_leave_quota,
                'used' => $user->annual_leave_used,
                'remaining' => $user->annual_leave_quota - $user->annual_leave_used,
            ],
            'sick' => [
                'quota' => $user->sick_leave_quota,
                'used' => $user->sick_leave_used,
                'remaining' => $user->sick_leave_quota - $user->sick_leave_used,
            ],
        ];
    }
}
