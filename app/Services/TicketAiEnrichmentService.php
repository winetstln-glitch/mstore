<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\GenieDeviceStatus;
use App\Models\Ticket;
use Illuminate\Support\Str;

class TicketAiEnrichmentService
{
    public function enrichTicket(int $ticketId): void
    {
        $ticket = Ticket::query()->with(['customer:id,name,onu_serial', 'diagnostic'])->find($ticketId);
        if (! $ticket) {
            return;
        }

        $text = strtolower(trim(($ticket->subject ?? '').' '.$ticket->description));
        $categoryResult = $this->classifyCategory($text);
        $diagnosis = $this->resolveDiagnosis($ticket);
        $priority = $this->resolvePriority($ticket, $diagnosis);
        $summary = $this->buildSummary($ticket, $diagnosis, $priority);

        $ticket->forceFill([
            'ai_summary' => $summary,
            'ai_category' => $categoryResult['category'],
            'ai_confidence' => $categoryResult['confidence'],
        ])->saveQuietly();

        if ($ticket->priority !== $priority && in_array($ticket->priority, ['', 'low', 'medium'], true)) {
            $ticket->forceFill(['priority' => $priority])->saveQuietly();
        }
    }

    private function buildSummary(Ticket $ticket, string $diagnosis, string $priority): string
    {
        $customerName = $ticket->customer?->name ?? ('#'.$ticket->customer_id);
        $complaint = trim((string) ($ticket->description ?: $ticket->subject));
        if ($complaint === '') {
            $complaint = '-';
        }

        $lines = [
            'Pelanggan:',
            $customerName,
            '',
            'Keluhan:',
            Str::limit($complaint, 300),
            '',
            'Diagnosa:',
            $diagnosis !== '' ? $diagnosis : '-',
            '',
            'Prioritas:',
            strtoupper($priority),
        ];

        return implode("\n", $lines);
    }

    private function resolveDiagnosis(Ticket $ticket): string
    {
        $diag = $ticket->diagnostic?->summary ? trim((string) $ticket->diagnostic->summary) : '';
        if ($diag !== '') {
            return $diag;
        }

        $customer = $ticket->customer;
        if ($customer instanceof Customer) {
            $status = GenieDeviceStatus::query()
                ->where('customer_id', $customer->id)
                ->latest('updated_at')
                ->first();
            if ($status && is_string($status->last_reason) && trim($status->last_reason) !== '') {
                return trim($status->last_reason);
            }
        }

        $text = strtolower(trim(($ticket->subject ?? '').' '.$ticket->description));
        if (str_contains($text, 'los')) {
            return 'ONU LOS';
        }
        if (str_contains($text, 'dying gasp')) {
            return 'ONU Dying Gasp';
        }
        if (str_contains($text, 'pppoe')) {
            return 'PPPoE Issue';
        }

        return '';
    }

    private function resolvePriority(Ticket $ticket, string $diagnosis): string
    {
        $text = strtolower(trim(($ticket->subject ?? '').' '.$ticket->description.' '.$diagnosis));
        if (str_contains($text, 'olt down') || str_contains($text, 'fiber cut')) {
            return 'high';
        }
        if (str_contains($text, 'los') || str_contains($text, 'internet mati') || str_contains($text, 'offline')) {
            return 'high';
        }
        if ($ticket->type === 'outage') {
            return 'high';
        }

        return 'medium';
    }

    private function classifyCategory(string $text): array
    {
        $rules = [
            'billing' => ['tagihan', 'invoice', 'bayar', 'pembayaran', 'duitku', 'qris', 'lunas'],
            'pppoe' => ['pppoe', 'ppp', 'dial', 'username', 'password', 'secret'],
            'hotspot' => ['hotspot', 'voucher', 'wifi id', 'login hotspot'],
            'onu' => ['onu', 'ont', 'modem', 'los', 'dying gasp', 'rx power', 'tx power', 'rdm power'],
            'olt' => ['olt', 'pon', 'gpon', 'port', 'uplink'],
            'fiber cut' => ['fiber cut', 'kabel putus', 'putus fiber', 'fo putus'],
            'instalasi' => ['pasang', 'instalasi', 'survey', 'pemasangan', 'daftar internet'],
            'cctv' => ['cctv', 'kamera'],
            'atk' => ['atk', 'alat tulis', 'stationery'],
            'wash' => ['wash', 'cuci', 'steam'],
            'wedding' => ['wedding', 'nikah', 'pernikahan'],
            'event' => ['event', 'eo', 'organizer'],
        ];

        $best = ['category' => null, 'score' => 0];
        foreach ($rules as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    $score++;
                }
            }
            if ($score > $best['score']) {
                $best = ['category' => $category, 'score' => $score];
            }
        }

        if (! $best['category']) {
            return ['category' => null, 'confidence' => null];
        }

        $confidence = min(1.0, $best['score'] / 3);

        return [
            'category' => $best['category'],
            'confidence' => (float) number_format($confidence * 100, 2, '.', ''),
        ];
    }
}

