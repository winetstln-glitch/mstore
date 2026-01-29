<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherTemplate extends Model
{
    protected $fillable = ['name', 'html_content', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
