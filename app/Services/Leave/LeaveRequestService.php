<?php

namespace App\Services\Leave;

use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
        $totalDays = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if (!$this->isWeekend($current) && !$this->isPublicHoliday($current)) {
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
        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
        ]);

        $this->updateLeaveQuota($leaveRequest);

        return $leaveRequest;
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
