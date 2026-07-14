<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashSupplier extends Model
{
    use Auditable;

    protected $fillable = ['code', 'name', 'address', 'phone', 'email', 'pic', 'is_active'];

    public function stockItems(): HasMany
    {
        return $this->hasMany(WashStockItem::class);
    }
}
