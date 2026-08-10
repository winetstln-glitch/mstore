<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'user_id',
        'wash_employee_id',
        'date_of_birth',
        'gender',
        'address',
        'phone',
        'email',
        'nik',
        'position',
        'department',
        'join_date',
        'employment_status',
        'monthly_salary',
        'daily_salary',
        'document_path',
        'id_card_photo_path',
        'id_card_expires_at',
        'annual_leave_quota',
        'annual_leave_used',
        'sick_leave_quota',
        'sick_leave_used',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'attendance_card_code',
        'attendance_device_hash',
        'attendance_device_locked_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date' => 'date',
        'id_card_expires_at' => 'date',
        'attendance_device_locked_at' => 'datetime',
        'annual_leave_quota' => 'integer',
        'annual_leave_used' => 'integer',
        'sick_leave_quota' => 'integer',
        'sick_leave_used' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function washEmployee(): BelongsTo
    {
        return $this->belongsTo(WashEmployee::class);
    }
}
