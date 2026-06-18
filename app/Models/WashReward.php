<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class WashReward extends Model
{
    use Auditable;

    protected $fillable = ['name', 'description', 'points_required', 'stock', 'image', 'is_active'];
}
