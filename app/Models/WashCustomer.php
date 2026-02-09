<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WashCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'visit_count',
        'free_wash_eligibility',
    ];

    public function transactions()
    {
        return $this->hasMany(WashTransaction::class);
    }
}
