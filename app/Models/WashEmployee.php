<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WashEmployee extends Model
{
    protected $fillable = ['name', 'phone', 'status', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
