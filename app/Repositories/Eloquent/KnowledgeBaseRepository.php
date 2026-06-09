<?php

namespace App\Repositories\Eloquent;

use App\Models\KnowledgeBase;
use App\Repositories\Contracts\KnowledgeBaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KnowledgeBaseRepository implements KnowledgeBaseRepositoryInterface
{
    public function paginateAdmin(int $perPage = 20): LengthAwarePaginator
    {
        return KnowledgeBase::query()
            ->with(['createdBy:id,name', 'updatedBy:id,name', 'categoryModel:id,name'])
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function find(int $id): ?KnowledgeBase
    {
        return KnowledgeBase::query()->with(['tags:id,name', 'categoryModel:id,name'])->find($id);
    }

    public function create(array $attributes): KnowledgeBase
    {
        return KnowledgeBase::create($attributes);
    }

    public function update(KnowledgeBase $document, array $attributes): KnowledgeBase
    {
        $document->fill($attributes);
        $document->save();

        return $document->fresh();
    }

    public function delete(KnowledgeBase $document): void
    {
        $document->delete();
    }
}
