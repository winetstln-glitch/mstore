<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'description',
        'is_national',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_national' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function isHoliday($date)
    {
        return self::whereDate('date', $date)->exists();
    }
}
