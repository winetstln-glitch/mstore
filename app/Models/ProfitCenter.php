<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitCenter extends Model
{
    protected $fillable = ['code', 'name', 'description', 'is_active'];

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }
}
