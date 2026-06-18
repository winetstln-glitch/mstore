<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostCenter extends Model
{
    protected $fillable = ['code', 'name', 'description', 'profit_center_id', 'is_active'];

    public function profitCenter(): BelongsTo
    {
        return $this->belongsTo(ProfitCenter::class);
    }
}
