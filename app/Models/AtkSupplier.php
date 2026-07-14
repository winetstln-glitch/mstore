<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtkSupplier extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'address', 'description',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(AtkProduct::class);
    }
}
