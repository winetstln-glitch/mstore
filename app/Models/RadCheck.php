<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadCheck extends Model
{
    protected $table = 'radcheck';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'attribute',
        'op',
        'value',
    ];
}
