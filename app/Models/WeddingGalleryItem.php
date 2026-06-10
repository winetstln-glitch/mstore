<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeddingGalleryItem extends Model
{
    protected $fillable = [
        'image_path',
        'caption',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}

