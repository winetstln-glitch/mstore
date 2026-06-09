<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KnowledgeBase extends Model
{
    protected $fillable = [
        'title',
        'content',
        'content_hash',
        'embedding',
        'category',
        'category_id',
        'tags',
        'status',
        'published_at',
        'view_count',
        'helpful_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'embedding' => 'array',
        'published_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function categoryModel(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeBaseTag::class, 'knowledge_base_document_tag', 'knowledge_base_id', 'knowledge_base_tag_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function markAsHelpful()
    {
        $this->increment('helpful_count');
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }
}
