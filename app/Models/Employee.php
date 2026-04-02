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
        'document_path',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date' => 'date',
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
