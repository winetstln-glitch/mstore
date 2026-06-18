<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashShift extends Model
{
    use Auditable;

    protected $fillable = ['name', 'start_time', 'end_time', 'description', 'is_active'];

    public function sessions(): HasMany
    {
        return $this->hasMany(WashShiftSession::class);
    }
}
