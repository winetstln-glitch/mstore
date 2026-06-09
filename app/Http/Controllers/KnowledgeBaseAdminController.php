<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeBaseCategory;
use App\Models\KnowledgeBaseTag;
use App\Repositories\Contracts\KnowledgeBaseRepositoryInterface;
use App\Services\KnowledgeBaseEmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class KnowledgeBaseAdminController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:whatsapp.kb.manage', only: ['index', 'create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index(Request $request, KnowledgeBaseRepositoryInterface $repository)
    {
        $docs = $repository->paginateAdmin(20);

        return view('whatsapp.knowledge_base.index', [
            'docs' => $docs,
        ]);
    }

    public function create()
    {
        return view('whatsapp.knowledge_base.form', [
            'doc' => new KnowledgeBase(),
            'categories' => KnowledgeBaseCategory::query()->orderBy('name')->get(),
            'tags' => KnowledgeBaseTag::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, KnowledgeBaseRepositoryInterface $repository, KnowledgeBaseEmbeddingService $embedding)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:knowledge_base_categories,id'],
            'tags_csv' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $tags = $this->parseTagsCsv($validated['tags_csv'] ?? null);
        $doc = $repository->create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'content_hash' => hash('sha256', $validated['title']."\n".$validated['content']),
            'embedding' => $embedding->embed($validated['title']."\n".$validated['content']),
            'category' => $validated['category'] ?? ($this->resolveCategoryName($validated['category_id'] ?? null) ?? 'FAQ'),
            'category_id' => $validated['category_id'] ?? null,
            'tags' => $tags,
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published' ? now() : null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $this->syncTags($doc, $tags);

        return redirect()->route('whatsapp.kb.index');
    }

    public function edit(int $id, KnowledgeBaseRepositoryInterface $repository)
    {
        $doc = $repository->find($id);
        abort_if(! $doc, 404);

        return view('whatsapp.knowledge_base.form', [
            'doc' => $doc,
            'categories' => KnowledgeBaseCategory::query()->orderBy('name')->get(),
            'tags' => KnowledgeBaseTag::query()->orderBy('name')->get(),
        ]);
    }

    public function update(int $id, Request $request, KnowledgeBaseRepositoryInterface $repository, KnowledgeBaseEmbeddingService $embedding)
    {
        $doc = $repository->find($id);
        abort_if(! $doc, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:knowledge_base_categories,id'],
            'tags_csv' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $tags = $this->parseTagsCsv($validated['tags_csv'] ?? null);
        $hash = hash('sha256', $validated['title']."\n".$validated['content']);
        $newEmbedding = $doc->content_hash !== $hash ? $embedding->embed($validated['title']."\n".$validated['content']) : $doc->embedding;

        $repository->update($doc, [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'content_hash' => $hash,
            'embedding' => $newEmbedding,
            'category' => $validated['category'] ?? ($this->resolveCategoryName($validated['category_id'] ?? null) ?? $doc->category),
            'category_id' => $validated['category_id'] ?? null,
            'tags' => $tags,
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published' ? ($doc->published_at ?? now()) : null,
            'updated_by' => $request->user()?->id,
        ]);

        $this->syncTags($doc->fresh(), $tags);

        return redirect()->route('whatsapp.kb.index');
    }

    public function destroy(int $id, KnowledgeBaseRepositoryInterface $repository)
    {
        $doc = $repository->find($id);
        abort_if(! $doc, 404);

        $repository->delete($doc);

        return redirect()->route('whatsapp.kb.index');
    }

    private function syncTags(KnowledgeBase $doc, array $tags): void
    {
        $tagIds = [];
        foreach ($tags as $tagName) {
            $tagName = trim((string) $tagName);
            if ($tagName === '') {
                continue;
            }
            $tag = KnowledgeBaseTag::query()->firstOrCreate(
                ['slug' => Str::slug($tagName)],
                ['name' => $tagName]
            );
            $tagIds[] = $tag->id;
        }

        $doc->tags()->sync($tagIds);
    }

    private function resolveCategoryName(?int $categoryId): ?string
    {
        if (! $categoryId) {
            return null;
        }
        $cat = KnowledgeBaseCategory::query()->find($categoryId);

        return $cat?->name;
    }

    private function parseTagsCsv(?string $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $raw = explode(',', $value);
        $tags = [];
        foreach ($raw as $t) {
            $t = trim((string) $t);
            if ($t === '') {
                continue;
            }
            $tags[] = mb_substr($t, 0, 80);
        }

        return array_values(array_unique($tags));
    }
}
