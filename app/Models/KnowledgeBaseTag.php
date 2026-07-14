<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KnowledgeBaseTag extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeBase::class, 'knowledge_base_document_tag', 'knowledge_base_tag_id', 'knowledge_base_id');
    }
}

