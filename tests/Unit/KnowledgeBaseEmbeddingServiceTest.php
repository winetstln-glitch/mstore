<?php

namespace Tests\Unit;

use App\Services\KnowledgeBaseEmbeddingService;
use PHPUnit\Framework\TestCase;

class KnowledgeBaseEmbeddingServiceTest extends TestCase
{
    public function test_it_produces_higher_similarity_for_related_text(): void
    {
        $svc = new KnowledgeBaseEmbeddingService();

        $a = $svc->embed('cara cek tagihan internet pelanggan');
        $b = $svc->embed('bagaimana melihat invoice dan tagihan pelanggan');
        $c = $svc->embed('panduan cuci kendaraan dan steam motor');

        $ab = $svc->cosine($a, $b);
        $ac = $svc->cosine($a, $c);

        $this->assertGreaterThan($ac, $ab);
        $this->assertGreaterThan(0.0, $ab);
    }
}
