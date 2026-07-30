<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class HotspotBanner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'mobile_image_path',
        'cta_text',
        'url_cta',
        'open_new_tab',
        'sort_order',
        'is_active',
        'page_target',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open_new_tab' => 'boolean',
        'sort_order' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', Carbon::now());
            });
    }

    public function scopeForPage(Builder $query, string $page = 'all'): Builder
    {
        return $query->where(function ($q) use ($page) {
            $q->where('page_target', 'all')
                ->orWhere('page_target', $page);
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id', 'desc');
    }

    public function getImageUrlAttribute(): string
    {
        $path = $this->image_path;
        if (!$path) return '';
        if (str_starts_with($path, 'http')) return $path;
        if (str_starts_with($path, '/')) return asset($path);
        return asset('storage/' . ltrim($path, '/'));
    }

    public function getMobileImageUrlAttribute(): string
    {
        $path = $this->mobile_image_path;
        if (!$path) return $this->image_url;
        if (str_starts_with($path, 'http')) return $path;
        if (str_starts_with($path, '/')) return asset($path);
        return asset('storage/' . ltrim($path, '/'));
    }
}
