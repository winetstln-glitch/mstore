<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

class WhatsAppIntentService
{
    private array $synonyms = [
        // Voucher synonyms
        'voucher_order' => [
            'voucher', 'vocer', 'vcr', 'pocer', 'voucher internet', 'antar voucher',
            'kirim voucher', 'ecer', 'voucher 2k', 'voucher 3k', 'voucher 5k',
            'voucher 7k', 'voucher 10k', 'voucher 20k', 'voucher 50k', 'voucher 100k'
        ],
        // Internet issue synonyms
        'internet_issue' => [
            'gangguan', 'wifi mati', 'internet mati', 'wifi lemot', 'lemot',
            'ngelag', 'ga konek', 'gk konek', 'tidak konek', 'putus putus',
            'tidak ada internet', 'jaringan error', 'wifi error', 'jaringan putus',
            'wifi abis'
        ],
        // Billing issue synonyms
        'billing_issue' => [
            'tagihan', 'jatuh tempo', 'bayar wifi', 'belum bayar', 'invoice',
            'pembayaran', 'biaya', 'bayar tagihan', 'bayar'
        ],
        // Greeting synonyms
        'greeting' => [
            'halo', 'hi', 'hello', 'hey', 'hai', 'selamat pagi',
            'selamat siang', 'selamat sore', 'selamat malam', 'punten'
        ],
        // Help synonyms
        'help' => [
            'bantuan', 'help', 'menu', 'tolong'
        ]
    ];

    private array $sundaneseDictionary = [
        'hoyong' => 'mau',
        'vocer' => 'voucher',
        'pocer' => 'voucher',
        'vcr' => 'voucher',
        'punten' => 'permisi',
        'nggeh' => 'iya',
        'gocap' => '50'
    ];

    public function classifyIntent(string $message): array
    {
        $originalMessage = $message;
        $normalizedMessage = $this->normalizeText($message);

        Log::info('Classifying WhatsApp intent', [
            'original_message' => $originalMessage,
            'normalized_message' => $normalizedMessage
        ]);

        $intent = 'unknown';
        $confidence = 0;

        // Check each intent
        foreach ($this->synonyms as $intentKey => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalizedMessage, $keyword)) {
                    $newConfidence = $this->calculateConfidence($keyword, $normalizedMessage);
                    if ($newConfidence > $confidence) {
                        $intent = $intentKey;
                        $confidence = $newConfidence;
                    }
                }
            }
        }

        Log::info('Intent classified', [
            'original_message' => $originalMessage,
            'normalized_message' => $normalizedMessage,
            'intent' => $intent,
            'confidence_score' => $confidence
        ]);

        return [
            'intent' => $intent,
            'confidence_score' => $confidence,
            'normalized_message' => $normalizedMessage
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower(trim($text));

        // Apply Sundanese dictionary
        foreach ($this->sundaneseDictionary as $sundanese => $indonesian) {
            $text = str_replace($sundanese, $indonesian, $text);
        }

        // Remove punctuation
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        // Replace multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
    }

    private function calculateConfidence(string $keyword, string $message): int
    {
        $keywordLength = mb_strlen($keyword);
        $messageLength = mb_strlen($message);
        
        if ($messageLength > 0) {
            $ratio = $keywordLength / $messageLength;
            $confidence = (int) min(100, $ratio * 200);
            return $confidence;
        }
        
        return 50;
    }

    public function getReplyForIntent(string $intent): ?string
    {
        $replies = [
            'voucher_order' => "Halo! 🎫 Terima kasih atas pesanan voucher Anda.\n\nSilakan informasikan:\n1. Jenis voucher yang ingin dipesan\n2. Jumlah voucher\n3. Alamat pengiriman\n\nKami akan segera memproses pesanan Anda! 🙏",
            'internet_issue' => "Maaf mendengarnya! 😔\n\nUntuk membantu penanganan gangguan internet, silakan informasikan:\n1. Nama pelanggan\n2. ID pelanggan\n3. Alamat pemasangan\n4. Deskripsi gangguan\n\nKami akan segera mengirim teknisi untuk pemeriksaan! 🙏",
            'billing_issue' => "Halo! 💳\n\nUntuk informasi tagihan, silakan informasikan:\n1. Nama pelanggan\n2. ID pelanggan\n\nKami akan memberikan informasi tagihan Anda! 🙏",
            'greeting' => null,
            'help' => null,
        ];

        return $replies[$intent] ?? null;
    }
}
