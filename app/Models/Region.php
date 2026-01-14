<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = ['name', 'description'];

    public function coordinators(): HasMany
    {
        return $this->hasMany(Coordinator::class);
    }

    public function odps(): HasMany
    {
        return $this->hasMany(Odp::class);
    }
}
