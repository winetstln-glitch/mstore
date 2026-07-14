<?php

namespace App\Repositories\Contracts;

use App\Models\KnowledgeBase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KnowledgeBaseRepositoryInterface
{
    public function paginateAdmin(int $perPage = 20): LengthAwarePaginator;

    public function find(int $id): ?KnowledgeBase;

    public function create(array $attributes): KnowledgeBase;

    public function update(KnowledgeBase $document, array $attributes): KnowledgeBase;

    public function delete(KnowledgeBase $document): void;
}

