<?php

namespace App\Services;

use App\Models\KnowledgeBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KnowledgeBaseRetrievalService
{
    public function __construct(
        private readonly KnowledgeBaseEmbeddingService $embedding,
    ) {}

    public function retrieve(string $query, int $limit = 3): Collection
    {
        $q = trim($query);
        if ($q === '') {
            return collect();
        }

        $qShort = mb_substr($q, 0, 180);
        $qVec = $this->embedding->embed($qShort);

        $candidates = KnowledgeBase::query()
            ->published()
            ->where(function ($qb) use ($qShort) {
                $qb->where('title', 'like', '%'.$qShort.'%')
                    ->orWhere('content', 'like', '%'.$qShort.'%');
            })
            ->latest('updated_at')
            ->limit(80)
            ->get();

        if ($candidates->isEmpty()) {
            $candidates = KnowledgeBase::query()
                ->published()
                ->latest('updated_at')
                ->limit(80)
                ->get();
        }

        $scored = $candidates->map(function (KnowledgeBase $doc) use ($qVec) {
            $docVec = is_array($doc->embedding) ? $doc->embedding : null;
            if (! $docVec) {
                $docVec = $this->embedding->embed($doc->title."\n".$doc->content);
                $doc->forceFill([
                    'embedding' => $docVec,
                    'content_hash' => hash('sha256', $doc->title."\n".$doc->content),
                ])->saveQuietly();
            }

            $score = $this->embedding->cosine($qVec, $docVec);

            return [
                'doc' => $doc,
                'score' => $score,
            ];
        })
            ->sortByDesc('score')
            ->values();

        return $scored->take($limit);
    }

    public function buildAnswerFromTopDocs(string $query, int $limit = 3): ?string
    {
        $results = $this->retrieve($query, $limit);
        if ($results->isEmpty()) {
            return null;
        }

        $top = $results->first();
        $doc = $top['doc'];
        $content = trim(strip_tags((string) $doc->content));
        $content = Str::limit($content, 900);

        $title = trim((string) $doc->title);
        $prefix = $title !== '' ? "*{$title}*\n\n" : '';

        return $prefix.$content;
    }
}

