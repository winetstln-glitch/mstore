<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashService extends Model
{
    protected $fillable = [
        'name', 'vehicle_type', 'service_category', 'size_tier', 'package_type', 'sort_order', 'price', 'cost_price', 'holiday_price', 'description', 'image', 'is_active', 'wash_stock_item_id',
    ];

    public const CATEGORY_OPTIONS = [
        'main' => 'Layanan Utama',
        'addon' => 'Add On',
        'skincare' => 'Skincare',
    ];

    public const SIZE_TIER_OPTIONS = [
        'none' => '-',
        'kecil' => 'Kecil',
        'sedang' => 'Sedang',
        'besar' => 'Besar',
        'extra_besar' => 'Extra Besar',
    ];

    public const PACKAGE_TYPE_OPTIONS = [
        'general' => 'General',
        'body_only' => 'Body Only',
        'full_clean' => 'Body + Kolong + Vacuum',
        'express' => 'Cuci Cepat + Semir Ban',
        'engine_cleaner' => 'Cleaner Mesin',
        'leather_cleaner' => 'Cleaner Jok Kulit',
    ];

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_OPTIONS[$this->service_category] ?? ucfirst((string) $this->service_category);
    }

    public function getSizeTierLabelAttribute(): string
    {
        return self::SIZE_TIER_OPTIONS[$this->size_tier] ?? ucfirst(str_replace('_', ' ', (string) $this->size_tier));
    }

    public function getPackageTypeLabelAttribute(): string
    {
        return self::PACKAGE_TYPE_OPTIONS[$this->package_type] ?? ucfirst(str_replace('_', ' ', (string) $this->package_type));
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(WashServicePriceRule::class)->orderBy('sort_order')->orderBy('id');
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(WashStockItem::class);
    }
}
