<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UrgentLeaveRequestNotification extends Notification
{
    use Queueable;

    public $leave;

    public function __construct(LeaveRequest $leave)
    {
        $this->leave = $leave;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subject' => 'Urgent leave request',
            'message' => $this->leave->user->name . ' mengajukan izin mendadak: ' . $this->leave->reason,
            'url' => route('leave-requests.index', ['reason_keyword' => 'mendadak']),
            'leave_request_id' => $this->leave->id,
        ];
    }
}

