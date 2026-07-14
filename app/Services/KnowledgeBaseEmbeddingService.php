<?php

namespace App\Services;

class KnowledgeBaseEmbeddingService
{
    public function embed(string $text, int $dimensions = 128): array
    {
        $vector = array_fill(0, $dimensions, 0.0);
        $tokens = preg_split('/\s+/', strtolower(trim($text))) ?: [];

        foreach ($tokens as $token) {
            $token = preg_replace('/[^a-z0-9\-_]+/i', '', $token);
            if (! is_string($token) || $token === '' || strlen($token) < 3) {
                continue;
            }
            $idx = (int) (abs(crc32($token)) % $dimensions);
            $vector[$idx] += 1.0;
        }

        $norm = 0.0;
        foreach ($vector as $v) {
            $norm += $v * $v;
        }
        $norm = sqrt($norm);
        if ($norm <= 0.0) {
            return $vector;
        }

        foreach ($vector as $i => $v) {
            $vector[$i] = $v / $norm;
        }

        return $vector;
    }

    public function cosine(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        $dot = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $dot += ((float) $a[$i]) * ((float) $b[$i]);
        }

        return $dot;
    }
}

