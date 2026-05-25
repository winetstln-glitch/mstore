<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsAppMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword',
        'type',
        'response_text',
        'file_path',
        'file_type',
        'is_active',
        'hits_count',
        'priority',
        'enable_fuzzy_match',
        'variables',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'enable_fuzzy_match' => 'boolean',
        'variables' => 'array',
        'expires_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function incrementHitCount()
    {
        $this->increment('hits_count');
    }
}
